<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

declare(strict_types=1);

namespace PagBank\PaymentMagento\Model\Api\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use PagBank\PaymentMagento\Api\Data\CustomAmountInterface;

/**
 * Class Custom Amount - Model data.
 */
class CustomAmount extends AbstractSimpleObject implements CustomAmountInterface
{
    /**
     * @inheritdoc
     */
    public function getCustomAmount()
    {
        return $this->_get(CustomAmountInterface::CUSTOM_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setCustomAmount($customAmount)
    {
        return $this->setData(CustomAmountInterface::CUSTOM_AMOUNT, $customAmount);
    }
}