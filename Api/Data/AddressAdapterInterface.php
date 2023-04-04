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

use Magento\Payment\Gateway\Data\AddressAdapterInterface as MageAddressAdapterInterface;

interface AddressAdapterInterface extends MageAddressAdapterInterface
{
    /**
     * Get region name.
     *
     * @return string[]|null
     */
    public function getRegion();

    /**
     * Get street line 3.
     *
     * @return string|null
     */
    public function getStreetLine3();

    /**
     * Get street line 4.
     *
     * @return string|null
     */
    public function getStreetLine4();

    /**
     * Get Vat Id.
     *
     * @return string
     */
    public function getVatId();
}
