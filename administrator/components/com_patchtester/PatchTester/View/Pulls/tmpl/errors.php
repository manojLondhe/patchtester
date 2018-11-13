<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

/** @var  \PatchTester\View\Pulls\PullsHtmlView  $this */
?>
<h3><?php echo \JText::_('COM_PATCHTESTER_REQUIREMENTS_HEADING'); ?></h3>
<p><?php echo \JText::_('COM_PATCHTESTER_REQUIREMENTS_NOT_MET'); ?></p>
<ul>
<?php foreach ($this->envErrors as $error) : ?>
	<li><?php echo $error; ?></li>
<?php endforeach; ?>
</ul>
