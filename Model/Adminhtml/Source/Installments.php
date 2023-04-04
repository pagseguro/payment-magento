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

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Installments - Define installment types.
 */
class Installments implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            '1'     => __('1 installment'),
            '2'     => __('2 installments'),
            '3'     => __('3 installments'),
            '4'     => __('4 installments'),
            '5'     => __('5 installments'),
            '6'     => __('6 installments'),
            '7'     => __('7 installments'),
            '8'     => __('8 installments'),
            '9'     => __('9 installments'),
            '10'    => __('10 installments'),
            '11'    => __('11 installments'),
            '12'    => __('12 installments'),
            '13'    => __('13 installments'),
            '14'    => __('14 installments'),
            '15'    => __('15 installments'),
            '16'    => __('16 installments'),
            '17'    => __('17 installments'),
            '18'    => __('18 installments'),
        ];
    }
}
