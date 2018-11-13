<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\ComponentAdapter;
use Joomla\CMS\Installer\InstallerScript;

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @since  2.0
 */
class Com_PatchtesterInstallerScript extends InstallerScript
{
	/**
	 * Array of templates with supported overrides
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $templateOverrides = array('atum', 'hathor');

	/**
	 * Extension script constructor.
	 *
	 * @since   3.0.0
	 */
	public function __construct()
	{
		$this->minimumJoomla = '3.8';
		$this->minimumPhp    = JOOMLA_MINIMUM_PHP;

		$this->deleteFiles = array(
			'/administrator/components/com_patchtester/PatchTester/View/Pulls/tmpl/default_errors.php',
		);

		$this->deleteFolders = array(
			'/administrator/components/com_patchtester/PatchTester/Table',
			'/components/com_patchtester',
		);
	}

	/**
	 * Function to perform changes during install
	 *
	 * @param   ComponentAdapter  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function install($parent)
	{
		$this->copyLayouts();
	}

	/**
	 * Function to perform changes during update
	 *
	 * @param   ComponentAdapter  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function update($parent)
	{
		$this->copyLayouts();
	}

	/**
	 * Function to perform changes during uninstall
	 *
	 * @param   ComponentAdapter  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function uninstall($parent)
	{
		jimport('joomla.filesystem.folder');

		// Initialize the error array
		$errorTemplates = array();

		// Loop the supported templates
		foreach ($this->templateOverrides as $template)
		{
			// Set the file paths
			$tmplRoot       = JPATH_ADMINISTRATOR . '/templates/' . $template;
			$overrideFolder = JPATH_ADMINISTRATOR . '/templates/' . $template . '/html/com_patchtester';

			// Make sure the template is actually installed
			if (is_dir($tmplRoot))
			{
				// If there's a failure in copying the overrides, log it to the error array
				if (!JFolder::delete($overrideFolder))
				{
					$errorTemplates[] = ucfirst($template);
				}
			}
		}

		// If we couldn't remove any overrides, notify the user
		if (count($errorTemplates) > 0)
		{
			Factory::getApplication()->enqueueMessage(JText::sprintf('COM_PATCHTESTER_COULD_NOT_REMOVE_OVERRIDES', implode(', ', $errorTemplates)));
		}
	}

	/**
	 * Function to perform changes during postflight
	 *
	 * @param   string            $type    The action being performed
	 * @param   ComponentAdapter  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   3.0.0
	 */
	public function postflight($type, $parent)
	{
		$this->removeFiles();
	}

	/**
	 * Function to copy layout overrides for core templates at install or update
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	private function copyLayouts()
	{
		jimport('joomla.filesystem.folder');

		// Initialize the error array
		$errorTemplates = array();

		// Loop the supported templates
		foreach ($this->templateOverrides as $template)
		{
			// Set the file paths
			$source      = __DIR__ . '/' . $template;
			$tmplRoot    = JPATH_ADMINISTRATOR . '/templates/' . $template;
			$destination = JPATH_ADMINISTRATOR . '/templates/' . $template . '/html/com_patchtester';

			// Make sure the template is actually installed
			if (is_dir($tmplRoot))
			{
				// If there's a failure in copying the overrides, log it to the error array
				try
				{
					if (!JFolder::copy($source, $destination, '', true))
					{
						$errorTemplates[] = ucfirst($template);
					}
				}
				catch (RuntimeException $exception)
				{
					$errorTemplates[] = ucfirst($template);
				}
			}
		}

		// If we couldn't remove any overrides, notify the user
		if (count($errorTemplates) > 0)
		{
			Factory::getApplication()->enqueueMessage(JText::sprintf('COM_PATCHTESTER_COULD_NOT_INSTALL_OVERRIDES', implode(', ', $errorTemplates)));
		}
	}
}
