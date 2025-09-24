/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        var config = window.checkoutConfig.payment,
            methodBoleto = 'pagbank_paymentmagento_boleto',
            methodCc = 'pagbank_paymentmagento_cc',
            methodTwoCc = 'pagbank_paymentmagento_two_cc',
            methodPix = 'pagbank_paymentmagento_pix',
            methodDeepLink = 'pagbank_paymentmagento_deep_link';

        if (config[methodBoleto].isActive) {
            rendererList.push(
                {
                    type: methodBoleto,
                    component: 'PagBank_PaymentMagento/js/view/payment/method-renderer/boleto'
                }
            );
        }

        if (config[methodCc].isActive) {
            rendererList.push(
                {
                    type: methodCc,
                    component: 'PagBank_PaymentMagento/js/view/payment/method-renderer/cc'
                }
            );
        }

        if (config[methodTwoCc].isActive) {
            rendererList.push(
                {
                    type: methodTwoCc,
                    component: 'PagBank_PaymentMagento/js/view/payment/method-renderer/two-cc'
                }
            );
        }

        if (config[methodPix].isActive) {
            rendererList.push(
                {
                    type: methodPix,
                    component: 'PagBank_PaymentMagento/js/view/payment/method-renderer/pix'
                }
            );
        }

        if (config[methodDeepLink].isActive) {
            rendererList.push(
                {
                    type: methodDeepLink,
                    component: 'PagBank_PaymentMagento/js/view/payment/method-renderer/deep-link'
                }
            );
        }

        return Component.extend({});
    }
);
