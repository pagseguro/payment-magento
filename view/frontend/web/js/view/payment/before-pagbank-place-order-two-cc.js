/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define([
    'uiElement',
    'pagBankCardJs',
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm',
    'Magento_Checkout/js/model/full-screen-loader',
    'PagBank_PaymentMagento/js/action/checkout/three-d-secure-session',
    'PagBank_PaymentMagento/js/model/pagbank-pre-order-data'
], function (
    Component,
    _pagBankCardJs,
    $,
    $t,
    confirmation,
    fullScreenLoader,
    ThreeDS,
    PagBankPreOrderData
) {
    'use strict';

    return Component.extend({

        /**
         * Init component
         * @returns {Void}
         */
        initialize() {
            this._super();
        },

        /**
         * Tokenize first card for step navigation
         * @param {Object} context - Main payment context
         * @param {Function} successCallback
         * @param {Function} errorCallback
         */
        tokenizeFirstCard(context, successCallback, errorCallback) {
            let self = this,
                firstCard = context.firstCard(),
                countPlaceOrder = context.countTryPlaceOrder || 0,
                maxTry = context.getMaxTryPlaceOrder(),
                typePay = firstCard.cardTypeTransaction(),
                enable = context.isActiveThreeDs(),
                applicable = context.isApplicable(),
                forceThreeDs = false;

            fullScreenLoader.startLoader();

            // Always generate new token
            context.firstCreditCardNumberToken(null);

            // Tokenize first card
            let encrypted = self.getPagBankTokenizeForCard(firstCard, context.baseDataForPaymentForm);

            if (!encrypted) {
                fullScreenLoader.stopLoader();
                context.goToStep1();
                if (errorCallback) errorCallback();
                return;
            }

            // Set token for first card
            context.firstCreditCardNumberToken(encrypted);

            countPlaceOrder++;
            context.countTryPlaceOrder = countPlaceOrder;

            if (countPlaceOrder > maxTry) {
                forceThreeDs = enable ? true : false;
            }

            // Check if 3DS is needed for first card
            if (applicable || typePay === 'DEBIT_CARD' || forceThreeDs) {
                ThreeDS()
                    .then((session) => {
                        var sessionId = session.session_id,
                            cardPayData = {
                                type: firstCard.cardTypeTransaction(),
                                installments: firstCard.creditCardInstallment() || 1,
                                card: {
                                    number: firstCard.creditCardNumber().replace(/\s/g,''),
                                    expMonth: firstCard.creditCardExpMonth(),
                                    expYear: firstCard.creditCardExpYear(),
                                    holder: {
                                        name: firstCard.creditCardHolderName()
                                    }
                                }
                            },
                            data = PagBankPreOrderData.getPreOrderData(cardPayData);

                        return self.sendDataForThreeDS(sessionId, data, firstCard, context);
                    })
                    .then((result) => {
                        fullScreenLoader.stopLoader();
                        if (result && successCallback) {
                            successCallback();
                        } else {
                            context.goToStep1();
                            if (errorCallback) errorCallback();
                        }
                    })
                    .catch(() => {
                        fullScreenLoader.stopLoader();
                        context.goToStep1();
                        if (errorCallback) errorCallback();
                    });
            } else {
                fullScreenLoader.stopLoader();
                if (successCallback) successCallback();
            }
        },

        /**
         * Get PagBank Place Two Cards
         * @param {Object} context
         * @param {Function} callback
         * @param {Function} errorCallback
         * @returns {Void}
         */
        getPagBankPlaceTwoCards: function (context, callback, errorCallback) {
            let self = this,
                secondEncrypted,
                countPlaceOrder = context.countTryPlaceOrder || 0,
                maxTry = 3,
                enable = context.isActiveThreeDs(),
                applicable = context.isApplicable(),
                forceThreeDs = false;

            fullScreenLoader.startLoader();

            // Always tokenize second card
            secondEncrypted = self.getPagBankTokenizeForCard(context.secondCard(), context.baseDataForPaymentForm);
            if (!secondEncrypted) {
                fullScreenLoader.stopLoader();
                context.goToStep1();
                errorCallback();
                return;
            }
            context.secondCreditCardNumberToken(secondEncrypted);

            countPlaceOrder++;
            context.countTryPlaceOrder = countPlaceOrder;

            if (countPlaceOrder > maxTry) {
                forceThreeDs = enable ? true : false;
            }

            // Check if 3DS is needed for either card
            let secondCardNeedsThreeDs = context.secondCard().cardTypeTransaction() === 'DEBIT_CARD' || applicable || forceThreeDs;

            if (secondCardNeedsThreeDs) {
                self.handleThreeDsForTwoCards(context, callback, errorCallback);
            } else {
                fullScreenLoader.stopLoader();
                callback();
            }
        },

        /**
         * Get PagBank Tokenize for specific card
         * @param {Object} cardForm
         * @param {Object} baseData
         * @returns {String|Boolean}
         */
        getPagBankTokenizeForCard(cardForm, baseData) {
            let cardPs,
                cardTokenized,
                cardHasError,
                cardData = {
                    publicKey: baseData.getPublicKey(),
                    holder: cardForm.creditCardHolderName(),
                    number: cardForm.creditCardNumber().replace(/\s/g,''),
                    expMonth: cardForm.creditCardExpMonth(),
                    expYear: cardForm.creditCardExpYear(),
                    securityCode: cardForm.creditCardVerificationNumber()
                };

            // eslint-disable-next-line no-undef
            cardPs = PagSeguro.encryptCard(cardData);
            cardTokenized = cardPs.encryptedCard;
            cardHasError = cardPs.hasErrors;

            if (cardHasError) {
                this.showError(
                    $t('Unable to complete the payment with this card, please verify the information and try again.')
                );
                return false;
            }

            return cardTokenized;
        },

        /**
         * Handle 3DS for two cards
         * @param {Object} context
         * @param {Function} callback
         * @param {Function} errorCallback
         */
        handleThreeDsForTwoCards(context, callback, errorCallback) {
            let self = this;

            ThreeDS()
                .then((session) => {
                    var sessionId = session.session_id,
                        secondCardData = {
                            type: context.secondCard().cardTypeTransaction(),
                            installments: context.secondCard().creditCardInstallment() ? context.secondCard().creditCardInstallment() : 1,
                            card: {
                                number: context.secondCard().creditCardNumber().replace(/\s/g,''),
                                expMonth: context.secondCard().creditCardExpMonth(),
                                expYear: context.secondCard().creditCardExpYear(),
                                holder: {
                                    name: context.secondCard().creditCardHolderName()
                                }
                            }
                        },
                        data = PagBankPreOrderData.getPreOrderData(secondCardData);

                    return self.sendDataForThreeDS(sessionId, data, context.secondCard(), context);
                }).then((result) => {
                    fullScreenLoader.stopLoader();
                    if (result) {
                        callback();
                    } else {
                        context.goToStep1();
                        errorCallback();
                    }
                }).catch(() => {
                    fullScreenLoader.stopLoader();
                    context.goToStep1();
                    errorCallback();
                });
        },

        /**
         * Send Data for 3ds
         * @param {String} sessionId
         * @param {Object} data
         * @param {Object} cardContext
         * @param {Object} mainContext
         */
        sendDataForThreeDS(sessionId, data, cardContext, mainContext) {
            let deferred = $.Deferred();

            // eslint-disable-next-line no-undef
            PagSeguro.setUp({
                session: sessionId,
                env: cardContext.parentMethod ? 
                    cardContext.parentMethod.baseDataForPaymentForm.getEnv() : 
                    cardContext.baseDataForPaymentForm.getEnv()
            });

            // eslint-disable-next-line no-undef
            PagSeguro.authenticate3DS(data).then(result => {
                let authId = result.id,
                    authStatus = result.status,
                    authenticationStatus = result.authenticationStatus,
                    isComplet = this.completOrderAuth(cardContext, authStatus, authenticationStatus);

                this.setThreeDsData(cardContext, authId, authStatus, authenticationStatus);
                deferred.resolve(isComplet);

            }).catch((err) => {
                // eslint-disable-next-line no-undef
                if (err instanceof PagSeguro.PagSeguroError) {
                    if (mainContext && mainContext.goToStep1) {
                        mainContext.goToStep1();
                    }
                    this.showError(err.detail.message);
                    deferred.resolve(false);
                } else {
                    deferred.reject(err);
                }
            });

            return deferred.promise();
        },

        /**
         * Set 3ds Data
         * @param {Object} context
         * @param {String} authId
         * @param {String} authStatus
         * @param {String} authenticationStatus
         */
        setThreeDsData(context, authId, authStatus, authenticationStatus) {
            context.threeDSecureSession(authId);
            context.threeDSecureAuth(authStatus);
            context.threeDSecureAuthStatus(authenticationStatus);
        },

        /**
         * Show error message
         * @param {String} errorMessage
         */
        showError(errorMessage) {
            confirmation({
                title: $t('Error while processing Payment'),
                content: errorMessage,
                buttons: [{
                    text: $t('Ok'),
                    class: 'action-primary action-accept',
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }]
            });

            fullScreenLoader.stopLoader(true);
        },

        /**
         * Complet Order Auth
         * @param {Object} context
         * @param {String} authStatus
         * @param {String} authenticationStatus
         * @returns {Boolean}
         */
        completOrderAuth(context, authStatus, authenticationStatus) {
            let baseData = context.parentMethod ? 
                context.parentMethod.baseDataForPaymentForm : 
                context.baseDataForPaymentForm;
            let reject = baseData.hasThreeDsRejectNotAuth();

            if (authStatus === 'AUTH_NOT_SUPPORTED') {
                return true;
            }

            if (authStatus === 'CHANGE_PAYMENT_METHOD') {
                this.showError($t('Change Payment Method'));
                return false;
            }

            if (authStatus === 'AUTH_FLOW_COMPLETED' && reject) {
                if (authenticationStatus === 'NOT_AUTHENTICATED') {
                    this.showError($t('Change Payment Method'));
                    return false;
                }
                return authenticationStatus === 'AUTHENTICATED';
            }
            return true;
        }
    });
});
