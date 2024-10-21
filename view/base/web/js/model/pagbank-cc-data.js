/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

/* @api */
define([], function () {
    'use strict';

    return {
        creditCard: null,
        creditCardNumberToken: null,
        creditCardNumber: null,
        creditCardVerificationNumber: null,
        creditCardType: null,
        creditCardExpYear: null,
        creditCardExpMonth: null,
        creditCardHolderName: null,
        creditCardInstallment: null,
        selectedCardType: null,
        creditCardOptionsInstallments: null,
        cardTypeTransaction: null,
        threeDSecureSession: null,
        threeDSecureAuth: null,
        threeDSecureAuthStatus: null
    };
});
