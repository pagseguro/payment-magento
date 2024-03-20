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

use Magento\Framework\Notification\NotifierInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use PagBank\PaymentMagento\Gateway\Config\Config;
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
     * Payment Method Vault.
     */
    public const PAYMENT_METHOD_VAULT = 'pagbank_paymentmagento_cc_vault';

    /**
     * Payment Method Pix.
     */
    public const PAYMENT_METHOD_PIX = 'pagbank_paymentmagento_pix';

    /**
     * Payment Method Deep Link.
     */
    public const PAYMENT_METHOD_DEEP_LINK = 'pagbank_paymentmagento_deep_link';

    /**
     * Payment Method Boleto.
     */
    public const PAYMENT_METHOD_BOLETO = 'pagbank_paymentmagento_boleto';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var NotifierInterface
     */
    protected $notifierInterface;

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
     * @param Config            $config
     * @param NotifierInterface $notifierInterface
     * @param Update            $update
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Logger $logger,
        Config $config,
        NotifierInterface $notifierInterface,
        Update $update,
        CollectionFactory $collectionFactory
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->notifierInterface = $notifierInterface;
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
        $exclude = $this->config->getAddtionalValue('exclude_fetch_cron');
        $exclude = explode(',', $exclude);

        $orders = $this->collectionFactory->create()
                    ->addFieldToFilter('state', [
                        'in' => [
                            Order::STATE_NEW,
                            Order::STATE_PAYMENT_REVIEW,
                        ],
                    ])
                    ->addFieldToFilter('status', [
                        'nin' => $exclude,
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

            try {
                $this->update->getUpdate($incrementId);
            } catch (\Throwable $th) {
                $this->errorNotificationManager($order);
                continue;
            }
        }
    }

    /**
     * Find Deep Link.
     *
     * @return void
     */
    public function findDeepLink()
    {
        $orders = $this->getFilterdOrders(self::PAYMENT_METHOD_DEEP_LINK);

        foreach ($orders as $order) {
            $incrementId = $order->getIncrementId();

            try {
                $this->update->getUpdate($incrementId);
            } catch (\Throwable $th) {
                $this->errorNotificationManager($order);
                continue;
            }
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

            try {
                $this->update->getUpdate($incrementId);
            } catch (\Throwable $th) {
                $this->errorNotificationManager($order);
                continue;
            }
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

            try {
                $this->update->getUpdate($incrementId);
            } catch (\Throwable $th) {
                $this->errorNotificationManager($order);
                continue;
            }
        }
    }

    /**
     * Find Vault.
     *
     * @return void
     */
    public function findVault()
    {
        $orders = $this->getFilterdOrders(self::PAYMENT_METHOD_VAULT);

        foreach ($orders as $order) {
            $incrementId = $order->getIncrementId();

            try {
                $this->update->getUpdate($incrementId);
            } catch (\Throwable $th) {
                $this->errorNotificationManager($order);
                continue;
            }
        }
    }

    /**
     * Error Notification Manager.
     *
     * @param Order $order
     * @return void
     */
    public function errorNotificationManager($order)
    {
        $header = __('PagBank, error when checking order status.');

        $detail = __(
            'It is not possible to check the status of order %1, please perform a manual verification.',
            $order->getIncrementId()
        );

        $this->notifierInterface->addCritical($header, $detail);
    }
}
