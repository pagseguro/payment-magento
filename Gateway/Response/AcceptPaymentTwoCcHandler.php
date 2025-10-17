<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Response;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Lock\LockManagerInterface;

/**
 * Class Accept Payment Two Cc Handler - Reply Flow for Accept Two Credit Cards.
 */
class AcceptPaymentTwoCcHandler implements HandlerInterface
{
    /**
     * Response Pay PagBank Id - Block Name.
     */
    public const RESPONSE_PAGBANK_ID = 'id';

    /**
     * Capture Results - Block Name.
     */
    public const CAPTURE_RESULTS = 'capture_results';

    /**
     * Total Authorized Amount - Block Name.
     */
    public const TOTAL_AUTHORIZED_AMOUNT = 'total_authorized_amount';

    /**
     * @var LockManagerInterface
     */
    private $lockManager;

    /**
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        LockManagerInterface $lockManager
    ) {
        $this->lockManager = $lockManager;
    }

    /**
     * Handles.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        try {
            if (!$response['RESULT_CODE']) {
                return;
            }

            $paymentDO = $handlingSubject['payment'];
            $payment = $paymentDO->getPayment();
            $order = $payment->getOrder();
            $amount = $order->getTotalDue();
            $baseAmount = $order->getBaseTotalDue();
            $captureResults = $response[self::CAPTURE_RESULTS] ?? [];

            if (count($captureResults) !== 2) {
                throw new InvalidArgumentException('Two capture results expected for two card payment');
            }

            if ($order->hasInvoices()) {
                return;
            }

            $allCaptures = true;
            foreach ($captureResults as $captureResult) {
                if (!$captureResult['success']) {
                    $allCaptures = false;
                    break;
                }
            }

            if ($allCaptures) {
                $hasProcFirstCard = false;
                
                foreach ($captureResults as $index => $captureResult) {
                    $paymentId = $captureResult['payment_id'];
                    $captureTransactionId = $paymentId . '-capture';
                    if ($payment->getTransaction($captureTransactionId)) {
                        continue;
                    }
                    $payment->setTransactionId($captureTransactionId);
                    $payment->setParentTransactionId($paymentId);
                    
                    if (!$hasProcFirstCard) {
                        $payment->setIsTransactionApproved(true);
                        $payment->setIsTransactionDenied(false);
                        $payment->setIsInProcess(true);
                        $payment->registerCaptureNotification($amount);
                        $payment->setAmountAuthorized($amount);
                        $payment->setBaseAmountAuthorized($baseAmount);
                        $hasProcFirstCard = true;
                    } else {
                        $payment->setIsTransactionClosed(true);
                        $payment->setShouldCloseParentTransaction(true);
                        $payment->setTransactionAdditionalInfo(
                            Transaction::RAW_DETAILS,
                            [
                                'card_index' => $index + 1,
                                'authorized_amount' => $captureResult['authorized_amount'] ?? 0,
                                'payment_id' => $paymentId,
                                'capture_data' => $captureResult['data'] ?? []
                            ]
                        );
                        $payment->addTransaction(Transaction::TYPE_CAPTURE);
                    }
                }
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
                $invoice = $payment->getCreatedInvoice();
                if ($invoice) {
                    $order->setState(Order::STATE_PROCESSING)
                        ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
                        ->addStatusHistoryComment(__('Payment confirmed by PagBank.'));
                }
                $order->save();
            } else {
                $payment->setIsTransactionApproved(false);
                $payment->setIsTransactionDenied(true);
                $payment->setIsInProcess(false);
                $order->addStatusHistoryComment(
                    __('Failed to capture payment for one or both credit cards.'),
                    false
                );
                throw new LocalizedException(
                    __('Failed to capture payment for all cards')
                );
            }
        } finally {
            if (isset($response['lock_name']) && $response['lock_name']) {
                $this->lockManager->unlock($response['lock_name']);
            }
        }
    }
}
