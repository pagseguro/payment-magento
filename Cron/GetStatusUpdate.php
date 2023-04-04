<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Cron;

use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use PagBank\PaymentMagento\Model\Console\Command\Orders\Update;

/**
 * Class Get Status Update in PagBank.
 */
class GetStatusUpdate
{
    /**
     * Payment Method Credit Card.
     */
    public const PAYMENT_METHOD_CC = 'pagbank_paymentmagento_cc';

    /**
     * Payment Method Pix.
     */
    public const PAYMENT_METHOD_PIX = 'pagbank_paymentmagento_pix';

    /**
     * Payment Method Boleto.
     */
    public const PAYMENT_METHOD_BOLETO = 'pagbank_paymentmagento_boleto';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Update
     */
    protected $update;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Constructor.
     *
     * @param Logger            $logger
     * @param Update            $update
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Logger $logger,
        Update $update,
        CollectionFactory $collectionFactory
    ) {
        $this->logger = $logger;
        $this->update = $update;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get Filtered Orders.
     *
     * @param string $method
     *
     * @return CollectionFactory|null
     */
    public function getFilterdOrders($method)
    {
        $orders = $this->collectionFactory->create()
                    ->addFieldToFilter('state', [
                        'in' => [
                            Order::STATE_NEW,
                            Order::STATE_PAYMENT_REVIEW,
                        ],
                    ]);

        $orders->getSelect()
            ->join(
                ['sop' => 'sales_order_payment'],
                'main_table.entity_id = sop.parent_id',
                ['method']
            )
            ->where('sop.method = ?', $method);

        return $orders;
    }

    /**
     * Find Pix.
     *
     * @return void
     */
    public function findPix()
    {
        $orders = $this->getFilterdOrders(self::PAYMENT_METHOD_PIX);

        foreach ($orders as $order) {
            $incrementId = $order->getIncrementId();

            $this->update->getUpdate($incrementId);
        }
    }

    /**
     * Find Boleto.
     *
     * @return void
     */
    public function findBoleto()
    {
        $orders = $this->getFilterdOrders(self::PAYMENT_METHOD_BOLETO);

        foreach ($orders as $order) {
            $incrementId = $order->getIncrementId();

            $this->update->getUpdate($incrementId);
        }
    }

    /**
     * Find Credit Card.
     *
     * @return void
     */
    public function findCc()
    {
        $orders = $this->getFilterdOrders(self::PAYMENT_METHOD_CC);

        foreach ($orders as $order) {
            $incrementId = $order->getIncrementId();

            $this->update->getUpdate($incrementId);
        }
    }
}
