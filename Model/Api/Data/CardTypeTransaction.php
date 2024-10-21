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
use PagBank\PaymentMagento\Api\Data\CardTypeTransactionInterface;

/**
 * Class Credit Card Bin - Model data.
 */
class CardTypeTransaction extends AbstractSimpleObject implements CardTypeTransactionInterface
{
    /**
     * @inheritdoc
     */
    public function getCardTypeTransaction()
    {
        return $this->_get(CardTypeTransactionInterface::PAGBANK_CARD_TYPE_TRANSACTION);
    }

    /**
     * @inheritdoc
     */
    public function setCardTypeTransaction($cardTypeTransaction)
    {
        return $this->setData(CardTypeTransactionInterface::PAGBANK_CARD_TYPE_TRANSACTION, $cardTypeTransaction);
    }
}
