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
 * Class TaxId - Defines tax ID.
 */
class TaxId implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            null       => __('Please select'),
            'customer' => __('by customer form (taxvat - customer account)'),
            'address'  => __('by address form (vat_id - checkout)'),
        ];
    }
}
