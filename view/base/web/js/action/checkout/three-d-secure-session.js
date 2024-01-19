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
    'Magento_Checkout/js/model/url-builder',
    'mage/url'
], function (
    _,
    $,
    urlBuilder,
    urlFormatter
) {
    'use strict';

    return function () {

        var deferred = $.Deferred(),
            serviceUrl;

        serviceUrl = urlBuilder.createUrl('/PagBankThreeDsSession', {});


        $.ajax({
            type: 'GET',
            url: urlFormatter.build(serviceUrl),
            contentType: 'application/json',

            /**
             * Resolve with session id if success, reject with response message otherwise
             *
             * @param {Object} response
             */
            success: function (response) {
                if (response.session_id) {
                    deferred.resolve(response);

                    return;
                }

                deferred.reject('Unable to proceed with your payment, please try again.');
            },

            /**
             * Extract the message and reject
             */
            error: function () {
                deferred.reject('Unable to proceed with your payment, please try again.');
            }
        });

        return deferred.promise();
    };
});
