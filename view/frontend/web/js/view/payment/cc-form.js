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
    'underscore',
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'PagBank_PaymentMagento/js/model/pagbank-cc-data',
    'PagBank_PaymentMagento/js/action/checkout/list-installments',
    'PagBank_PaymentMagento/js/action/checkout/set-interest',
    'PagBank_PaymentMagento/js/model/credit-card-validation/credit-card-number-validator',
    'PagBank_PaymentMagento/js/validation/custom-credit-card-validation',
    'PagBank_PaymentMagento/js/view/payment/lib/jquery/jquery.mask',
    'mage/translate'
], function (
    _,
    $,
    Component,
    quote,
    creditCardData,
    ListInstallments,
    setInterest,
    cardNumberValidator,
    _custom,
    _mask,
    $t
) {
    'use strict';

    return Component.extend({
        defaults: {
            creditCardNumber: '',
            creditCardVerificationNumber: '',
            creditCardType: '',
            creditCardExpYear: '',
            creditCardExpMonth: '',
            creditCardHolderName: '',
            creditCardInstallment: '',
            selectedCardType: '',
            creditCardOptionsInstallments: null
        },
        totals: quote.getTotals(),

        /** @inheritdoc */
        initObservable() {
            this._super()
                .observe([
                    'creditCardNumberToken',
                    'creditCardNumber',
                    'creditCardVerificationNumber',
                    'creditCardType',
                    'creditCardExpYear',
                    'creditCardExpMonth',
                    'creditCardHolderName',
                    'creditCardInstallment',
                    'selectedCardType',
                    'creditCardOptionsInstallments',
                    'threeDSecureSession',
                    'threeDSecureAuth',
                    'threeDSecureAuthStatus'
                ]);

            return this;
        },

        /**
         * Init component
         */
        initialize() {
            var self = this,
                number;

            self._super();
            self.creditCardNumber.subscribe((value) => {
                var result;

                self.selectedCardType(null);
                if (value === '' || value === null) {
                    return false;
                }
                result = cardNumberValidator(value);
                if (!result.isPotentiallyValid && !result.isValid) {
                    return false;
                }
                if (result.card !== null) {
                    self.selectedCardType(result.card.type);
                    creditCardData.creditCard = result.card;
                }
                if (result.isValid) {
                    creditCardData.creditCardNumber = value;
                    self.creditCardType(result.card.type);
                    self.getListInstallments(value);
                }
            });
            self.creditCardInstallment.subscribe((value) => {
                self.addInterest();
                creditCardData.creditCardInstallment = value;
            });
            self.creditCardNumberToken.subscribe((value) => {
                creditCardData.creditCardNumberToken = value;
            });
            self.creditCardOptionsInstallments.subscribe((value) => {
                creditCardData.creditCardOptionsInstallments = value;
            });
            self.selectedCardType.subscribe((value) => {
                $('#pagbank_paymentmagento_cc_number').unmask();
                $('#pagbank_paymentmagento_cc_number').mask('0000 0000 0000 0000 0000');
                if (value === 'DN') {
                    $('#pagbank_paymentmagento_cc_number').mask('0000 000000 00000');
                }
                creditCardData.selectedCardType = value;
            });
            quote.totals.subscribe(() => {
                number = self.creditCardNumber();
                if (number) {
                    self.getListInstallments(number);
                }
            });
        },

        /**
         * Get List Installments
         *
         * @params {String} number
         * @return {Void}
         */
        getListInstallments(number) {
            var self = this,
                creditCardBin = number.replace(/\s/g,'').slice(0, 6),
                deferred = $.Deferred();

            ListInstallments(creditCardBin)
                .then((response) => {
                    self.creditCardOptionsInstallments(response);
                    deferred.resolve(response);
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
                creditCardNumber = self.creditCardNumber().replace(/\s/g,'').slice(0, 6);

            if (selectInstallment >= 0) {
                setInterest.pagbankInterest(selectInstallment, creditCardNumber);
            }
        },

        /**
         * Get Calculete instalments
         * @returns {Array}
         */
        getOptionsInstallments() {
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
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'cc';
        },

        /**
         * Get list of available credit card types
         * @returns {Object}
         */
        getCcAvailableTypes() {
            return window.checkoutConfig.payment.ccform.availableTypes[this.getCode()];
        },

        /**
         * Get payment icons
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons(type) {
            return window.checkoutConfig.payment.pagbank_paymentmagento_cc.icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment.pagbank_paymentmagento_cc.icons[type]
                : false;
        },

        /**
         * Get list of months
         * @returns {Object}
         */
        getCcMonths() {
            return window.checkoutConfig.payment.ccform.months[this.getCode()];
        },

        /**
         * Get list of years
         * @returns {Object}
         */
        getCcYears() {
            return window.checkoutConfig.payment.ccform.years[this.getCode()];
        },

        /**
         * Check if current payment has verification
         * @returns {Boolean}
         */
        hasVerification() {
            return window.checkoutConfig.payment.ccform.hasVerification[this.getCode()];
        },

        /**
         * Get list of available credit card types values
         * @returns {Object}
         */
        getCcAvailableTypesValues() {
            return _.map(this.getCcAvailableTypes(), (value, key) => {
                return {
                    'value': key,
                    'type': value
                };
            });
        },

        /**
         * Get list of available month values
         * @returns {Object}
         */
        getCcMonthsValues() {
            return _.map(this.getCcMonths(), (value, key) => {
                return {
                    'value': key,
                    'month': value
                };
            });
        },

        /**
         * Get list of available year values
         * @returns {Object}
         */
        getCcYearsValues() {
            return _.map(this.getCcYears(), (value, key) => {
                return {
                    'value': key,
                    'year': value
                };
            });
        },

        /**
         * Get available credit card type by code
         * @param {String} code
         * @returns {String}
         */
        getCcTypeTitleByCode(code) {
            var title = '',
                keyValue = 'value',
                keyType = 'type';

            _.each(this.getCcAvailableTypesValues(), (value) => {
                if (value[keyValue] === code) {
                    title = value[keyType];
                }
            });

            return title;
        },

        /**
         * Prepare credit card number to output
         * @param {String} number
         * @returns {String}
         */
        formatDisplayCcNumber(number) {
            return 'xxxx-' + number.substr(-4);
        },

        /**
         * Get credit card details
         * @returns {Array}
         */
        getInfo() {
            return [
                {
                    'name': 'Credit Card Type', value: this.getCcTypeTitleByCode(this.creditCardType())
                },
                {
                    'name': 'Credit Card Number', value: this.formatDisplayCcNumber(this.creditCardNumber())
                }
            ];
        }
    });
});
