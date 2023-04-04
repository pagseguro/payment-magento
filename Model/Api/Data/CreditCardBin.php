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
use PagBank\PaymentMagento\Api\Data\CreditCardBinInterface;

/**
 * Class Credit Card Bin - Model data.
 */
class CreditCardBin extends AbstractSimpleObject implements CreditCardBinInterface
{
    /**
     * @inheritdoc
     */
    public function getCreditCardBin()
    {
        return $this->_get(CreditCardBinInterface::PAGBANK_CREDIT_CARD_BIN);
    }

    /**
     * @inheritdoc
     */
    public function setCreditCardBin($creditCardBin)
    {
        return $this->setData(CreditCardBinInterface::PAGBANK_CREDIT_CARD_BIN, $creditCardBin);
    }
}
