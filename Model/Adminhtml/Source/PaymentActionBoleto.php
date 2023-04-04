<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class Payment Action Boleto - Define payment actions.
 */
class PaymentActionBoleto implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * To Options Array.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => AbstractMethod::ACTION_ORDER,
                'label' => __('Order'),
            ],
        ];
    }
}
