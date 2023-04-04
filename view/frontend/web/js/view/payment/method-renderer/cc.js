/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

 define([
    'pagBankCardJs',
    'underscore',
    'jquery',
    'PagBank_PaymentMagento/js/view/payment/cc-form',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Magento_Checkout/js/model/full-screen-loader',
    'PagBank_PaymentMagento/js/view/payment/payer-form',
    'PagBank_PaymentMagento/js/view/payment/base-data-for-payment-form'
], function (
    _pagBankCardJs,
    _,
    $,
    Component,
    VaultEnabler,
    fullScreenLoader,
    PayerFormData,
    BaseDataForPaymentForm
) {
    'use strict';

    return Component.extend({
        defaults: {
            active: false,
            template: 'PagBank_PaymentMagento/payment/cc',
            ccForm: 'PagBank_PaymentMagento/payment/cc-form',
            payerForm: 'PagBank_PaymentMagento/payment/payer-form',
            creditCardNumberToken: ''
        },

        /**
         * Initializes model instance
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'active'
            ]);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'pagbank_paymentmagento_cc';
        },

        /**
         * Init component
         * @returns {Void}
         */
        initialize() {
            var self = this;

            this._super();

            self.payerFormData = new PayerFormData();
            self.payerFormData.setPaymentCode(self.getCode());

            self.baseDataForPaymentForm = new BaseDataForPaymentForm();
            self.baseDataForPaymentForm.setPaymentCode(self.getCode());

            self.vaultEnabler = new VaultEnabler();
            self.vaultEnabler.setPaymentCode(self.getVaultCode());

            self.active.subscribe(() => {
                self.creditCardInstallment(0);
            });
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
         * @returns {Void}
         */
        initFormElement(element) {
            this.formElement = element;
            $(this.formElement).validation();
        },

        /**
         * Before Place Order
         * @returns {Void}
         */
        beforePlaceOrder() {
            if (!$(this.formElement).valid()) {
                return;
            }
            this.getTokenize();
        },

        /**
         * Get Tokenize
         * @returns {Void}
         */
        getTokenize() {
            var self = this,
                cardPs,
                cardTokenized,
                cardHasError,
                cardError,
                cardData = {
                    publicKey: self.baseDataForPaymentForm.getPublicKey(),
                    holder: self.creditCardHolderName(),
                    number: self.creditCardNumber().replace(/\s/g,''),
                    expMonth: self.creditCardExpMonth(),
                    expYear: self.creditCardExpYear(),
                    securityCode: self.creditCardVerificationNumber()
                };

            fullScreenLoader.startLoader();

            // eslint-disable-next-line no-undef
            cardPs = PagSeguro.encryptCard(cardData);
            cardTokenized = cardPs.encryptedCard;
            cardHasError = cardPs.hasErrors;
            if (cardHasError) {
                cardError = cardPs.errors;
                console.log(cardError);
                fullScreenLoader.stopLoader();
            }

            if (cardTokenized) {
                self.creditCardNumberToken(cardTokenized);
                fullScreenLoader.stopLoader();
                self.placeOrder();
            }

        },

        /**
         * Get data
         * @returns {Object}
         */
        getData() {
            var data = {
                'method': this.getCode(),
                'additional_data': {
                    'cc_number_token': this.creditCardNumberToken(),
                    'cc_installments': this.creditCardInstallment(),
                    'payer_tax_id': this.payerFormData.payerTaxId(),
                    'payer_phone': this.payerFormData.payerPhone()
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
            this.vaultEnabler.visitAdditionalData(data);
            return data;
        },

        /**
         * Tax ID capture
         * @returns {Boolean}
         */
        getVaultCode() {
            return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
        },

        /**
         * Is vault enabled
         * @returns {Boolean}
         */
        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        }
    });
});
