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
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'PagBank_PaymentMagento/js/action/checkout/list-installments-two-cc',
    'PagBank_PaymentMagento/js/action/checkout/set-interest-two-cc',
    'PagBank_PaymentMagento/js/model/credit-card-validation/credit-card-number-validator',
    'PagBank_PaymentMagento/js/validation/custom-credit-card-validation',
    'PagBank_PaymentMagento/js/view/payment/lib/jquery/jquery.mask',
    'PagBank_PaymentMagento/js/view/payment/payer-form-for-two-cc',
    'mage/translate'
], function (
    _,
    $,
    ko,
    Component,
    quote,
    ListInstallmentsTwoCc,
    setInterestTwoCc,
    cardNumberValidator,
    _custom,
    _mask,
    TwoCcPayerForm,
    $t
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PagBank_PaymentMagento/payment/two-cc-form',
            parentMethod: null,
            cardIdentifier: '',
            isFirstCard: true,
            payerFormData: null,
            creditCardNumber: '',
            creditCardVerificationNumber: '',
            creditCardType: '',
            creditCardExpYear: '',
            creditCardExpMonth: '',
            creditCardHolderName: '',
            creditCardInstallment: '',
            selectedCardType: '',
            creditCardOptionsInstallments: null,
            cardTypeTransaction: 'CREDIT_CARD',
            threeDSecureSession: '',
            threeDSecureAuth: '',
            threeDSecureAuthStatus: ''
        },

        /** @inheritdoc */
        initObservable() {
            this._super()
                .observe([
                    'creditCardNumber',
                    'creditCardVerificationNumber',
                    'creditCardType',
                    'creditCardExpYear',
                    'creditCardExpMonth',
                    'creditCardHolderName',
                    'creditCardInstallment',
                    'creditCardNumberToken',
                    'selectedCardType',
                    'creditCardOptionsInstallments',
                    'cardTypeTransaction',
                    'threeDSecureSession',
                    'threeDSecureAuth',
                    'threeDSecureAuthStatus',
                    'countTryPlaceOrder',
                    'payerFormData'
                ]);

            return this;
        },

        /**
         * Init component
         */
        initialize() {
            var self = this;

            self._super();

            self.creditCardInstallment(null);

            // Initialize payer form data for this card
            self.payerFormData = ko.observable(new TwoCcPayerForm({
                paymentCode: self.getCode(),
                parentCard: self,
                cardIdentifier: self.cardIdentifier
            }));

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
                }
                if (result.isValid) {
                    self.creditCardType(result.card.type);
                    self.getListInstallments(value);
                }
            });

            self.creditCardInstallment.subscribe((value) => {
                self.addInterest();
            });

            self.selectedCardType.subscribe((value) => {
                var elementId = '#' + self.getInputId('number');
                $(elementId).unmask();
                $(elementId).mask('0000 0000 0000 0000 0000');
                if (value === 'DN') {
                    $(elementId).mask('0000 000000 00000');
                }
            });

            quote.totals.subscribe(() => {
                var number = self.creditCardNumber();
                if (number) {
                    self.getListInstallments(number);
                }
            });

            self.cardTypeTransaction.subscribe((value) => {
                if (value === 'DEBIT_CARD') {
                    self.creditCardInstallment(1);
                } else {
                    self.creditCardInstallment(0);
                }
            });
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'pagbank_paymentmagento_two_cc';
        },

        /**
         * Get unique input ID
         * @param {String} field
         * @returns {String}
         */
        getInputId(field) {
            return 'pagbank_two_cc_' + this.cardIdentifier + '_' + field;
        },

        /**
         * Get card title
         * @returns {String}
         */
        getCardTitle() {
            return this.isFirstCard ? $t('First Credit Card') : $t('Second Credit Card');
        },

        /**
         * Validate card
         * @returns {Boolean}
         */
        validate() {
            var formSelector = '#payment_form_' + this.cardIdentifier + '_cc';
            return $(formSelector).validation() && $(formSelector).validation('isValid');
        },

        /**
         * Get card data with payer information
         * @returns {Object}
         */
        getCardData() {
            var payerData = {};
            
            if (this.payerFormData() && this.payerFormData().getPayerData) {
                payerData = this.payerFormData().getPayerData();
            }
            
            return {
                'cc_number_token': this.creditCardNumberToken(),
                'cc_installments': this.creditCardInstallment() || 1,
                'cc_type': this.creditCardType(),
                'cc_exp_month': this.creditCardExpMonth(),
                'cc_exp_year': this.creditCardExpYear(),
                'cc_holder_name': this.creditCardHolderName(),
                'card_type_transaction': this.cardTypeTransaction(),
                'three_ds_session': this.threeDSecureSession(),
                'three_ds_auth': this.threeDSecureAuth(),
                'three_ds_auth_status': this.threeDSecureAuthStatus(),
                'payer_name': payerData.payer_name || '',
                'payer_tax_id': payerData.payer_tax_id || '',
                'payer_phone': payerData.payer_phone || ''
            };
        },

        /**
         * Get card amount based on division
         * @returns {Number}
         */
        getCardAmount() {
            var totals = quote.totals(),
                baseTotal = parseFloat(totals.base_grand_total || 0),
                pagBankInterest = 0,
                grandTotal;
            
            // Busca o segmento de juros do PagBank
            if (totals.total_segments && totals.total_segments.length) {
                totals.total_segments.forEach(function(segment) {
                    if (segment.code === 'pagbank_interest_amount') {
                        pagBankInterest = parseFloat(segment.value || 0);
                    }
                });
            }
            
            // Total sem juros
            grandTotal = baseTotal - pagBankInterest;
            
            if (this.parentMethod) {
                var divisionPercentage = this.parentMethod.paymentDivisionValue();
                
                return (grandTotal * divisionPercentage) / 100;
            }
            
            return grandTotal;
        },

        /**
         * Get List Installments with custom amount
         * @param {String} number
         * @return {Void}
         */
        getListInstallments(number) {
            var self = this,
                creditCardBin = number.replace(/\s/g, '').slice(0, 6),
                cardTypeTransaction = self.cardTypeTransaction(),
                deferred = $.Deferred(),
                customAmount = self.getCardAmount(),
                cardIndex = self.isFirstCard ? 1 : 2,
                requestData;

            // Verifica se este cartão está no step ativo
            if (self.parentMethod && self.parentMethod.currentStep) {
                var currentStep = self.parentMethod.currentStep();
                // Só processa se está no step correto
                if ((self.isFirstCard && currentStep !== 2) || (!self.isFirstCard && currentStep !== 3)) {
                    return deferred.reject('Card not in active step');
                }
            }

            // Verifica se tem dados válidos
            if (!creditCardBin || creditCardBin.length < 6) {
                return deferred.reject('Invalid card number');
            }

            requestData = {
                'creditCardBin': {
                    'credit_card_bin': creditCardBin
                },
                'cardTypeTransaction': {
                    'card_type_transaction': cardTypeTransaction
                },
                'customAmount': customAmount,
                'cardIndex': cardIndex
            };

            ListInstallmentsTwoCc(requestData)
                .then((response) => {
                    self.creditCardOptionsInstallments(response);
                    deferred.resolve(response);
                })
                .fail((error) => {
                    deferred.reject(error);
                });
            
            return deferred.promise();
        },

        /**
         * Add Interest in totals
         * @returns {void}
         */
        addInterest() {
            var self = this,
                selectInstallment = self.creditCardInstallment(),
                creditCardNumber = self.creditCardNumber().replace(/\s/g, '').slice(0, 6),
                customAmount = self.getCardAmount(),
                cardIndex = self.isFirstCard ? 1 : 2,
                creditCardNumberCard1 = null,
                selectInstallmentCard1 = null;

            if (self.parentMethod && self.parentMethod.currentStep) {
                var currentStep = self.parentMethod.currentStep();
                if ((self.isFirstCard && currentStep !== 2) || (!self.isFirstCard && currentStep !== 3)) {
                    return;
                }
            }

            if (!creditCardNumber || creditCardNumber.length < 6) {
                return;
            }

            if (!self.isFirstCard && self.parentMethod && self.parentMethod.firstCard) {
                var firstCard = self.parentMethod.firstCard();
                if (firstCard) {
                    var firstCardNumber = firstCard.creditCardNumber();
                    if (firstCardNumber) {
                        creditCardNumberCard1 = firstCardNumber.replace(/\s/g, '').slice(0, 6);
                    }
                    selectInstallmentCard1 = firstCard.creditCardInstallment();
                }
            }

            setInterestTwoCc.pagbankTwoCcInterest(
                selectInstallment,
                creditCardNumber,
                customAmount,
                cardIndex,
                creditCardNumberCard1,
                selectInstallmentCard1
            );
        },

        /**
         * Get Calculate installments
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

            return [{
                'installment_value': null,
                'installment_label': $t('Enter card number...')
            }];
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
         * @returns {Boolean|Object}
         */
        getIcons(type) {
            return window.checkoutConfig.payment.pagbank_paymentmagento_two_cc.icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment.pagbank_paymentmagento_two_cc.icons[type] : false;
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
         * Is debit enabled
         * @returns {Boolean}
         */
        isEnableDebit() {
            return this.parentMethod && this.parentMethod.isEnableDebit();
        },

        /**
         * Get card type values
         * @returns {Array}
         */
        getCardTypeTransactionValues() {
            return [
                {
                    'value': 'CREDIT_CARD',
                    'label': $t('Credit')
                },
                {
                    'value': 'DEBIT_CARD',
                    'label': $t('Debit')
                }
            ];
        },

        /**
         * Get list of available credit card types values
         * @returns {Array}
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
         * @returns {Array}
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
         * @returns {Array}
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
        }
    });
});