/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define([
    'pagBankCardJs',
    'underscore',
    'jquery',
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'PagBank_PaymentMagento/js/view/payment/before-pagbank-place-order-two-cc',
    'PagBank_PaymentMagento/js/view/payment/payer-form-for-two-cc',
    'PagBank_PaymentMagento/js/view/payment/base-data-for-payment-form',
    'PagBank_PaymentMagento/js/view/payment/two-cc-form',
    'Magento_SalesRule/js/action/set-coupon-code',
    'Magento_SalesRule/js/action/cancel-coupon'
], function (
    _pagBankCardJs,
    _,
    $,
    ko,
    Component,
    quote,
    priceUtils,
    PagBankPlaceOrder,
    PayerFormData,
    BaseDataForPaymentForm,
    TwoCcForm,
    setCouponCodeAction,
    cancelCouponAction
) {
    'use strict';

    setCouponCodeAction.registerSuccessCallback(function () {
        $(document).trigger('pagbank:refresh-installments');
    });

    cancelCouponAction.registerSuccessCallback(function () {
        $(document).trigger('pagbank:refresh-installments');
    });

    return Component.extend({
        defaults: {
            active: false,
            template: 'PagBank_PaymentMagento/payment/two-cc',
            firstCard: null,
            secondCard: null,
            countTryPlaceOrder: 0,
            isProcessing: false,
            paymentDivisionValue: 50,
            minScale: 10,
            maxScale: 90,
            grandTotal: 0,
            currentStep: 1
        },

        /**
         * Initializes model instance
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'active',
                'firstCard',
                'secondCard',
                'firstCreditCardNumberToken',
                'secondCreditCardNumberToken',
                'paymentDivisionValue',
                'minScale',
                'maxScale',
                'currentStep',
                'countTryPlaceOrder',
                'grandTotal'  // Adiciona grandTotal como observable
            ]);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'pagbank_paymentmagento_two_cc';
        },

        /**
         * Step navigation functions
         */
        goToStep1() {
            this.paymentDivisionValue(50);  // Correto: usa () para setar observable
            this.currentStep(1);             // Correto: usa () para setar observable
        },

        goToStep2() {
            var self = this;
            if (self.paymentDivisionValue() < self.minScale() || self.paymentDivisionValue() > self.maxScale()) {
                return false;
            }
            self.currentStep(2);
        },

        goToStep3() {
            var self = this;
            if (self.validateFirstCard() === false) {
                return false;
            }
            self.currentStep(3);
        },

        /**
         * Validate first card form
         * @returns {Boolean}
         */
        validateFirstCard() {
            var self = this,
                formSelector = '#pagbank-form-two-cc',
                form = $(formSelector);

            // Initialize validation if not already done
            if (!form.data('validator')) {
                form.validation();
            }
            
            // Validate the form
            return form.validation('isValid');
        },

        /**
         * Tokenize first card and continue to step 2
         */
        tokenizeFirstCardAndContinue() {
            var self = this;

            // Validate payment division
            if (self.paymentDivisionValue() < self.minScale() || self.paymentDivisionValue() > self.maxScale()) {
                alert($t('Please select a valid payment division amount'));
                return false;
            }

            // Validate first card form
            if (!self.validateFirstCard()) {
                return false;
            }

            self.isProcessing = true;

            // Use the before-pagbank-place-order-two-cc component
            self.pagBankPlaceOrder.tokenizeFirstCard(
                self,
                function() {
                    // Success callback
                    self.isProcessing = false;
                    self.goToStep3();
                },
                function() {
                    // Error callback
                    self.isProcessing = false;
                    self.firstCreditCardNumberToken(null);
                }
            );
        },

        /**
         * Validate second card form
         * @returns {Boolean}
         */
        validateSecondCard() {
            var self = this,
                formSelector = '#payment_form_second_cc_' + self.getCode(),
                form = $(formSelector);
            
            // Initialize validation if not already done
            if (!form.data('validator')) {
                form.validation();
            }
            
            // Validate the form
            return form.validation('isValid');
        },

        /**
         * Validate both cards
         * @returns {Boolean}
         */
        validateBothCards() {
            var self = this;

            return self.validateSecondCard();
        },

        /**
         * Init component
         * @returns {Void}
         */
        initialize() {
            var self = this;

            this._super();

            // Set initial grand total como observable
            self.grandTotal(self.getTotalWithoutInterest());

            // Initialize first card form
            self.firstCard = ko.observable(new TwoCcForm({
                parentMethod: self,
                cardIdentifier: 'first',
                isFirstCard: true
            }));

            // Initialize second card form
            self.secondCard = ko.observable(new TwoCcForm({
                parentMethod: self,
                cardIdentifier: 'second',
                isFirstCard: false
            }));

            self.payerFormData = new PayerFormData();
            self.payerFormData.setPaymentCode(self.getCode());

            self.pagBankPlaceOrder = new PagBankPlaceOrder();

            self.baseDataForPaymentForm = new BaseDataForPaymentForm();
            self.baseDataForPaymentForm.setPaymentCode(self.getCode());

            // Listener para refresh quando cupom é aplicado/removido
            $(document).on('pagbank:refresh-installments', function() {
                var newTotal = self.getTotalWithoutInterest();
                self.grandTotal(newTotal);
                self.paymentDivisionValue(50);
                self.goToStep1();
                
                if (self.firstCard()) {
                    self.firstCard().creditCardInstallment(null);
                    if (self.firstCard().creditCardNumber()) {
                        self.firstCard().creditCardInstallment(0);
                        self.firstCard().getListInstallments(self.firstCard().creditCardNumber());
                    }
                }
                
                if (self.secondCard()) {
                    self.secondCard().creditCardInstallment(null);
                    if (self.secondCard().creditCardNumber()) {
                        self.secondCard().creditCardInstallment(0);
                        self.secondCard().getListInstallments(self.secondCard().creditCardNumber());
                    }
                }
            });

            // quote.totals.subscribe(() => {
            //     var newTotal = self.getTotalWithoutInterest();
            //     self.grandTotal(newTotal);
            //     self.paymentDivisionValue(50);
            // });

            self.active.subscribe(() => {
                self.currentStep(1);
                self.firstCard().creditCardInstallment(null);
                self.secondCard().creditCardInstallment(null);
            });

            self.paymentDivisionValue.subscribe(() => {
                if (self.firstCard() && self.firstCard().creditCardNumber()) {
                    self.firstCard().creditCardInstallment(0);
                    self.firstCard().getListInstallments(self.firstCard().creditCardNumber());
                }
                if (self.secondCard() && self.secondCard().creditCardNumber()) {
                    self.secondCard().creditCardInstallment(0);
                    self.secondCard().getListInstallments(self.secondCard().creditCardNumber());
                }
            });

            self.currentStep.subscribe((step) => {
                if (step === 1) {
                    if (self.firstCard()) {
                        self.firstCard().creditCardInstallment(null);
                    }

                    if (self.secondCard()) {
                        self.secondCard().creditCardInstallment(null);
                    }
                }
            });
        },

        getTotalWithoutInterest() {
            var totals = quote.totals();
            var baseTotal = parseFloat(totals.base_grand_total || 0);
            var pagBankInterest = 0;
            
            if (totals.total_segments && totals.total_segments.length) {
                totals.total_segments.forEach(function(segment) {
                    if (segment.code === 'pagbank_interest_amount') {
                        pagBankInterest = parseFloat(segment.value || 0);
                    }
                });
            }
            
            return baseTotal - pagBankInterest;
        },

        /**
         * Get formatted minimum scale value
         * @returns {String}
         */
        getFormartMinScale() {
            return priceUtils.formatPrice(
                (this.grandTotal() * this.minScale()) / 100,
                quote.getPriceFormat()
            );
        },

        /**
         * Get formatted maximum scale value
         * @returns {String}
         */
        getFormartMaxScale() {
            return priceUtils.formatPrice(
                (this.grandTotal() * this.maxScale()) / 100,
                quote.getPriceFormat()
            );
        },

        /**
         * Get formatted first card amount
         * @returns {String}
         */
        getFormartFirstAmount() {
            return priceUtils.formatPrice(
                (this.grandTotal() * this.paymentDivisionValue()) / 100,
                quote.getPriceFormat()
            );
        },

        /**
         * Get formatted secondary card amount
         * @returns {String}
         */
        getFormartSecondaryAmount() {
            return priceUtils.formatPrice(
                (this.grandTotal() * (100 - this.paymentDivisionValue())) / 100,
                quote.getPriceFormat()
            );
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
         * Override placeOrder to handle errors
         * @param {*} data 
         * @param {*} event 
         */
        placeOrder: function(data, event) {
            var self = this;
            
            try {
                var result = this._super(data, event);

                if (result) {
                    self.goToStep1();
                }

                return result;
            } catch (error) {
                self.goToStep1();
                self.isProcessing = false;
                throw error;
            }
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

            if (!self.validateBothCards()) {
                return;
            }

            self.pagBankPlaceOrder.getPagBankPlaceTwoCards(self, () => {
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
            var self = this,
                firstCard = self.firstCard(),
                secondCard = self.secondCard(),
                firstPayerData = firstCard && firstCard.payerFormData() ? firstCard.payerFormData().getPayerData() : {},
                secondPayerData = secondCard && secondCard.payerFormData() ? secondCard.payerFormData().getPayerData() : {};
            
            var data = {
                'method': this.getCode(),
                'additional_data': {
                    // Division amounts - usa grandTotal() pois é observable
                    'first_card_amount': (this.grandTotal() * this.paymentDivisionValue()) / 100,
                    'second_card_amount': (this.grandTotal() * (100 - this.paymentDivisionValue())) / 100,
                    
                    // First card fields
                    'first_card_cc_number_token': self.firstCreditCardNumberToken(),
                    'first_card_cc_holder_name': firstCard ? firstCard.creditCardHolderName() : null,
                    'first_card_cc_installments': firstCard ? (firstCard.creditCardInstallment() || 1) : 1,
                    'first_card_cc_type': firstCard ? firstCard.creditCardType() : null,
                    'first_card_cc_exp_month': firstCard ? firstCard.creditCardExpMonth() : null,
                    'first_card_cc_exp_year': firstCard ? firstCard.creditCardExpYear() : null,
                    'first_card_type_transaction': firstCard ? firstCard.cardTypeTransaction() : null,
                    'first_card_three_ds_session': firstCard ? firstCard.threeDSecureSession() : null,
                    'first_card_three_ds_auth': firstCard ? firstCard.threeDSecureAuth() : null,
                    'first_card_three_ds_auth_status': firstCard ? firstCard.threeDSecureAuthStatus() : null,
                    'first_card_payer_tax_id': firstPayerData.payer_tax_id || null,
                    'first_card_payer_phone': firstPayerData.payer_phone || null,

                    // Second card fields
                    'second_card_cc_number_token': self.secondCreditCardNumberToken(),
                    'second_card_cc_holder_name': secondCard ? secondCard.creditCardHolderName() : null,
                    'second_card_cc_installments': secondCard ? (secondCard.creditCardInstallment() || 1) : 1,
                    'second_card_cc_type': secondCard ? secondCard.creditCardType() : null,
                    'second_card_cc_exp_month': secondCard ? secondCard.creditCardExpMonth() : null,
                    'second_card_cc_exp_year': secondCard ? secondCard.creditCardExpYear() : null,
                    'second_card_type_transaction': secondCard ? secondCard.cardTypeTransaction() : null,
                    'second_card_three_ds_session': secondCard ? secondCard.threeDSecureSession() : null,
                    'second_card_three_ds_auth': secondCard ? secondCard.threeDSecureAuth() : null,
                    'second_card_three_ds_auth_status': secondCard ? secondCard.threeDSecureAuthStatus() : null,
                    'second_card_payer_tax_id': secondPayerData.payer_tax_id || null,
                    'second_card_payer_phone': secondPayerData.payer_phone || null
                }
            };

            return data;
        },

        /**
         * Has 3ds
         * @returns {Boolean|*}
         */
        isEnableDebit() {
            return window.checkoutConfig.payment[this.getCode()].threeDs.hasOwnProperty('enable_deb') ?
                window.checkoutConfig.payment[this.getCode()].threeDs.enable_deb : false;
        },

        /**
         * Get Max Try Place Order
         * @returns {Integer}
         */
        getMaxTryPlaceOrder() {
            return window.checkoutConfig.payment[this.getCode()].threeDs.hasOwnProperty('max_try_place') ?
                window.checkoutConfig.payment[this.getCode()].threeDs.max_try_place : 0;
        },

        /**
         * Is Applicable
         * @returns {Boolean|*}
         */
        isApplicable() {
            return window.checkoutConfig.payment[this.getCode()].threeDs.hasOwnProperty('applicable') ?
                window.checkoutConfig.payment[this.getCode()].threeDs.applicable : false;
        },

        /**
         * Is Active 3ds
         * @returns {Boolean|*}
         */
        isActiveThreeDs() {
            return window.checkoutConfig.payment[this.getCode()].threeDs.hasOwnProperty('enable') ?
                window.checkoutConfig.payment[this.getCode()].threeDs.enable : false;
        }
    });
});