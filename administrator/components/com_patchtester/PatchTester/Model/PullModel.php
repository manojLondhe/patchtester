<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use PatchTester\GitHub\Exception\UnexpectedResponse;
use PatchTester\Helper;

/**
 * Methods supporting pull requests.
 *
 * @since  2.0
 */
class PullModel extends \JModelDatabase
{
	/**
	 * Array containing top level non-production folders
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $nonProductionFolders = array(
		'build',
		'docs',
		'installation',
		'tests',
		'.github',
	);

	/**
	 * Array containing non-production files
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $nonProductionFiles = array(
		'.drone.yml',
		'.gitignore',
		'.php_cs',
		'.travis.yml',
		'README.md',
		'build.xml',
		'composer.json',
		'composer.lock',
		'phpunit.xml.dist',
		'robots.txt.dist',
		'travisci-phpunit.xml',
		'LICENSE',
		'RoboFile.dist.ini',
		'RoboFile.php',
		'codeception.yml',
		'jorobo.dist.ini',
		'manifest.xml',
		'crowdin.yaml',
	);

	/**
	 * Parse the list of modified files from a pull request
	 *
	 * @param   object  $files  The modified files to parse
	 *
	 * @return  array
	 *
	 * @since   3.0.0
	 */
	protected function parseFileList($files)
	{
		$parsedFiles = array();

		/*
		 * Check if the patch tester is running in a development environment
		 * If we are not in development, we'll need to check the exclusion lists
		 */
		$isDev = file_exists(JPATH_INSTALLATION . '/index.php');

		foreach ($files as $file)
		{
			if (!$isDev)
			{
				$filePath = explode('/', $file->filename);

				if (in_array($filePath[0], $this->nonProductionFiles))
				{
					continue;
				}

				if (in_array($filePath[0], $this->nonProductionFolders))
				{
					continue;
				}
			}

			// Sometimes the repo filename is not the production file name
			$prodFileName        = $file->filename;
			$prodRenamedFileName = isset($file->previous_filename) ? $file->previous_filename : false;
			$filePath            = explode('/', $prodFileName);

			// Remove the `src` here to match the CMS paths if needed
			if ($filePath[0] === 'src')
			{
				$prodFileName = str_replace('src/', '', $prodFileName);
			}

			if ($prodRenamedFileName)
			{
				$filePath = explode('/', $prodRenamedFileName);

				// Remove the `src` here to match the CMS paths if needed
				if ($filePath[0] === 'src')
				{
					$prodRenamedFileName = str_replace('src/', '', $prodRenamedFileName);
				}
			}

			$parsedFiles[] = (object) array(
				'action'       => $file->status,
				'filename'     => $prodFileName,
				'repofilename' => $file->filename,
				'fileurl'      => $file->contents_url,
				'originalFile' => $prodRenamedFileName,
			);
		}

		return $parsedFiles;
	}

