<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\Model;

/**
 * Methods supporting applied pull requests.
 *
 * @since  2.0
 */
class TestsModel extends \JModelDatabase
{
	/**
	 * Retrieves a list of applied patches
	 *
	 * @return  mixed
	 *
	 * @since   2.0
	 */
	public function getAppliedPatches()
	{
		$db = $this->getDb();

		$db->setQuery(
			$db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__patchtester_tests'))
				->where($db->quoteName('applied') . ' = 1')
		);

		return $db->loadObjectList('pull_id');
	}

	/**
	 * Truncates the tests table
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function truncateTable()
	{
		$this->getDb()->truncateTable('#__patchtester_tests');
	}
}
