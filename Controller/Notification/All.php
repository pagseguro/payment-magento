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
use PagBank\PaymentMagento\Controller\AbstractNotification;

/**
 * Controler Notification All - Notification of receivers for All Methods.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class All extends AbstractNotification implements CsrfAwareActionInterface
{
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

        $psData = $this->json->unserialize($response);

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
        $result = [];
        $searchCriteria = $this->searchCriteria->addFilter('txn_id', $psPaymentId)
            ->addFilter('txn_type', 'order')
            ->create();

        try {
            /** @var TransactionRepositoryInterface $transaction */
            $transaction = $this->transaction->getList($searchCriteria)->getFirstItem();
        } catch (Exception $exc) {
            /** @var ResultInterface $result */
            $result = $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $exc->getMessage(),
                ]
            );

            return $result;
        }

        if ($transaction->getOrderId()) {
            /** Order $order */
            $order = $this->getOrderData($transaction->getOrderId());

            $process = $this->processNotification($order);

            /** @var ResultInterface $result */
            $result = $this->createResult($process['code'], $process['msg']);

            return $result;
        }

        /** @var ResultInterface $result */
        $result = $this->createResult(200, []);

        return $result;
    }

    /**
     * Process Notification.
     *
     * @param OrderRepository $order
     *
     * @return array
     */
    public function processNotification($order)
    {
        $result = [];

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
