/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

/* @api */
define([
    'jquery',
    'uiElement',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/customer-data',
    'PagBank_PaymentMagento/js/view/payment/lib/jquery/jquery.mask',
    'mage/translate'
], function (
    $,
    Component,
    quote,
    customerData
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PagBank_PaymentMagento/payment/payer-form-for-two-cc',
            payerTaxId: null,
            payerPhone: null,
            payerName: null,
            parentCard: null,
            cardIdentifier: ''
        },

        /**
         * Init Observable
         * @returns {Object}
         */
        initObservable() {
            this._super()
                .observe([
                    'payerTaxId',
                    'payerPhone',
                    'payerName'
                ]);

            return this;
        },

        /**
         * Init component
         * @returns {Void}
         */
        initialize() {
            var self = this,
                typeMaskVat,
                fieldVatId,
                fieldPhone;

            self._super();

            self.payerTaxId.subscribe((value) => {
                fieldVatId = $('input[name="payment[' + self.cardIdentifier + '_payer_tax_id]"]');
                fieldVatId.unmask();
                typeMaskVat = value.replace(/\D/g, '').length >= 12 ? '00.000.000/0000-00' : '000.000.000-009';
                fieldVatId.mask(typeMaskVat, { clearIfNotMatch: true });
            });

            self.payerPhone.subscribe((value) => {
                fieldPhone = $('input[name="payment[' + self.cardIdentifier + '_payer_phone]"]');
                fieldPhone.mask('(00)00000-0000', { clearIfNotMatch: true });
            });

            if (self.getDefaultTaxid()) {
                self.payerTaxId(self.getDefaultTaxid());
            }

            if (self.getDefaultPayerPhone()) {
                self.payerPhone(self.getDefaultPayerPhone());
            }
        },

        /**
         * Set Payment Code
         * @param {String} paymentCode
         */
        setPaymentCode(paymentCode) {
            this.paymentCode = paymentCode;
        },

        /**
         * Get Payer Information Title
         * @returns {String}
         */
        getPayerInformationTitle: function() {
            var cardLabel = this.getCardIdentifier() == 'first' 
                ? $.mage.__('1º Card') 
                : $.mage.__('2º Card');

            return $.mage.__('Payer Information for %1').replace('%1', cardLabel);
        },

        /**
         * Get card identifier
         * @returns {String}
         */
        getCardIdentifier() {
            return this.cardIdentifier;
        },

        /**
         * Set Payment Code
         * @param {String} paymentCode
         */
        setPaymentCode(paymentCode) {
            this.paymentCode = paymentCode;
        },

        /**
         * Has Tax ID Capture
         * @returns {Boolean}
         */
        hasTaxIdCapture() {
            return window.checkoutConfig.payment[this.paymentCode].hasOwnProperty('tax_id_capture') ?
            window.checkoutConfig.payment[this.paymentCode].tax_id_capture
            : false;
        },

        /**
         * Has Phone Capture
         * @returns {Boolean}
         */
        hasPhoneCapture() {
            return window.checkoutConfig.payment[this.paymentCode].hasOwnProperty('phone_capture') ?
            window.checkoutConfig.payment[this.paymentCode].phone_capture
            : false;
        },

        /**
         * Has Name Capture
         * @returns {Boolean}
         */
        hasNameCapture() {
            return window.checkoutConfig.payment[this.paymentCode].hasOwnProperty('name_capture') ?
            window.checkoutConfig.payment[this.paymentCode].name_capture
            : false;
        },

        /**
         * Get Default Tax Id
         * @returns {String}
         */
        getDefaultTaxid() {
            var taxId = null,
                collectBy = window.checkoutConfig.payment['pagbank_paymentmagento'].tax_id_from,
                customer = customerData.get('customer');

            if (collectBy === 'address') {
                if (quote.billingAddress()) {
                    taxId = quote.billingAddress().vatId;
                }
            }

            if (collectBy === 'customer') {
                if (customer().taxvat) {
                    taxId = customer().taxvat;
                }
            }

            return taxId;
        },

        /**
         * Get Default Payer Phone
         * @returns {String}
         */
        getDefaultPayerPhone() {
            var payerPhone = null;

            if (quote.billingAddress()) {
                payerPhone = quote.billingAddress().telephone;
            }

            return payerPhone;
        },

        /**
         * Get payer data
         * @returns {Object}
         */
        getPayerData() {
            return {
                'payer_name': this.payerName(),
                'payer_tax_id': this.payerTaxId(),
                'payer_phone': this.payerPhone()
            };
        }
    });
});