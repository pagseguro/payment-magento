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
    'Magento_Checkout/js/view/payment/default',
    'PagBank_PaymentMagento/js/view/payment/payer-form',
    'PagBank_PaymentMagento/js/view/payment/base-data-for-payment-form'
], function (
    $,
    Component,
    PayerFormData,
    BaseDataForPaymentForm
) {
    'use strict';

    return Component.extend({
        defaults: {
            active: false,
            template: 'PagBank_PaymentMagento/payment/deep-link',
            deepLinkForm: 'PagBank_PaymentMagento/payment/deep-link-form',
            payerForm: 'PagBank_PaymentMagento/payment/payer-form'
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe(['active']);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'pagbank_paymentmagento_deep_link';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this;

            self._super();

            self.payerFormData = new PayerFormData();
            self.payerFormData.setPaymentCode(self.getCode());

            self.baseDataForPaymentForm = new BaseDataForPaymentForm();
            self.baseDataForPaymentForm.setPaymentCode(self.getCode());
        },

        /**
         * Is Active
         * @returns {Boolean}
         */
        isActive() {
            var active = this.getCode() === this.isChecked();

            this.active(active);
            return active;
        },

        /**
         * Init Form Element
         * @returns {void}
         */
        initFormElement(element) {
            this.formElement = element;
            $(this.formElement).validation();
        },

        /**
         * Before Place Order
         * @returns {void}
         */
        beforePlaceOrder() {
            if (!$(this.formElement).valid()) {
                return;
            }
            this.placeOrder();
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData() {
            return {
                method: this.getCode(),
                'additional_data': {
                    'payer_name': this.payerFormData.payerName(),
                    'payer_tax_id': this.payerFormData.payerTaxId(),
                    'payer_phone': this.payerFormData.payerPhone()
                }
            };
        }
    });
});
