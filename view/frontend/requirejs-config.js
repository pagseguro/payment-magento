/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

var config = {
    config: {
        mixins: {
            'Magento_SalesRule/js/action/set-coupon-code': {
                'PagBank_PaymentMagento/js/action/mixin/set-coupon-code-mixin': true
            },
            'Magento_SalesRule/js/action/cancel-coupon': {
                'PagBank_PaymentMagento/js/action/mixin/cancel-coupon-mixin': true
            }
        }
    },
    paths: {
        // eslint-disable-next-line max-len
        'pagBankCardJs':'https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js?source=Magento'
    },
    shim: {
        'pagBankCardJs': {
            'deps': ['jquery']
        }
    }
};