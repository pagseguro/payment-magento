<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Api\Data;

use Magento\Payment\Gateway\Data\OrderAdapterInterface as MageOrderAdapterInterface;

/**
 * Interface OrderAdapterInterface.
 *
 * @api
 *
 * @since 100.0.2
 */
interface OrderAdapterInterface extends MageOrderAdapterInterface
{
    /**
     * Gets the Tax/Vat for the customer.
     *
     * @return string|null Tax/Vat.
     */
    public function getCustomerTaxvat();

    /**
     * Returns order pagbank interest amount.
     *
     * @return float|null
     */
    public function getPagbankInterestAmount();

    /**
     * Returns order base pagbank interest amount.
     *
     * @return float|null
     */
    public function getBasePagbankInterestAmount();

    /**
     * Returns order shipping amount.
     *
     * @return float|null
     */
    public function getShippingAmount();
}
