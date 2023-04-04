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

use Magento\Payment\Model\Source\Cctype as MagentoCcType;

/**
 * Class CcType - Defines allowed credit card types.
 */
class CcType extends MagentoCcType
{
    /**
     * Get Allwed Types.
     *
     * @return array
     */
    public function getAllowedTypes(): array
    {
        return ['HC', 'ELO', 'AE', 'VI', 'MC', 'AU', 'DN'];
    }

    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $allowed = $this->getAllowedTypes();
        $options = [];

        foreach ($this->_paymentConfig->getCcTypes() as $code => $name) {
            if (in_array($code, $allowed)) {
                $options[] = ['value' => $code, 'label' => $name];
            }
        }

        return $options;
    }
}
