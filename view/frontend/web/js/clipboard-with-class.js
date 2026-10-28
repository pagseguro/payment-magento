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

    $.widget('mage.clipboardWithClass', {
        options: {
            enabled: true,
            copiedtext: 'Copied',
            textBtn: 'Copy',
            copiedClass: 'copied',
            copiedClassDuration: 2000
        },

        /**
         * Create widget
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
         * Copy functionality with class toggle
         *
         * @returns {void}
         */
        _copy() {
            var self = this,
                btn = $(self.element),
                clipboard = new ClipboardJS('#' + btn.prop('id'), {});

            clipboard.on('success', function (e) {
                // Adiciona a classe 'copied'
                btn.addClass(self.options.copiedClass);
                
                // Altera o texto do botão
                btn.text(self.options.copiedtext);
                
                // Remove a classe e restaura o texto após o tempo configurado
                setTimeout(() => {
                    btn.removeClass(self.options.copiedClass);
                    btn.text(self.options.textBtn);
                }, self.options.copiedClassDuration);
                
                e.clearSelection();
            });
        }
    });

    return $.mage.clipboardWithClass;
});
