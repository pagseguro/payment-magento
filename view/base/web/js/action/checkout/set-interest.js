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
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'mage/url'
], function (
    _,
    $,
    getTotalsAction,
    errorProcessor,
    quote,
    totals,
    urlBuilder,
    customer,
    urlFormatter
) {
        'use strict';

        return {

            /**
             * Add PagBank Interest in totals
             * @param {String} installmentSelected
             * @param {String} customerCreditCardBin
             * @returns {Void}
             */
            pagbankInterest(
                installmentSelected,
                customerCreditCardBin
            ) {
                var serviceUrl,
                    quoteId = quote.getQuoteId(),
                    payload = {
                        'creditCardBin': {
                          'credit_card_bin': customerCreditCardBin
                        },
                        'installmentSelected': {
                            'installment_selected': installmentSelected
                        }
                    };

                serviceUrl = urlBuilder.createUrl('/carts/mine/pagbank-interest/', {});

                if (!customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl(
                        '/guest-carts/:cartId/pagbank-interest/',
                        {
                            cartId: quoteId
                        }
                    );
                }

                $.ajax({
                    url: urlFormatter.build(serviceUrl),
                    global: true,
                    data: JSON.stringify(payload),
                    contentType: 'application/json',
                    type: 'POST',
                    async: true
                }).done(
                    () => {
                        var deferred = $.Deferred();

                        getTotalsAction([], deferred);
                    }
                ).fail(
                    (response) => {
                        errorProcessor.process(response);
                    }
                );
            }
        };
});
