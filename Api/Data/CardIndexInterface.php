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

namespace PagBank\PaymentMagento\Api\Data;

/**
 * Interface Card Index - Data Card Index for Two CC.
 *
 * @api
 *
 * @since 100.0.1
 */
interface CardIndexInterface
{
    /**
     * @const string
     */
    public const CARD_INDEX = 'card_index';

    /**
     * Get Card Index.
     *
     * @return int|null
     */
    public function getCardIndex();

    /**
     * Set Card Index.
     *
     * @param int|null $cardIndex
     *
     * @return void
     */
    public function setCardIndex($cardIndex);
}