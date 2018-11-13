<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/** @var  \PatchTester\View\Pulls\PullsHtmlView  $this */

\JHtml::_('behavior.core');
\JHtml::_('bootstrap.tooltip');
\JHtml::_('stylesheet', 'com_patchtester/octicons.css', array('version' => 'auto', 'relative' => true));
\JHtml::_('script', 'com_patchtester/patchtester.js', array('version' => 'auto', 'relative' => true));

$listOrder     = $this->escape($this->state->get('list.ordering'));
$listDirn      = $this->escape($this->state->get('list.direction'));
$filterApplied = $this->escape($this->state->get('filter.applied'));
$filterBranch  = $this->escape($this->state->get('filter.branch'));
$filterRtc     = $this->escape($this->state->get('filter.rtc'));
$colSpan       = $this->trackerAlias !== false ? 8 : 7;

Factory::getDocument()->addStyleDeclaration(
	'
	.icon-48-patchtester { background-image:url("/media/com_patchtester/images/icon-48-patchtester.png"); }
	'
);
echo \JHtml::_(
	'bootstrap.renderModal',
	'modal-refresh',
	array(
		'url' => Uri::root() . 'administrator/index.php?option=com_patchtester&view=fetch&tmpl=component',
		'title' => \JText::_('COM_PATCHTESTER_TOOLBAR_FETCH_DATA'),
		'width' => '800px',
		'height' => '300px'
	)
);
?>
<form action="<?php echo \JRoute::_('index.php?option=com_patchtester&view=pulls'); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-main-container">
		<fieldset id="filter-bar">
			<legend class="element-invisible"><?php echo \JText::_('JSEARCH_FILTER_LABEL'); ?></legend>
			<div class="filter-search">
				<label class="filter-search-lbl" for="filter_search"><?php echo \JText::_('JSEARCH_FILTER_LABEL'); ?></label>
				<input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo \JText::_('COM_PATCHTESTER_FILTER_SEARCH_DESCRIPTION'); ?>" />
				<button type="submit" class="btn"><?php echo \JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
				<button type="button" onclick="document.getElementById('filter_search').value='';this.form.submit();"><?php echo \JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
			</div>
			<div class="filter-select">
				<label class="selectlabel" for="filter_applied"><?php echo \JText::_('COM_PATCHTESTER_FILTER_APPLIED_PATCHES'); ?></label>
				<select name="filter_applied" id="filter_applied">
					<option value=""><?php echo \JText::_('COM_PATCHTESTER_FILTER_APPLIED_PATCHES'); ?></option>
					<option value="yes"<?php if ($filterApplied == 'yes') echo ' selected="selected"'; ?>><?php echo \JText::_('COM_PATCHTESTER_APPLIED'); ?></option>
					<option value="no"<?php if ($filterApplied == 'no') echo ' selected="selected"'; ?>><?php echo \JText::_('COM_PATCHTESTER_NOT_APPLIED'); ?></option>
				</select>
				<label class="selectlabel" for="filter_rtc"><?php echo \JText::_('COM_PATCHTESTER_FILTER_RTC_PATCHES'); ?></label>
				<select name="filter_rtc" id="filter_rtc">
					<option value=""><?php echo \JText::_('COM_PATCHTESTER_FILTER_RTC_PATCHES'); ?></option>
					<option value="yes"<?php if ($filterRtc == 'yes') echo ' selected="selected"'; ?>><?php echo \JText::_('COM_PATCHTESTER_RTC'); ?></option>
					<option value="no"<?php if ($filterRtc == 'no') echo ' selected="selected"'; ?>><?php echo \JText::_('COM_PATCHTESTER_NOT_RTC'); ?></option>
				</select>
				<label class="selectlabel" for="filter_branch"><?php echo \JText::_('COM_PATCHTESTER_FILTER_BRANCH'); ?></label>
				<select name="filter_branch" id="filter_branch">
					<option value=""><?php echo \JText::_('COM_PATCHTESTER_FILTER_BRANCH'); ?></option>
					<?php echo \JHtml::_('select.options', $this->branches, 'text', 'text', $filterBranch, false);?>
				</select>
				<button type="submit" id="filter-go"><?php echo \JText::_('JSUBMIT'); ?></button>
			</div>
		</fieldset>
		<div class="clr"> </div>

		<table class="adminlist">
			<thead>
				<tr>
					<th width="5%" class="nowrap center">
						<?php echo \JHtml::_('grid.sort', 'COM_PATCHTESTER_PULL_ID', 'a.pull_id', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap">
						<?php echo \JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
					</th>
					<th width="8%" class="nowrap center">
						<?php echo \JText::_('COM_PATCHTESTER_BRANCH'); ?>
					</th>
					<th width="8%" class="nowrap center">
						<?php echo \JText::_('COM_PATCHTESTER_READY_TO_COMMIT'); ?>
					</th>
					<th width="8%" class="nowrap center">
						<?php echo \JText::_('COM_PATCHTESTER_GITHUB'); ?>
					</th>
					<?php if ($this->trackerAlias !== false) : ?>
					<th width="8%" class="nowrap center">
						<?php echo \JText::_('COM_PATCHTESTER_JISSUES'); ?>
					</th>
					<?php endif; ?>
					<th width="10%" class="nowrap center">
						<?php echo \JHtml::_('grid.sort', 'JSTATUS', 'applied', $listDirn, $listOrder); ?>
					</th>
					<th width="15%" class="nowrap center">
						<?php echo \JText::_('COM_PATCHTESTER_TEST_THIS_PATCH'); ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php echo $this->loadTemplate('items'); ?>
			</tbody>
		</table>
		<?php echo $this->pagination->getListFooter(); ?>

		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<input type="hidden" name="pull_id" id="pull_id" value="" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
		<?php echo \JHtml::_('form.token'); ?>
	</div>
</form>
