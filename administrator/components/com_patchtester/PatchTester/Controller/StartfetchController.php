<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use PatchTester\Helper;
use PatchTester\Model\TestsModel;

/**
 * Controller class to start fetching remote data
 *
 * @since  2.0
 */
class StartfetchController extends AbstractController
{
	/**
	 * Execute the controller.
	 *
	 * @return  void  Redirects the application
	 *
	 * @since   2.0
	 */
	public function execute()
	{
		// We don't want this request to be cached.
		$this->getApplication()->setHeader('Expires', 'Mon, 1 Jan 2001 00:00:00 GMT', true);
		$this->getApplication()->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT', true);
		$this->getApplication()->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', false);
		$this->getApplication()->setHeader('Pragma', 'no-cache');
		$this->getApplication()->setHeader('Content-Type', $this->getApplication()->mimeType . '; charset=' . $this->getApplication()->charSet);

		// Check for a valid token. If invalid, send a 403 with the error message.
		if (!Session::checkToken('request'))
		{
			$response = new JsonResponse(new \Exception(\JText::_('JINVALID_TOKEN'), 403));

			$this->getApplication()->sendHeaders();
			echo json_encode($response);

			$this->getApplication()->close(1);
		}

		// Make sure we can fetch the data from GitHub - throw an error on < 10 available requests
		try
		{
			$rateResponse = Helper::initializeGithub()->getRateLimit();
			$rate         = json_decode($rateResponse->body);
		}
		catch (\Exception $e)
		{
			$response = new JsonResponse(
				new \Exception(
					\JText::sprintf('COM_PATCHTESTER_COULD_NOT_CONNECT_TO_GITHUB', $e->getMessage()),
					$e->getCode(),
					$e
				)
			);

			$this->getApplication()->sendHeaders();
			echo json_encode($response);

			$this->getApplication()->close(1);
		}

		// If over the API limit, we can't build this list
		if ($rate->resources->core->remaining < 10)
		{
			$response = new JsonResponse(
				new \Exception(
					\JText::sprintf('COM_PATCHTESTER_API_LIMIT_LIST', Factory::getDate($rate->resources->core->reset)),
					429
				)
			);

			$this->getApplication()->sendHeaders();
			echo json_encode($response);

			$this->getApplication()->close(1);
		}

		$testsModel = new TestsModel(null, Factory::getDbo());

		try
		{
			// Sanity check, ensure there aren't any applied patches
			if (count($testsModel->getAppliedPatches()) >= 1)
			{
				$response = new JsonResponse(new \Exception(\JText::_('COM_PATCHTESTER_ERROR_APPLIED_PATCHES'), 500));

				$this->getApplication()->sendHeaders();
				echo json_encode($response);

				$this->getApplication()->close(1);
			}
		}
		catch (\Exception $e)
		{
			$response = new JsonResponse($e);

			$this->getApplication()->sendHeaders();
			echo json_encode($response);

			$this->getApplication()->close(1);
		}

		// We're able to successfully pull data, prepare our environment
		Factory::getSession()->set('com_patchtester_fetcher_page', 1);

		$response = new JsonResponse(
			array('complete' => false, 'header' => \JText::_('COM_PATCHTESTER_FETCH_PROCESSING', true)),
			\JText::sprintf('COM_PATCHTESTER_FETCH_PAGE_NUMBER', 1),
			false,
			true
		);

		$this->getApplication()->sendHeaders();
		echo json_encode($response);

		$this->getApplication()->close();
	}
}
