/**
 * Copyright © O2TI. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

 define([
    'jquery',
    'clipboard',
    'jquery-ui-modules/widget'
], function ($, ClipboardJS) {
    'use strict';

    $.widget('mage.widgetClipboard', {
        /**
         * Create.
         *
         * @returns {void}
         */
        _create() {
            this._super();
            if (this.options.enabled) {
                this._copy();
            }
        },

        /**
         * Copy.
         *
         * @returns {void}
         */
        _copy() {
            var self = this,
                btn = $(self.element),
                clipboard = new ClipboardJS('#' + btn.prop('id'), {});

            clipboard.on('success', function (e) {
                $(self.element).text(self.options.copiedtext);
                setTimeout(() => {
                    $(self.element).text(self.options.textBtn);
                }, 300);
                e.clearSelection();
            });
        }
    });

    return $.mage.widgetClipboard;
});
