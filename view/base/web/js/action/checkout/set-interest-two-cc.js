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
         * @returns {Promise}
         */
        pagbankTwoCcInterest: function (selectInstallment, creditCardNumber, customAmount, cardIndex) {
            var serviceUrl,
                quoteId = quote.getQuoteId(),
                requestData;

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

            // Add optional parameters for two cc
            if (customAmount !== undefined && customAmount !== null) {
                requestData.customAmount = {
                    custom_amount: customAmount
                };
            }

            if (cardIndex !== undefined && cardIndex !== null) {
                requestData.cardIndex = {
                    card_index: cardIndex
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
