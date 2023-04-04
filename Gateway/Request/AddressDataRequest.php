<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Request;

/**
 * Class Address Data Request - Address structure.
 */
class AddressDataRequest
{
    /**
     * Address block name.
     */
    public const ADDRESS = 'address';

    /**
     * Billing Address block name.
     */
    public const BILLING_ADDRESS = 'billing';

    /**
     * Shipping Address block name.
     */
    public const SHIPPING_ADDRESS = 'shipping';

    /**
     * Street block name.
     */
    public const STREET = 'street';

    /**
     * Number block name.
     */
    public const NUMBER = 'number';

    /**
     * Complement block name.
     */
    public const COMPLEMENT = 'complement';

    /**
     * Locality block name.
     */
    public const LOCALITY = 'locality';

    /**
     * City block name.
     */
    public const CITY = 'city';

    /**
     * State block name.
     */
    public const STATE = 'region';

    /**
     * State Code block name.
     */
    public const STATE_CODE = 'region_code';

    /**
     * Country block name.
     */
    public const COUNTRY_CODE = 'country';

    /**
     * Postal Code block name.
     */
    public const POSTAL_CODE = 'postal_code';
}
