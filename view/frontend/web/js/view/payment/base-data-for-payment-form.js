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
    'uiElement'
],
function (
    Component
) {
    'use strict';

    return Component.extend({

        /**
         * Set Payment Code
         * @param {String} paymentCode
         */
        setPaymentCode(paymentCode) {
            this.paymentCode = paymentCode;
        },

        /**
         * Has verification
         * @returns {Boolean}
         */
        hasVerification() {
            return window.checkoutConfig.payment[this.paymentCode].hasOwnProperty('useCvv') ?
            window.checkoutConfig.payment[this.paymentCode].useCvv
            : false;
        },

        /**
         * Get title
         * @returns {String|*}
         */
        getTitle() {
            return window.checkoutConfig.payment[this.paymentCode].hasOwnProperty('title') ?
            window.checkoutConfig.payment[this.paymentCode].title
            : false;
        },

        /**
         * Get logo
         * @returns {String|*}
         */
        getLogo() {
            return window.checkoutConfig.payment[this.paymentCode].hasOwnProperty('logo') ?
            window.checkoutConfig.payment[this.paymentCode].logo
            : false;
        },

        /**
         * Get Public Key
         * @returns {String|*}
         */
        getPublicKey() {
            return window.checkoutConfig.payment[this.paymentCode].hasOwnProperty('public_key') ?
            window.checkoutConfig.payment[this.paymentCode].public_key
            : false;
        },

        /**
         * Get Env
         * @returns {String|*}
         */
        getEnv() {
            return window.checkoutConfig.payment[this.paymentCode].threeDs.hasOwnProperty('env') ?
            window.checkoutConfig.payment[this.paymentCode].threeDs.env
            : false;
        },

        /**
         * Has 3ds Reject Not Auth
         * @returns {Boolean|*}
         */
        hasThreeDsRejectNotAuth() {
            return window.checkoutConfig.payment[this.paymentCode].threeDs.hasOwnProperty('reject') ?
            window.checkoutConfig.payment[this.paymentCode].threeDs.reject
            : false;
        },

        /**
         * Get payment icons
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons(type) {
            return window.checkoutConfig.payment[this.paymentCode].icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment[this.paymentCode].icons[type]
                : false;
        },

        /**
         * Is show legend
         * @returns {Boolean}
         */
        isShowLegend() {
            return true;
        },

        /**
         * Get instruction checkout
         * @returns {string}
         */
        getInstructionCheckout() {
            return window.checkoutConfig.payment[this.paymentCode].hasOwnProperty('instruction_checkout') ?
                window.checkoutConfig.payment[this.paymentCode].instruction_checkout
                : false;
        },

        /**
         * Get Expiration
         * @returns {string}
         */
        getExpiration() {
            return window.checkoutConfig.payment[this.paymentCode].hasOwnProperty('expiration') ?
                window.checkoutConfig.payment[this.paymentCode].expiration
                : false;
        }
    });
});
