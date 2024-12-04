/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';

    return function (config, element) {
        var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                modalClass: 'modal-sm pagbank-add-new-card',
                title: $.mage.__('Add New Card'),
                focus: '#card_number',
                buttons: []
            };

        modal(options, $('#pagbank-form-container'));

        $(element).click(function () {
            $('#pagbank-form-container').modal('openModal');
        });
    };
});
