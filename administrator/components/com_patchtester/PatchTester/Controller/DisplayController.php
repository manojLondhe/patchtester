<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\Controller;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

/**
 * Default display controller
 *
 * @since  2.0
 */
class DisplayController extends AbstractController
{
	/**
	 * Default ordering column
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $defaultOrderColumn = 'a.pull_id';

	/**
	 * Default sort direction
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $defaultDirection = 'DESC';

	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		// Set up variables to build our classes
		$view   = $this->getInput()->getCmd('view', $this->defaultView);
		$format = $this->getInput()->getCmd('format', 'html');

		// Register the layout paths for the view
		$paths = new \SplPriorityQueue;

		// Add the path for template overrides
		$paths->insert(JPATH_THEMES . '/' . $this->getApplication()->getTemplate() . '/html/com_patchtester/' . $view, 2);

		// Add the path for the default layouts
		$paths->insert(dirname(__DIR__) . '/View/' . ucfirst($view) . '/tmpl', 1);

		// Build the class names for the model and view
		$viewClass  = '\\PatchTester\\View\\' . ucfirst($view) . '\\' . ucfirst($view) . ucfirst($format) . 'View';
		$modelClass = '\\PatchTester\\Model\\' . ucfirst($view) . 'Model';

		// Sanity check - Ensure our classes exist
		if (!class_exists($viewClass))
		{
			// Try to use a default view
			$viewClass = '\\PatchTester\\View\\Default' . ucfirst($format) . 'View';

			if (!class_exists($viewClass))
			{
				throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_VIEW_NOT_FOUND', $view, $format), 500);
			}
		}

		if (!class_exists($modelClass))
		{
			throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_MODEL_NOT_FOUND', $modelClass), 500);
		}

		// Initialize the model class now; need to do it before setting the state to get required data from it
		$model = new $modelClass($this->context, null, Factory::getDbo());

		// Initialize the state for the model
		$model->setState($this->initializeState($model));

		// Initialize the view class now
		$view = new $viewClass($model, $paths);

		// Echo the rendered view for the application
		echo $view->render();

		// Finished!
		return true;
	}

	/**
	 * Sets the state for the model object
	 *
	 * @param   \JModel  $model  Model object
	 *
	 * @return  Registry
	 *
	 * @since   2.0
	 */
	protected function initializeState(\JModel $model)
	{
		$state = parent::initializeState($model);

		// Load the filter state.
		$state->set('filter.search', $this->getApplication()->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', ''));
		$state->set('filter.applied', $this->getApplication()->getUserStateFromRequest($this->context . '.filter.applied', 'filter_applied', ''));
		$state->set('filter.branch', $this->getApplication()->getUserStateFromRequest($this->context . '.filter.branch', 'filter_branch', ''));
		$state->set('filter.rtc', $this->getApplication()->getUserStateFromRequest($this->context . '.filter.rtc', 'filter_rtc', ''));

		// Pre-fill the limits.
		$limit = $this->getApplication()->getUserStateFromRequest('global.list.limit', 'limit', $this->getApplication()->get('list_limit', 20), 'uint');
		$state->set('list.limit', $limit);

		// Check if the ordering field is in the white list, otherwise use the incoming value.
		$value = $this->getApplication()->getUserStateFromRequest($this->context . '.ordercol', 'filter_order', $this->defaultOrderColumn);

		if (!in_array($value, $model->getSortFields()))
		{
			$value = $this->defaultOrderColumn;
			$this->getApplication()->setUserState($this->context . '.ordercol', $value);
		}

		$state->set('list.ordering', $value);

		// Check if the ordering direction is valid, otherwise use the incoming value.
		$value = $this->getApplication()->getUserStateFromRequest($this->context . '.orderdirn', 'filter_order_Dir', $this->defaultDirection);

		if (!in_array(strtoupper($value), array('ASC', 'DESC', '')))
		{
			$value = $this->defaultDirection;
			$this->getApplication()->setUserState($this->context . '.orderdirn', $value);
		}

		$state->set('list.direction', $value);

		$value = $this->getApplication()->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0);
		$limitstart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
		$state->set('list.start', $limitstart);

		return $state;
	}
}
