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
use PagBank\PaymentMagento\Api\Data\CardIndexInterface;

/**
 * Class Card Index - Model data.
 */
class CardIndex extends AbstractSimpleObject implements CardIndexInterface
{
    /**
     * @inheritdoc
     */
    public function getCardIndex()
    {
        return $this->_get(CardIndexInterface::CARD_INDEX);
    }

    /**
     * @inheritdoc
     */
    public function setCardIndex($cardIndex)
    {
        return $this->setData(CardIndexInterface::CARD_INDEX, $cardIndex);
    }
}