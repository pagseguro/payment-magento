/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

/* @api */
define([
    'jquery',
    'Magento_Payment/js/model/credit-card-validation/cvv-validator',
    'PagBank_PaymentMagento/js/model/credit-card-validation/credit-card-number-validator',
    'PagBank_PaymentMagento/js/model/pagbank-cc-data',
    'mage/translate'
], function ($, cvvValidator, creditCardNumberValidator, creditCardData) {
    'use strict';

    var pagBankCreditCartTypes = {
        'VI': [new RegExp('^4[0-9]{12}([0-9]{3})?$'), new RegExp('^[0-9]{3}$'), true],
        'MC': [
            new RegExp('^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$'),
            new RegExp('^[0-9]{3}$'),
            true
        ],
        'AE': [new RegExp('^3[47][0-9]{13}$'), new RegExp('^[0-9]{4}$'), true],
        'DN': [new RegExp('^(3(0[0-5]|095|6|[8-9]))\\d*$'), new RegExp('^[0-9]{3}$'), true],
        'ELO': [new RegExp('^((451416)|(509091)|(636368)|(636297)|(504175)|(438935)|(40117[8-9])|(45763[1-2])|' +
            '(457393)|(431274)|(50990[0-2])|(5099[7-9][0-9])|(50996[4-9])|(509[1-8][0-9][0-9])|' +
            '(5090(0[0-2]|0[4-9]|1[2-9]|[24589][0-9]|3[1-9]|6[0-46-9]|7[0-24-9]))|' +
            '(5067(0[0-24-8]|1[0-24-9]|2[014-9]|3[0-379]|4[0-9]|5[0-3]|6[0-5]|7[0-8]))|' +
            '(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|' +
            '(6504(8[5-9]|9[0-9])|6505(0[0-9]|1[0-9]|2[0-9]|3[0-8]))|' +
            '(6505(4[1-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|' +
            '(6507(0[0-9]|1[0-8]))|(65072[0-7])|(6509(0[1-9]|1[0-9]|20))|' +
            '(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[0-9]))|' +
            '(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8])))\\d*$'), new RegExp('^[0-9]{3}$'), true],
        'HC': [new RegExp('^((606282|3841))\\d*$'), new RegExp('^[0-9]{3}$'), true],
        'AU': [new RegExp('^((5078))\\d*$'), new RegExp('^[0-9]{3}$'), true]
    };


    $.each({

        'validate-card-type-math-pagbank': [

            /**
             * Validate credit card number is for the correct credit card type.
             *
             * @param {String} value - credit card number
             * @param {*} element - element contains credit card number
             * @param {*} params - selector for credit card type
             * @return {Boolean}
             */
            (value, _element, params) => {
                var ccType;

                if (value && params) {
                    ccType = $(params).val();
                    value = value.replace(/\s/g, '').replace(/\-/g, '');
                    if (pagBankCreditCartTypes[ccType] && pagBankCreditCartTypes[ccType][0]) {
                        return pagBankCreditCartTypes[ccType][0].test(value);
                    } else if (pagBankCreditCartTypes[ccType] && !pagBankCreditCartTypes[ccType][0]) {
                        return true;
                    }
                }

                return false;
            },
            $.mage.__('This type of card is not accepted.')
        ],
        'validate-card-type-pagbank': [

            /**
             * Validate credit type pagbank is for the correct credit card type.
             *
             * @param {String} number - credit card number
             * @param {*} item - element contains credit card number
             * @param {*} allowedTypes - selector for credit card type
             * @return {Boolean}
             */
            (number, _item, allowedTypes) => {
                var cardInfo,
                    i,
                    l;

                if (!creditCardNumberValidator(number).isValid) {
                    return false;
                }

                cardInfo = creditCardNumberValidator(number).card;

                for (i = 0, l = allowedTypes.length; i < l; i++) {
                    if (cardInfo.title == allowedTypes[i].type) { //eslint-disable-line eqeqeq
                        return true;
                    }
                }

                return false;
            },
            $.mage.__('Please enter a valid credit card type number.')
        ],
        'validate-card-number-pagbank': [

            /**
             * Validate credit card number based on mod 10
             *
             * @param {*} number - credit card number
             * @return {Boolean}
             */
            (number) => {
                return creditCardNumberValidator(number).isValid;
            },
            $.mage.__('Please enter a valid credit card number.')
        ],
        'validate-card-cvv-pagbank': [

            /**
             * Validate cvv
             *
             * @param {String} cvv - card verification value
             * @return {Boolean}
             */
            (cvv) => {
                var maxLength = creditCardData.creditCard ? creditCardData.creditCard.code.size : 3;

                return cvvValidator(cvv, maxLength).isValid;
            },
            $.mage.__('Please enter a valid credit card verification number.')
        ]

    }, function (i, rule) {
        rule.unshift(i);
        $.validator.addMethod.apply($.validator, rule);
    });
});