	/**
	 * Patches the code with the supplied pull request
	 *
	 * @param   integer  $id  ID of the pull request to apply
	 *
	 * @return  boolean
	 *
	 * @since   2.0
	 * @throws  \RuntimeException
	 */
	public function apply($id)
	{
		// Get the Github object
		$github = Helper::initializeGithub();

		try
		{
			$rateResponse = $github->getRateLimit();
			$rate         = json_decode($rateResponse->body);
		}
		catch (UnexpectedResponse $e)
		{
			throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_COULD_NOT_CONNECT_TO_GITHUB', $e->getMessage()), $e->getCode(), $e);
		}

		// If over the API limit, we can't build this list
		if ($rate->resources->core->remaining == 0)
		{
			throw new \RuntimeException(
				\JText::sprintf('COM_PATCHTESTER_API_LIMIT_LIST', Factory::getDate($rate->resources->core->reset))
			);
		}

		try
		{
			$pullResponse = $github->getPullRequest($this->getState()->get('github_user'), $this->getState()->get('github_repo'), $id);
			$pull         = json_decode($pullResponse->body);
		}
		catch (UnexpectedResponse $e)
		{
			throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_COULD_NOT_CONNECT_TO_GITHUB', $e->getMessage()), $e->getCode(), $e);
		}

		if (is_null($pull->head->repo))
		{
			throw new \RuntimeException(\JText::_('COM_PATCHTESTER_REPO_IS_GONE'));
		}

		try
		{
			$filesResponse = $github->getFilesForPullRequest($this->getState()->get('github_user'), $this->getState()->get('github_repo'), $id);
			$files         = json_decode($filesResponse->body);
		}
		catch (UnexpectedResponse $e)
		{
			throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_COULD_NOT_CONNECT_TO_GITHUB', $e->getMessage()), $e->getCode(), $e);
		}

		if (!count($files))
		{
			return false;
		}

		$parsedFiles = $this->parseFileList($files);

		foreach ($parsedFiles as $file)
		{
			switch ($file->action)
			{
				case 'deleted':
					if (!file_exists(JPATH_ROOT . '/' . $file->filename))
					{
						throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_FILE_DELETED_DOES_NOT_EXIST_S', $file->filename));
					}

					break;

				case 'added':
				case 'modified':
				case 'renamed':
					// If the backup file already exists, we can't apply the patch
					if (file_exists(JPATH_COMPONENT . '/backups/' . md5($file->filename) . '.txt'))
					{
						throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_CONFLICT_S', $file->filename));
					}

					if ($file->action == 'modified' && !file_exists(JPATH_ROOT . '/' . $file->filename))
					{
						throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_FILE_MODIFIED_DOES_NOT_EXIST_S', $file->filename));
					}

					try
					{
						$contentsResponse = $github->getFileContents(
							$pull->head->user->login, $this->getState()->get('github_repo'), $file->repofilename, urlencode($pull->head->ref)
						);

						$contents = json_decode($contentsResponse->body);

						// In case encoding type ever changes
						switch ($contents->encoding)
						{
							case 'base64':
								$file->body = base64_decode($contents->content);

								break;

							default:
								throw new \RuntimeException(\JText::_('COM_PATCHTESTER_ERROR_UNSUPPORTED_ENCODING'));
						}
					}
					catch (UnexpectedResponse $e)
					{
						throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_COULD_NOT_CONNECT_TO_GITHUB', $e->getMessage()), $e->getCode(), $e);
					}

					break;
			}
		}

		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.path');

		// At this point, we have ensured that we have all the new files and there are no conflicts
		foreach ($parsedFiles as $file)
		{
			// We only create a backup if the file already exists
			if ($file->action == 'deleted' || (file_exists(JPATH_ROOT . '/' . $file->filename) && $file->action == 'modified')
				|| (file_exists(JPATH_ROOT . '/' . $file->originalFile) && $file->action == 'renamed'))
			{
				$filename = $file->action == 'renamed' ? $file->originalFile : $file->filename;
				$src      = JPATH_ROOT . '/' . $filename;
				$dest     = JPATH_COMPONENT . '/backups/' . md5($filename) . '.txt';

				if (!\JFile::copy(\JPath::clean($src), $dest))
				{
					throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_CANNOT_COPY_FILE', $src, $dest));
				}
			}

			switch ($file->action)
			{
				case 'modified':
				case 'added':
					if (!\JFile::write(\JPath::clean(JPATH_ROOT . '/' . $file->filename), $file->body))
					{
						throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_CANNOT_WRITE_FILE', JPATH_ROOT . '/' . $file->filename));
					}

					break;

				case 'deleted':
					if (!\JFile::delete(\JPath::clean(JPATH_ROOT . '/' . $file->filename)))
					{
						throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE', JPATH_ROOT . '/' . $file->filename));
					}

					break;

				case 'renamed':
					if (!\JFile::delete(\JPath::clean(JPATH_ROOT . '/' . $file->originalFile)))
					{
						throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE', JPATH_ROOT . '/' . $file->originalFile));
					}

					if (!\JFile::write(\JPath::clean(JPATH_ROOT . '/' . $file->filename), $file->body))
					{
						throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_CANNOT_WRITE_FILE', JPATH_ROOT . '/' . $file->filename));
					}

					break;
			}

			// We don't need the file's body any longer (and it causes issues with binary data when json_encode() is run), so remove it
			unset($file->body);
		}

		$record = (object) array(
			'pull_id'         => $pull->number,
			'data'            => json_encode($parsedFiles),
			'patched_by'      => Factory::getUser()->id,
			'applied'         => 1,
			'applied_version' => JVERSION,
		);

		$db = $this->getDb();

		$db->insertObject('#__patchtester_tests', $record);

		// Insert the retrieved commit SHA into the pulls table for this item
		$db->setQuery(
			$db->getQuery(true)
				->update('#__patchtester_pulls')
				->set('sha = ' . $db->quote($pull->head->sha))
				->where($db->quoteName('pull_id') . ' = ' . (int) $id)
		)->execute();

		// Change the media version
		$version = new Version;
		$version->refreshMediaVersion();

		return true;
	}

