<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\View;

/**
 * Default HTML view class.
 *
 * @since  2.0
 */
class DefaultHtmlView extends \JViewHtml
{
	/**
	 * Load a template file -- first look in the templates folder for an override
	 *
	 * @param   string  $tpl  The name of the template source file; automatically searches the template paths and compiles as needed.
	 *
	 * @return  string  The output of the the template script.
	 *
	 * @since   2.0
	 * @throws  \RuntimeException
	 */
	public function loadTemplate($tpl = null)
	{
		// Get the path to the file
		$file = $this->getLayout();

		if (isset($tpl))
		{
			$file .= '_' . $tpl;
		}

		$path = $this->getPath($file);

		if (!$path)
		{
			throw new \RuntimeException(\JText::sprintf('JLIB_APPLICATION_ERROR_LAYOUTFILE_NOT_FOUND', $file), 500);
		}

		// Unset so as not to introduce into template scope
		unset($tpl);
		unset($file);

		// Never allow a 'this' property
		if (isset($this->this))
		{
			unset($this->this);
		}

		// Start an output buffer.
		ob_start();

		// Load the template.
		include $path;

		// Get the layout contents.
		return ob_get_clean();
	}
}
