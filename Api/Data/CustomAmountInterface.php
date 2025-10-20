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
 * Interface Custom Amount - Data Custom Amount for Two CC.
 *
 * @api
 *
 * @since 100.0.1
 */
interface CustomAmountInterface
{
    /**
     * @const string
     */
    public const CUSTOM_AMOUNT = 'custom_amount';

    /**
     * Get Custom Amount.
     *
     * @return float|null
     */
    public function getCustomAmount();

    /**
     * Set Custom Amount.
     *
     * @param float|null $customAmount
     *
     * @return void
     */
    public function setCustomAmount($customAmount);
}