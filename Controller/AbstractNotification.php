<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Controller;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Notification\NotifierInterface as NotifierPool;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\CreditmemoService;
use PagBank\PaymentMagento\Gateway\Config\Config;

/**
 * Abstract Notification PagBank.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractNotification extends Action
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteria;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transaction;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var NotifierPool
     */
    protected $notifierPool;

    /**
     * @var CreditmemoFactory
     */
    protected $creditMemoFactory;

    /**
     * @var CreditmemoService
     */
    protected $creditMemoService;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var LockManagerInterface
     */
    protected $lockManager;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @param Config                         $config
     * @param Context                        $context
     * @param Json                           $json
     * @param SearchCriteriaBuilder          $searchCriteria
     * @param TransactionRepositoryInterface $transaction
     * @param OrderRepository                $orderRepository
     * @param PageFactory                    $pageFactory
     * @param JsonFactory                    $resultJsonFactory
     * @param Logger                         $logger
     * @param NotifierPool                   $notifierPool
     * @param CreditmemoFactory              $creditMemoFactory
     * @param CreditmemoService              $creditMemoService
     * @param Invoice                        $invoice
     * @param LockManagerInterface           $lockManager
     * @param PaymentHelper                  $paymentHelper
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Config $config,
        Context $context,
        Json $json,
        SearchCriteriaBuilder $searchCriteria,
        TransactionRepositoryInterface $transaction,
        OrderRepository $orderRepository,
        PageFactory $pageFactory,
        JsonFactory $resultJsonFactory,
        Logger $logger,
        NotifierPool $notifierPool,
        CreditmemoFactory $creditMemoFactory,
        CreditmemoService $creditMemoService,
        Invoice $invoice,
        LockManagerInterface $lockManager,
        PaymentHelper $paymentHelper
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->json = $json;
        $this->searchCriteria = $searchCriteria;
        $this->transaction = $transaction;
        $this->orderRepository = $orderRepository;
        $this->pageFactory = $pageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->notifierPool = $notifierPool;
        $this->creditMemoFactory = $creditMemoFactory;
        $this->creditMemoService = $creditMemoService;
        $this->invoice = $invoice;
        $this->lockManager = $lockManager;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Get Order Data.
     *
     * @param string $orderId
     *
     * @return OrderRepository|ResultInterface
     */
    public function getOrderData($orderId)
    {
        try {
            /** @var OrderRepository $order */
            $order = $this->orderRepository->get($orderId);
        } catch (Exception $exc) {
            return $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $exc->getMessage(),
                ]
            );
        }

        return $order;
    }

    /**
     * Create Result.
     *
     * @param int   $statusCode
     * @param array $data
     *
     * @return ResultInterface
     */
    public function createResult($statusCode, $data)
    {
        /** @var JsonFactory $resultPage */
        $resultPage = $this->resultJsonFactory->create();
        $resultPage->setHttpResponseCode($statusCode);
        $resultPage->setData($data);

        return $resultPage;
    }

    /**
     * Filter Invalid Notification.
     *
     * @param OrderRepository $order
     *
     * @return array
     */
    public function filterInvalidNotification($order)
    {
        $result = [];

        if (!$order->getEntityId()) {
            $result = [
                'isInvalid' => true,
                'code'      => 406,
                'msg'       => __('Order not found.'),
            ];

            return $result;
        }

        $state = $order->getState();

        if ($state !== Order::STATE_NEW && $state !== Order::STATE_PAYMENT_REVIEW) {
            $result = [
                'isInvalid' => true,
                'code'      => 406,
                'msg'       => __('Not Apply.'),
            ];

            return $result;
        }

        if ($order->hasInvoices()) {
            $result = [
                'isInvalid' => true,
                'code'      => 406,
                'msg'       => __('Order already has invoice.'),
            ];

            return $result;
        }

        $exclude = $this->config->getAddtionalValue('exclude_fetch_cron');
        $excludeStatuses = explode(',', $exclude);
        
        if (in_array($order->getStatus(), $excludeStatuses)) {
            $result = [
                'isInvalid' => true,
                'code'      => 406,
                'msg'       => __('Order status is excluded.'),
            ];

            return $result;
        }

        $payment = $order->getPayment();
        $paymentMethod = $payment->getMethod();
        $creditCardMethods = [
            'pagbank_paymentmagento_two_cc',
            'pagbank_paymentmagento_cc_vault',
            'pagbank_paymentmagento_cc',
        ];

        if (in_array($paymentMethod, $creditCardMethods) && $state === Order::STATE_PAYMENT_REVIEW) {
            try {
                $methodInstance = $this->paymentHelper->getMethodInstance($paymentMethod);
                $paymentAction = $methodInstance->getConfigData('payment_action', $order->getStoreId());
                
                if ($paymentAction === 'authorize') {
                    $result = [
                        'isInvalid' => true,
                        'code'      => 406,
                        'msg'       => __('Credit card payment with authorize action in review cannot be processed by webhook.'),
                    ];

                    return $result;
                }
            } catch (Exception $e) {
                $this->logger->debug([
                    'message' => 'Error getting payment method instance',
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        $result = [
            'isInvalid' => false,
        ];

        return $result;
    }
}
