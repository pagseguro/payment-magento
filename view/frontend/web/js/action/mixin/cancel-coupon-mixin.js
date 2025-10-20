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
    'mage/utils/wrapper',
    'Magento_Checkout/js/action/get-payment-information'
], function ($, wrapper, getPaymentInformation) {
    'use strict';

    return function (cancelCoupon) {
        return wrapper.wrap(cancelCoupon, function (originalAction) {
            return originalAction().done(function () {
                $(document).trigger('pagbank:refresh-installments');
                getPaymentInformation();
            });
        });
    };
});