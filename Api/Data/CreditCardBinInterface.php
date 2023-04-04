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
 * Interface Credit Card Bin - Data Credit Card Bin.
 *
 * @api
 *
 * @since 100.0.1
 */
interface CreditCardBinInterface
{
    /**
     * @const string
     */
    public const PAGBANK_CREDIT_CARD_BIN = 'credit_card_bin';

    /**
     * Get Credit Card Bin.
     *
     * @return string
     */
    public function getCreditCardBin();

    /**
     * Set Credit Card Bin.
     *
     * @param string $cardNumber
     *
     * @return void
     */
    public function setCreditCardBin($cardNumber);
}
