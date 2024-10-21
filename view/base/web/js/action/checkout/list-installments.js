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
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'Magento_Catalog/js/price-utils',
    'mage/translate',
    'mage/url'
], function (
    _,
    $,
    quote,
    urlBuilder,
    customer,
    priceUtils,
    $t,
    urlFormatter
) {
    'use strict';

    return function (payload) {
        var deferred = $.Deferred(),
            serviceUrl,
            options,
            listFormated = [{
                'installment_value': null,
                'installment_label': $t('Enter card number...')
            }],
            label,
            supplementaryText,
            quoteId = quote.getQuoteId(),
            creditCardBin = payload.creditCardBin.credit_card_bin;

        serviceUrl = urlBuilder.createUrl('/carts/mine/pagbank-list-installments', {});

        if (!customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/pagbank-list-installments', {
                cartId: quoteId
            });
        }

        if (creditCardBin && creditCardBin.length === 6) {
            try {
                $.ajax({
                    url: urlFormatter.build(serviceUrl),
                    global: false,
                    data: JSON.stringify(payload),
                    contentType: 'application/json',
                    type: 'POST',
                    async: true
                }).done(
                    (response) => {
                        options = response;

                        listFormated = _.map(options, (value) => {
                            supplementaryText = $t('in total of %1')
                                .replace('%1', priceUtils.formatPrice(
                                    value.amount.value / 100, quote.getPriceFormat()
                                ));

                            if (value.interest_free) {
                                supplementaryText = $t('not interest');
                            }

                            label = $t('%1x of %2 %3')
                                .replace('%1', value.installments)
                                .replace('%2', priceUtils.formatPrice(
                                    value.installment_value / 100, quote.getPriceFormat()
                                ))
                                .replace('%3', supplementaryText);

                            return {
                                'installment_value': value.installments,
                                'installment_label': label
                            };
                        });

                        deferred.resolve(listFormated);
                    }
                ).fail(() => {
                    deferred.reject(new Error('Error when generating installments.'));
                });
            } catch (exc) {
                deferred.reject(new Error('Error when generating installments.'));
            }
        } else {
            deferred.resolve(listFormated);
        }

        return deferred.promise();
    };
});
