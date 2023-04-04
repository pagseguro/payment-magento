/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define([
    'underscore',
    'jquery',
    'ko',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'PagBank_PaymentMagento/js/action/checkout/list-installments',
    'PagBank_PaymentMagento/js/action/checkout/set-interest',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'Magento_Checkout/js/model/quote',
    'mage/translate'
], function (
    _,
    $,
    _ko,
    VaultComponent,
    ListInstallments,
    setInterest,
    creditCardData,
    quote,
    $t
) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            active: false,
            template: 'PagBank_PaymentMagento/payment/vault',
            vaultForm: 'PagBank_PaymentMagento/payment/vault-form',
            creditCardInstallment: '',
            creditCardOptionsInstallments: ''
        },
        totals: quote.getTotals(),

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'active',
                'creditCardInstallment',
                'creditCardOptionsInstallments'
            ]);
            return this;
        },

        /**
         * Get auxiliary code
         * @returns {String}
         */
        getAuxiliaryCode() {
            return 'pagbank_paymentmagento_cc';
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'pagbank_paymentmagento_cc_vault';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this;

            self._super();

            self.active.subscribe((value) => {
                if (value === true) {
                    self.getListInstallments(self.getCardBin());
                }
            });

            self.creditCardInstallment.subscribe((value) => {
                self.addInterest();
                creditCardData.creditCardInstallment = value;
            });

            quote.totals.subscribe(() => {
                if (self.active() === true) {
                    self.getListInstallments(self.getCardBin());
                }
            });
        },

        /**
         * Is Active
         * @returns {Boolean}
         */
        isActive() {
            var active = this.getId() === this.isChecked();

            this.active(active);
            return active;
        },

        /**
         * Get List Installments
         *
         * @params {String} creditCardBin
         * @return {Void}
         */
        getListInstallments(creditCardBin) {
            var self = this,
                deferred = $.Deferred();

            ListInstallments(creditCardBin)
                .then((response) => {
                    self.creditCardOptionsInstallments(response);
                })
                .fail((error) => {
                    deferred.reject(error);
                });
        },

        /**
         * Add Interest in totals
         * @returns {void}
         */
        addInterest() {
            var self = this,
                selectInstallment = self.creditCardInstallment(),
                creditCardNumber = self.getCardBin();

            if (selectInstallment >= 0) {
                setInterest.pagbankInterest(selectInstallment, creditCardNumber);
            }
        },

        /**
         * Get Calculete instalments for vault
         * @returns {Array}
         */
        getOptionsInstallmentsVault() {
            var self = this,
                options = self.creditCardOptionsInstallments();

            if (options) {
                return _.map(options, (value) => {
                    return {
                        'installment_value': value.installment_value,
                        'installment_label': value.installment_label
                    };
                });
            }

            return {
                'installment_value': null,
                'installment_label': $t('Enter card number...')
            };
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
            var data = {
                'method': this.getCode(),
                'additional_data': {
                    'cc_installments': this.creditCardInstallment(),
                    'public_hash': this.getToken()
                }
            };

            return data;
        },

        /**
         * Is show legend
         * @returns {boolean}
         */
        isShowLegend() {
            return true;
        },

        /**
         * Get Token
         * @returns {string}
         */
        getToken() {
            return this.publicHash;
        },

        /**
         * Get masked card
         * @returns {string}
         */
        getMaskedCard() {
            return this.details['cc_last4'];
        },

        /**
         * Get expiration date
         * @returns {string}
         */
        getExpirationDate() {
            return this.details['cc_exp_month'] + '/' + this.details['cc_exp_year'];
        },

        /**
         * Get card type
         * @returns {string}
         */
        getCardType() {
            return this.details['cc_type'];
        },

        /**
         * Get card bin
         * @returns {string}
         */
        getCardBin() {
            return this.details['cc_bin'];
        },

        /**
         * Has verification
         * @returns {boolean}
         */
        hasVerification() {
            return window.checkoutConfig.payment[this.getCode()].useCvv;
        },

        /**
         * Get payment icons
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons(type) {
            return window.checkoutConfig.payment[this.getCode()].icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment[this.getCode()].icons[type]
                : false;
        }
    });
});
