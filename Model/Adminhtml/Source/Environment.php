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
 * Class Environment - Defines the types of environment.
 */
class Environment implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'production' => __('Production'),
            'sandbox'    => __('Sandbox - Environment for tests'),
        ];
    }
}