	/**
	 * Reverts the specified pull request
	 *
	 * @param   integer  $id  ID of the pull request to revert
	 *
	 * @return  boolean
	 *
	 * @since   2.0
	 * @throws  \RuntimeException
	 */
	public function revert($id)
	{
		$db = $this->getDb();

		$testRecord = $db->setQuery(
			$db->getQuery(true)
				->select('*')
				->from('#__patchtester_tests')
				->where('id = ' . (int) $id)
		)->loadObject();

		// We don't want to restore files from an older version
		if ($testRecord->applied_version != JVERSION)
		{
			return $this->removeTest($testRecord);
		}

		$files = json_decode($testRecord->data);

		if (!$files)
		{
			throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_READING_DATABASE_TABLE', __METHOD__, htmlentities($testRecord->data)));
		}

		jimport('joomla.filesystem.file');

		foreach ($files as $file)
		{
			switch ($file->action)
			{
				case 'deleted':
				case 'modified':
					$src  = JPATH_COMPONENT . '/backups/' . md5($file->filename) . '.txt';
					$dest = JPATH_ROOT . '/' . $file->filename;

					if (!\JFile::copy($src, $dest))
					{
						throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_CANNOT_COPY_FILE', $src, $dest));
					}

					if (file_exists($src))
					{
						if (!\JFile::delete($src))
						{
							throw new \RuntimeException(
								\JText::sprintf('COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE', $src)
							);
						}
					}

					break;

				case 'added':
					$src = JPATH_ROOT . '/' . $file->filename;

					if (file_exists($src))
					{
						if (!\JFile::delete($src))
						{
							throw new \RuntimeException(
								\JText::sprintf('COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE', $src)
							);
						}
					}

					break;

				case 'renamed':
					$originalSrc = JPATH_COMPONENT . '/backups/' . md5($file->originalFile) . '.txt';
					$newSrc      = JPATH_ROOT . '/' . $file->filename;
					$dest        = JPATH_ROOT . '/' . $file->originalFile;

					if (!\JFile::copy($originalSrc, $dest))
					{
						throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_CANNOT_COPY_FILE', $originalSrc, $dest));
					}

					if (file_exists($originalSrc))
					{
						if (!\JFile::delete($originalSrc))
						{
							throw new \RuntimeException(
								\JText::sprintf('COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE', $originalSrc)
							);
						}
					}

					if (file_exists($newSrc))
					{
						if (!\JFile::delete($newSrc))
						{
							throw new \RuntimeException(
								\JText::sprintf('COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE', $newSrc)
							);
						}
					}

					break;
			}
		}

		// Change the media version
		$version = new Version;
		$version->refreshMediaVersion();

		return $this->removeTest($testRecord);
	}

	/**
	 * Remove the database record for a test
	 *
	 * @param   stdClass  $testRecord  The record being deleted
	 *
	 * @return  boolean
	 *
	 * @since   3.0.0
	 */
	private function removeTest($testRecord)
	{
		$db = $this->getDb();

		// Remove the retrieved commit SHA from the pulls table for this item
		$db->setQuery(
			$db->getQuery(true)
				->update('#__patchtester_pulls')
				->set('sha = ' . $db->quote(''))
				->where($db->quoteName('pull_id') . ' = ' . (int) $testRecord->pull_id)
		)->execute();

		// And delete the record from the tests table
		$db->setQuery(
			$db->getQuery(true)
				->delete('#__patchtester_tests')
				->where('id = ' . (int) $testRecord->id)
		)->execute();

		return true;
	}
}
