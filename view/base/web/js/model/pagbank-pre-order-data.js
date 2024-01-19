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
    'underscore',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'Magento_Ui/js/modal/confirm',
    'Magento_Checkout/js/model/full-screen-loader'
], function (
    _,
    $,
    quote,
    $t,
    confirmation,
    fullScreenLoader
) {
    'use strict';

    return {

        /**
         * Challenge Instruction
         * @param {Object} challenge
         */
        challengeInstruction(challenge) {
            let instruction = window.checkoutConfig.payment['pagbank_paymentmagento_cc'].threeDs.instruction,
                ccBrand = challenge.brand.charAt(0).toUpperCase() + challenge.brand.slice(1),
                ccIssuer = challenge.issuer ? challenge.issuer.toUpperCase() : $t('card issuer'),
                content = instruction.replace('%1', ccBrand).replace('%2', ccIssuer);

            fullScreenLoader.stopLoader(true);
            confirmation({
                title: $t('Verification of data'),
                content: content,
                buttons: [{
                    text: $t('Confirm my data'),
                    class: 'action-primary action-accept'
                }],
                closed: () => {
                    fullScreenLoader.startLoader(true);
                    challenge.open();
                },
                confirm: () => {
                    fullScreenLoader.startLoader(true);
                    challenge.open();
                }
            });
        },

        /**
         * Get CountryIsoAlpha3
         * @param {String} countryIsoAlpha2
         * @returns {String|null}
         */
        getCountryIsoAlpha3(countryIsoAlpha2) {
            var isoAlpha3Map = {
                    'BR': 'BRA'
                };

            if (isoAlpha3Map.hasOwnProperty(countryIsoAlpha2)) {
                return isoAlpha3Map[countryIsoAlpha2];
            }
            return null;
        },

        /**
         * Format Phone Number
         * @param {String} phoneNumber
         * @param {String} country
         * @returns {Array}
         */
        formatPhoneNumber(
            phoneNumber,
            country
        ) {
            let countryNumber = country === 'BR' ? '55' : '1',
                cleanedNumber = phoneNumber.replace(/\D/g, ''),
                area = cleanedNumber.substring(0, 2),
                number = cleanedNumber.substring(2),
                type = number.length === 9 ? 'MOBILE' : 'HOME';

            return [
                {
                    country: countryNumber,
                    area: area,
                    number: number,
                    type: type
                }
            ];
        },

        /**
         * Get PagBank Pre Order Data
         * @param {Object} cardPayData
         * @returns {Void}
         */
        getPreOrderData(
            cardPayData
        ) {
            var totalAmount = parseFloat(quote.totals()['base_grand_total']) * 100,
                customerEmail = quote.guestEmail ? quote.guestEmail : window.checkoutConfig.customerData.email,
                billingAddress = quote.billingAddress(),
                shippingAddress = quote.isVirtual() ? quote.billingAddress() : quote.shippingAddress(),
                telphone = this.formatPhoneNumber(billingAddress.telephone, 'BR'),
                zipBilling = billingAddress.postcode.replace(/[^0-9]/g, ''),
                zipShipping = shippingAddress.postcode.replace(/[^0-9]/g, ''),
                countryBilling = this.getCountryIsoAlpha3(billingAddress.countryId),
                countryShipping = this.getCountryIsoAlpha3(shippingAddress.countryId),
                currencyCode = quote.totals().quote_currency_code,
                request = {
                    data: {
                        customer: {
                            name: billingAddress.firstname + ' ' + billingAddress.lastname,
                            email: customerEmail,
                            phones: telphone
                        },
                        paymentMethod: cardPayData,
                        amount: {
                            value: totalAmount.toFixed(0),
                            currency: currencyCode
                        },
                        billingAddress: {
                            street: billingAddress.street[0],
                            number: billingAddress.street[1],
                            complement: billingAddress.street[2] ? billingAddress.street[2] : null,
                            regionCode: billingAddress.regionCode,
                            country: countryBilling,
                            city: billingAddress.city,
                            postalCode: zipBilling
                        },
                        shippingAddress: {
                            street: shippingAddress.street[0],
                            number: shippingAddress.street[1],
                            complement: shippingAddress.street[2] ? shippingAddress.street[2] : null,
                            regionCode: shippingAddress.regionCode,
                            country: countryShipping,
                            city: shippingAddress.city,
                            postalCode: zipShipping
                        },
                        dataOnly: false
                    },
                    beforeChallenge: this.challengeInstruction
                };

            return request;
        }
    };
});
