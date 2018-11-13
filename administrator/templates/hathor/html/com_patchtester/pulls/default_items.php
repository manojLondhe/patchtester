<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

/** @var \PatchTester\View\DefaultHtmlView $this */

foreach ($this->items as $i => $item) :
	$status = '';

	if ($item->applied) :
		$status = ' success';
	endif;
?>
<tr class="row<?php echo $i % 2; ?><?php echo $status ?>">
	<td class="center">
		<?php echo $item->pull_id; ?>
	</td>
	<td>
		<p class="hasTooltip" title="<?php echo $this->escape($item->description); ?>">
			<?php echo $this->escape($item->title); ?>
		</p>
		<?php if ($item->applied) : ?>
			<p class="smallsub">
				<span class="label label-info"><?php echo \JText::sprintf('COM_PATCHTESTER_APPLIED_COMMIT_SHA', substr($item->sha, 0, 10)); ?></span>
			</p>
		<?php endif; ?>
	</td>
	<td class="center">
		<?php echo $this->escape($item->branch); ?>
	</td>
	<td class="center">
		<?php if ($item->is_rtc) : ?>
			<span class="label label-success"><?php echo \JText::_('JYES'); ?></span>
		<?php else : ?>
			<span class="label label-primary"><?php echo \JText::_('JNO'); ?></span>
		<?php endif; ?>
	</td>
	<td>
		<a class="btn btn-small btn-info" href="<?php echo $item->pull_url; ?>" target="_blank">
			<span class="octicon octicon-mark-github"></span> <?php echo \JText::_('COM_PATCHTESTER_GITHUB'); ?>
		</a>
	</td>
	<?php if ($this->trackerAlias !== false) : ?>
	<td>
		<a class="btn btn-small btn-warning" href="https://issues.joomla.org/tracker/<?php echo $this->trackerAlias; ?>/<?php echo $item->pull_id; ?>" target="_blank">
			<i class="icon-joomla"></i> <?php echo \JText::_('COM_PATCHTESTER_JISSUE'); ?>
		</a>
	</td>
	<?php endif; ?>
	<td class="center">
		<?php if ($item->applied) : ?>
			<span class="label label-success"><?php echo \JText::_('COM_PATCHTESTER_APPLIED'); ?></span>
		<?php else : ?>
			<span class="label"><?php echo \JText::_('COM_PATCHTESTER_NOT_APPLIED'); ?></span>
		<?php endif; ?>
	</td>
	<td class="center">
		<?php if ($item->applied) : ?>
			<a class="btn btn-small btn-success" href="javascript:PatchTester.submitpatch('revert', '<?php echo (int) $item->applied; ?>');"><?php echo \JText::_('COM_PATCHTESTER_REVERT_PATCH'); ?></a><br />
		<?php else : ?>
			<a class="btn btn-small btn-primary" href="javascript:PatchTester.submitpatch('apply', '<?php echo (int) $item->pull_id; ?>');"><?php echo \JText::_('COM_PATCHTESTER_APPLY_PATCH'); ?></a>
		<?php endif; ?>
	</td>
</tr>
<?php endforeach;
