/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

if (typeof Joomla === 'undefined') {
    throw new Error('PatchTester JavaScript requires the Joomla core JavaScript API')
}

!function (Joomla, window, document) {
    'use strict';

    window.PatchTester = {
        /**
         * Re-order the pull request list table
         */
        orderTable: function () {
            var table = document.getElementById('sortTable'),
                direction = document.getElementById('directionTable'),
                order = table.options[table.selectedIndex].value,
                currentOrder = document.getElementById('adminForm').getAttribute('data-order').valueOf();

            if (order != currentOrder) {
                var dirn = 'asc';
            } else {
                var dirn = direction.options[direction.selectedIndex].value;
            }

            Joomla.tableOrdering(order, dirn, '');
        },

        /**
         * Process the patch action
         *
         * @param {String} task The task to perform
         * @param {Number} id   The item ID
         */
        submitpatch: function (task, id) {
            var idField = document.getElementById('pull_id');
            idField.value = id;

            Joomla.submitform(task);
        }
    };

    Joomla.submitbutton = function (task) {
        if (task != 'reset' || confirm(Joomla.JText._('COM_PATCHTESTER_CONFIRM_RESET', 'Resetting will attempt to revert all applied patches and removes all backed up files. This may result in a corrupted environment. Are you sure you want to continue?'))) {
            Joomla.submitform(task);
        }
    };
}(Joomla, window, document);
