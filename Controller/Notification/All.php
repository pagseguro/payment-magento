<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Controller\Notification;

use Exception;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Lock\LockManagerInterface;
use PagBank\PaymentMagento\Controller\AbstractNotification;

/**
 * Controler Notification All - Notification of receivers for All Methods.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class All extends AbstractNotification implements CsrfAwareActionInterface
{
    /**
     * @var LockManagerInterface
     */
    private $lockManager;

    /**
     * @var int
     */
    private $lockTimeout = 360;

    /**
     * @param \PagBank\PaymentMagento\Gateway\Config\Config $config
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteria
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transaction
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Notification\NotifierInterface $notifierPool
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory
     * @param \Magento\Sales\Model\Service\CreditmemoService $creditMemoService
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @param LockManagerInterface $lockManager
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \PagBank\PaymentMagento\Gateway\Config\Config $config,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteria,
        \Magento\Sales\Api\TransactionRepositoryInterface $transaction,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Notification\NotifierInterface $notifierPool,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory,
        \Magento\Sales\Model\Service\CreditmemoService $creditMemoService,
        \Magento\Sales\Model\Order\Invoice $invoice,
        LockManagerInterface $lockManager
    ) {
        parent::__construct(
            $config,
            $context,
            $json,
            $searchCriteria,
            $transaction,
            $orderRepository,
            $pageFactory,
            $resultJsonFactory,
            $logger,
            $notifierPool,
            $creditMemoFactory,
            $creditMemoService,
            $invoice
        );
        $this->lockManager = $lockManager;
    }

    /**
     * Create Csrf Validation Exception.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        if ($request) {
            return null;
        }
    }

    /**
     * Validate For Csrf.
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        if ($request) {
            return true;
        }
    }

    /**
     * Execute.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->createResult(
                404,
                [
                    'error'   => 404,
                    'message' => __('You should not be here...'),
                ]
            );
        }

        $response = $this->getRequest()->getContent();

        try {
            $psData = $this->json->unserialize($response);
        } catch (Exception $exc) {
            return $this->createResult(
                205,
                [
                    'error'   => 205,
                    'message' => $exc->getMessage(),
                ]
            );
        }

        if (!isset($psData['id'])) {
            return $this->createResult(
                200,
                [
                    'error'   => 200,
                    'message' => __('Not apply.'),
                ]
            );
        }

        $psPaymentId = $psData['id'];

        $this->logger->debug([
            'payload' => $psPaymentId,
        ]);

        return $this->initProcess($psPaymentId);
    }

    /**
     * Init Process.
     *
     * @param string $psPaymentId
     *
     * @return ResultInterface
     */
    public function initProcess($psPaymentId)
    {
        $searchCriteria = $this->searchCriteria->addFilter('txn_id', $psPaymentId)
            ->addFilter('txn_type', 'order')
            ->create();

        try {
            $transaction = $this->transaction->getList($searchCriteria)->getFirstItem();
        } catch (Exception $exc) {
            return $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $exc->getMessage(),
                ]
            );
        }

        if (!$transaction->getOrderId()) {
            return $this->createResult(200, []);
        }

        $orderId = $transaction->getOrderId();
        $lockName = 'pagbank_order_' . $orderId;

        if (!$this->lockManager->lock($lockName, $this->lockTimeout)) {
            $this->logger->debug([
                'message' => 'Order is already being processed',
                'order_id' => $orderId,
                'payment_id' => $psPaymentId,
            ]);

            return $this->createResult(
                409,
                [
                    'error'   => 409,
                    'message' => __('Order is already being processed.'),
                ]
            );
        }

        try {
            $order = $this->getOrderData($orderId);

            if ($order instanceof ResultInterface) {
                $this->lockManager->unlock($lockName);
                return $order;
            }

            $process = $this->processNotification($order);

            return $this->createResult($process['code'], $process['msg']);
        } catch (Exception $exc) {
            $this->lockManager->unlock($lockName);
            
            $this->logger->critical([
                'message' => 'Error during webhook processing',
                'exception' => $exc->getMessage(),
                'order_id' => $orderId,
                'payment_id' => $psPaymentId,
            ]);
            
            return $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $exc->getMessage(),
                ]
            );
        }
    }

    /**
     * Process Notification.
     *
     * @param \Magento\Sales\Model\OrderRepository $order
     *
     * @return array
     */
    public function processNotification($order)
    {
        $isNotApplicable = $this->filterInvalidNotification($order);

        if ($isNotApplicable['isInvalid']) {
            return $isNotApplicable;
        }

        $payment = $order->getPayment();

        $payment->update(true);

        $order->save();

        $result = [
            'code'  => 200,
            'msg'   => [
                'order'     => $order->getIncrementId(),
                'state'     => $order->getState(),
                'status'    => $order->getStatus(),
            ],
        ];

        return $result;
    }
}