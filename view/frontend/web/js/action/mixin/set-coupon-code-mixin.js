define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/action/get-payment-information'
], function ($, wrapper, getPaymentInformation) {
    'use strict';

    return function (setCouponCode) {
        return wrapper.wrap(setCouponCode, function (originalAction, couponCode, isApplied) {
            return originalAction(couponCode, isApplied).done(function () {
                if (isApplied) {
                    $(document).trigger('pagbank:refresh-installments');
                    getPaymentInformation();
                }
            });
        });
    };
});