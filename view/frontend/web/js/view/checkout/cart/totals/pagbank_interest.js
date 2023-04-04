/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define(
    [
        'PagBank_PaymentMagento/js/view/checkout/cart/summary/pagbank_interest'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            /**
             * @override
             *
             * @returns {boolean}
             */
            isDisplayed() {
                return this.getPureValue() !== 0;
            }
        });
    }
);
