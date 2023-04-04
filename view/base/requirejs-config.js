/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

var config = {
    map: {
        '*': {
            'clipboard':'PagBank_PaymentMagento/js/view/payment/lib/clipboardjs/clipboard',
            'widgetClipboard':'PagBank_PaymentMagento/js/view/payment/lib/clipboardjs/widgetClipboard'
        }
    },
    config: {
        mixins: {
            'mage/validation': {
                'PagBank_PaymentMagento/js/validation/custom-validation': true
            }
        }
    }
};
