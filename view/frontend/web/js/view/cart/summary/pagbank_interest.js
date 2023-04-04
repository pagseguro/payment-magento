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
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals',
        'mage/translate'
    ],
    function (Component, quote, priceUtils, totals, $t) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'PagBank_PaymentMagento/cart/summary/pagbank_interest',
                active: false
            },
            totals: quote.getTotals(),

            /**
             * Init observable variables
             *
             * @return {Object}
             */
            initObservable() {
                this._super().observe(['active']);
                return this;
            },

            /**
             * Is Active
             * @return {*|Boolean}
             */
            isActive() {
                return this.getPureValue() !== 0;
            },

            /**
             * Get Pure Value
             * @return {*}
             */
            getPureValue() {
                var pagBankInterest = 0;

                if (this.totals() && totals.getSegment('pagbank_interest_amount')) {
                    pagBankInterest = totals.getSegment('pagbank_interest_amount').value;
                    return pagBankInterest;
                }

                return pagBankInterest;
            },

            /**
             * Custon Title
             * @return {*|String}
             */
            customTitle() {
                if (this.getPureValue() > 0) {
                    return $t('Installments Interest');
                }
                return $t('Discount in cash');
            },

            /**
             * Get Value
             * @return {*|String}
             */
            getValue() {
                return this.getFormattedPrice(this.getPureValue());
            }
        });
    }
);
