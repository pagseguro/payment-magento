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
 * Interface Installment Selected - Interest.
 *
 * @api
 *
 * @since 100.0.1
 */
interface InstallmentSelectedInterface
{
    /**
     * @const string
     */
    public const PAGBANK_INSTALLMENT_SELECTED = 'installment_selected';

    /**
     * @const string
     */
    public const PAGBANK_INTEREST_AMOUNT = 'pagbank_interest_amount';

    /**
     * @const string
     */
    public const BASE_PAGBANK_INTEREST_AMOUNT = 'base_pagbank_interest_amount';

    /**
     * Return Installment Selected.
     *
     * @return int
     */
    public function getInstallmentSelected();

    /**
     * Set the Installment Selected.
     *
     * @param int $installmentSelected
     *
     * @return $this
     */
    public function setInstallmentSelected($installmentSelected);
}
