<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\Controller;

use Joomla\Application\AbstractApplication;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/**
 * Base controller for the patch testing component
 *
 * @since  2.0
 *
 * @method  CMSApplication  getApplication()  getApplication()  Get the application object.
 */
abstract class AbstractController extends \JControllerBase
{
	/**
	 * The object context
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $context;

	/**
	 * The default view to display
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $defaultView = 'pulls';

	/**
	 * Instantiate the controller
	 *
	 * @param   \JInput              $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since   2.0
	 */
	public function __construct(\JInput $input = null, AbstractApplication $app = null)
	{
		parent::__construct($input, $app);

		// Set the context for the controller
		$this->context = 'com_patchtester.' . $this->getInput()->getCmd('view', $this->defaultView);
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
		$state = new Registry;

		// Load the parameters.
		$params = ComponentHelper::getParams('com_patchtester');

		$state->set('github_user', $params->get('org', 'joomla'));
		$state->set('github_repo', $params->get('repo', 'joomla-cms'));

		return $state;
	}
}
