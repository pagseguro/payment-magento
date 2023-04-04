/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

var config = {
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
