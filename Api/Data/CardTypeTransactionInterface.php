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
 * Interface Card Type Transaction - Data Card Type Transaction.
 *
 * @api
 *
 * @since 100.0.1
 */
interface CardTypeTransactionInterface
{
    /**
     * @const string
     */
    public const PAGBANK_CARD_TYPE_TRANSACTION = 'card_type_transaction';

    /**
     * Get Card Type Transaction.
     *
     * @return string
     */
    public function getCardTypeTransaction();

    /**
     * Set Card Type Transaction.
     *
     * @param string $cardTypeTransaction
     *
     * @return void
     */
    public function setCardTypeTransaction($cardTypeTransaction);
}
