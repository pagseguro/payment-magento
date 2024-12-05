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
 * Class Times - Defines possible times for rule 3ds.
 */
class Times implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            '0'     => __('Do not apply rule'),
            '24'    => __('1 day'),
            '48'    => __('2 days - recommended'),
            '72'    => __('3 days'),
            '168'   => __('1 week'),
            '720'   => __('1 month'),
        ];
    }
}
