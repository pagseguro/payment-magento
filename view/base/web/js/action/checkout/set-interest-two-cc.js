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
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/totals',
    'mage/url'
], function (
    $,
    quote,
    urlBuilder,
    customer,
    totals,
    urlFormatter
) {
    'use strict';

    return {
        /**
         * Set interest for two credit cards
         *
         * @param {Number} selectInstallment
         * @param {String} creditCardNumber
         * @param {Number} customAmount
         * @param {Number} cardIndex
         * @param {String} creditCardNumberCard1
         * @param {Number} selectInstallmentCard1
         * @returns {Promise}
         */
        pagbankTwoCcInterest: function (
            selectInstallment,
            creditCardNumber,
            customAmount,
            cardIndex,
            creditCardNumberCard1,
            selectInstallmentCard1
        ) {
            var serviceUrl,
                quoteId = quote.getQuoteId(),
                requestData,
                cardIndexInt = cardIndex !== undefined && cardIndex !== null
                    ? parseInt(cardIndex, 10)
                    : null;

            serviceUrl = urlBuilder.createUrl('/carts/mine/pagbank-interest', {});

            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/pagbank-interest', {
                    cartId: quoteId
                });
            }

            requestData = {
                creditCardBin: {
                    credit_card_bin: creditCardNumber
                },
                installmentSelected: {
                    installment_selected: selectInstallment
                }
            };

            if (customAmount !== undefined && customAmount !== null) {
                requestData.customAmount = {
                    custom_amount: customAmount
                };
            }

            if (cardIndexInt !== null) {
                requestData.cardIndex = {
                    card_index: cardIndexInt
                };
            }

            if (cardIndexInt === 2 && creditCardNumberCard1 !== undefined && creditCardNumberCard1 !== null) {
                requestData.creditCardBinCard1 = {
                    credit_card_bin: creditCardNumberCard1
                };
            }

            if (cardIndexInt === 2 && selectInstallmentCard1 !== undefined && selectInstallmentCard1 !== null) {
                requestData.installmentSelectedCard1 = {
                    installment_selected: selectInstallmentCard1
                };
            }

            return $.ajax({
                url: urlFormatter.build(serviceUrl),
                global: true,
                data: JSON.stringify(requestData),
                contentType: 'application/json',
                type: 'POST',
                async: true
            }).done(function (response) {
                if (response) {
                    totals.isLoading(true);
                    quote.setTotals(response);
                    totals.isLoading(false);
                }
            }).fail(function () {
                totals.isLoading(false);
            });
        }
    };
});