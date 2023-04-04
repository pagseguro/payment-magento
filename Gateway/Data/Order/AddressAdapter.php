<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Data\Order;

use Magento\Payment\Gateway\Data\Order\AddressAdapter as MageAddressAdapter;
use Magento\Sales\Api\Data\OrderAddressInterface;
use PagBank\PaymentMagento\Api\Data\AddressAdapterInterface;

/**
 * Class Address Adapter - Add necessary information to the address.
 */
class AddressAdapter extends MageAddressAdapter implements AddressAdapterInterface
{
    /**
     * @var OrderAddressInterface
     */
    protected $address;

    /**
     * @param OrderAddressInterface $address
     */
    public function __construct(OrderAddressInterface $address)
    {
        $this->address = $address;
        parent::__construct($address);
    }

    /**
     * Get region name.
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->address->getRegion();
    }

    /**
     * Get street line 3.
     *
     * @return string
     */
    public function getStreetLine3()
    {
        $street = $this->address->getStreet();

        return isset($street[2]) ? $street[2] : '';
    }

    /**
     * Get street line 4.
     *
     * @return string
     */
    public function getStreetLine4()
    {
        $street = $this->address->getStreet();

        return isset($street[3]) ? $street[3] : '';
    }

    /**
     * Get Vat Id.
     *
     * @return string
     */
    public function getVatId()
    {
        return $this->address->getVatId();
    }
}
