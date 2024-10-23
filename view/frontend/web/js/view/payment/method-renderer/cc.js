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
    'PagBank_PaymentMagento/js/view/payment/pagbank-place-order',
    'PagBank_PaymentMagento/js/view/payment/payer-form',
    'PagBank_PaymentMagento/js/view/payment/base-data-for-payment-form'
], function (
    _pagBankCardJs,
    _,
    $,
    Component,
    VaultEnabler,
    PagBankPlaceOrder,
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
            creditCardNumberToken: '',
            countTryPlaceOrder: 0,
            threeDSecureSession: '',
            threeDSecureAuth: '',
            threeDSecureAuthStatus: '',
            isProcessing: false
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

            self.pagBankPlaceOrder = new PagBankPlaceOrder();

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
            var self = this;

            if (!$(self.formElement).valid()) {
                return;
            }

            // place order on success validation
            self.pagBankPlaceOrder.getPagBankPlace(self, () => {
                self.placeOrder('parent');
            }, () => {
                self.isProcessing = false;
            });
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
                    'cc_installments': this.creditCardInstallment() ? this.creditCardInstallment() : 1,
                    'card_type_transaction': this.cardTypeTransaction(),
                    'three_ds_session': this.threeDSecureSession(),
                    'three_ds_auth': this.threeDSecureAuth(),
                    'three_ds_auth_status': this.threeDSecureAuthStatus(),
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
        isVaultEnabled() {
            return this.vaultEnabler.isVaultEnabled();
        },

        /**
         * Has 3ds
         * @returns {Boolean|*}
         */
        isEnableDebit() {
            return window.checkoutConfig.payment[this.getCode()].threeDs.hasOwnProperty('enable_deb') ?
            window.checkoutConfig.payment[this.getCode()].threeDs.enable_deb
            : false;
        },

        /**
         * Get Max Try Place Order
         * @returns {Interger}
         */
        getMaxTryPlaceOrder() {
            return window.checkoutConfig.payment[this.getCode()].threeDs.hasOwnProperty('max_try_place') ?
            window.checkoutConfig.payment[this.getCode()].threeDs.max_try_place
            : 0;
        },

        /**
         * Is Applicable
         * @returns {Boolean|*}
         */
        isApplicable() {
            return window.checkoutConfig.payment[this.getCode()].threeDs.hasOwnProperty('applicable') ?
            window.checkoutConfig.payment[this.getCode()].threeDs.applicable
            : false;
        },

        /**
         * Is Active 3ds
         * @returns {Boolean|*}
         */
        isActiveThreeDs() {
            return window.checkoutConfig.payment[this.getCode()].threeDs.hasOwnProperty('enable') ?
            window.checkoutConfig.payment[this.getCode()].threeDs.enable
            : false;
        }
    });
});
