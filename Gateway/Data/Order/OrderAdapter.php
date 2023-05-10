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

use Magento\Payment\Gateway\Data\Order\AddressAdapterFactory;
use Magento\Payment\Gateway\Data\Order\OrderAdapter as MageOrderAdapter;
use Magento\Sales\Model\Order;

/**
 * Class Order Adapter - Adds necessary information to the order.
 */
class OrderAdapter extends MageOrderAdapter
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var AddressAdapterFactory
     */
    protected $addAdapterFactory;

    /**
     * @param Order                 $order
     * @param AddressAdapterFactory $addAdapterFactory
     */
    public function __construct(
        Order $order,
        AddressAdapterFactory $addAdapterFactory
    ) {
        $this->order = $order;
        $this->addAdapterFactory = $addAdapterFactory;
        parent::__construct($order, $addAdapterFactory);
    }

    /**
     * Gets the Tax/Vat for the customer.
     *
     * @return string|null Tax/Vat.
     */
    public function getCustomerTaxvat()
    {
        return $this->order->getCustomerTaxvat();
    }

    /**
     * Returns order pagbank interest amount.
     *
     * @return float|null
     */
    public function getPagbankInterestAmount()
    {
        return $this->order->getPagbankInterestAmount();
    }

    /**
     * Returns order base pagbank interest amount.
     *
     * @return float|null
     */
    public function getBasePagbankInterestAmount()
    {
        return $this->order->getBasePagbankInterestAmount();
    }

    /**
     * Returns order shipping amount
     *
     * @return float|null
     */
    public function getShippingAmount()
    {
        return $this->order->getBaseShippingAmount();
    }
}
