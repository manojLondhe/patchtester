<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\View\Pulls;

use Joomla\CMS\Factory;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\Registry\Registry;
use PatchTester\TrackerHelper;
use PatchTester\View\DefaultHtmlView;

/**
 * View class for a list of pull requests.
 *
 * @since  2.0
 *
 * @property-read  \PatchTester\Model\PullsModel  $model  The model object.
 */
class PullsHtmlView extends DefaultHtmlView
{
	/**
	 * Array containing the list of branches
	 *
	 * @var    array
	 * @since  3.0.0
	 */
	protected $branches = array();

	/**
	 * Array containing environment errors
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $envErrors = array();

	/**
	 * Array of open pull requests
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $items;

	/**
	 * Pagination object
	 *
	 * @var    Pagination
	 * @since  2.0
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var    Registry
	 * @since  2.0
	 */
	protected $state;

	/**
	 * The issue tracker project alias
	 *
	 * @var    string|boolean
	 * @since  2.0
	 */
	protected $trackerAlias;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   2.0
	 */
	public function render()
	{
		if (!extension_loaded('openssl'))
		{
			$this->envErrors[] = \JText::_('COM_PATCHTESTER_REQUIREMENT_OPENSSL');
		}

		if (!in_array('https', stream_get_wrappers()))
		{
			$this->envErrors[] = \JText::_('COM_PATCHTESTER_REQUIREMENT_HTTPS');
		}

		// Only process the data if there are no environment errors
		if (!count($this->envErrors))
		{
			$this->state        = $this->model->getState();
			$this->items        = $this->model->getItems();
			$this->pagination   = $this->model->getPagination();
			$this->trackerAlias = TrackerHelper::getTrackerAlias($this->state->get('github_user'), $this->state->get('github_repo'));
			$this->branches     = $this->model->getBranches();
		}

		// Change the layout if there are environment errors
		if (count($this->envErrors))
		{
			$this->setLayout('errors');
		}

		$this->addToolbar();

		// Make text strings available in the JavaScript API
		\JText::script('COM_PATCHTESTER_CONFIRM_RESET');

		// Set a warning on 4.0 branch
		if (version_compare(JVERSION, '4.0', 'ge'))
		{
			Factory::getApplication()->enqueueMessage(\JText::_('COM_PATCHTESTER_40_WARNING'), 'warning');
		}

		return parent::render();
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	protected function addToolbar()
	{
		\JToolbarHelper::title(\JText::_('COM_PATCHTESTER'), 'patchtester icon-apply');

		if (!count($this->envErrors))
		{
			$toolbar = Toolbar::getInstance('toolbar');

			$toolbar->appendButton(
				'Popup',
				'refresh',
				'COM_PATCHTESTER_TOOLBAR_FETCH_DATA',
				'index.php?option=com_patchtester&view=fetch&tmpl=component',
				500,
				210,
				0,
				0,
				'window.parent.location.reload()',
				'COM_PATCHTESTER_HEADING_FETCH_DATA'
			);

			// Add a reset button.
			$toolbar->appendButton('Standard', 'expired', 'COM_PATCHTESTER_TOOLBAR_RESET', 'reset', false);
		}

		\JToolbarHelper::preferences('com_patchtester');
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   2.0
	 */
	protected function getSortFields()
	{
		return array(
			'a.title'   => \JText::_('JGLOBAL_TITLE'),
			'a.pull_id' => \JText::_('COM_PATCHTESTER_PULL_ID'),
			'applied'   => \JText::_('JSTATUS')
		);
	}
}
