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
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Deny Payment Two Cc Handler - Reply Flow for Deny Two Credit Cards.
 */
class DenyPaymentTwoCcHandler implements HandlerInterface
{
    /**
     * Response Pay PagBank Id - Block Name.
     */
    public const RESPONSE_PAGBANK_ID = 'id';

    /**
     * Void Results - Block Name.
     */
    public const VOID_RESULTS = 'void_results';

    /**
     * Total Voided Amount - Block Name.
     */
    public const TOTAL_VOIDED_AMOUNT = 'total_voided_amount';

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

        if (!$response['RESULT_CODE']) {
            return;
        }

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();
        $amount = $order->getTotalDue();
        $baseAmount = $order->getBaseTotalDue();
        $voidResults = $response[self::VOID_RESULTS] ?? [];

        if (count($voidResults) !== 2) {
            throw new InvalidArgumentException('Two void results expected for two card payment');
        }

        $allVoidsSuccessful = true;
        $voidTransactionIds = [];
        $hasProcessedVoid = false;

        foreach ($voidResults as $index => $voidResult) {
            if (!$voidResult['success']) {
                $allVoidsSuccessful = false;
                continue;
            }

            $paymentId = $voidResult['payment_id'];
            $voidTransactionId = $paymentId . '-void';
            
            if ($payment->getTransaction($voidTransactionId)) {
                continue;
            }
            
            $voidTransactionIds[] = $voidTransactionId;
            
            $payment->setTransactionId($voidTransactionId);
            $payment->setParentTransactionId($paymentId);
            
            if (!$hasProcessedVoid) {
                $payment->setPreparedMessage(__('Order Canceled - Two Credit Cards.'));
                $payment->setIsTransactionPending(false);
                $payment->setIsTransactionDenied(true);
                $payment->setIsInProcess(false);
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
                $payment->setAmountCanceled($amount);
                $payment->setBaseAmountCanceled($baseAmount);
                
                $payment->registerVoidNotification($amount);
                $hasProcessedVoid = true;
            } else {
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
                
                $payment->setTransactionAdditionalInfo(
                    Transaction::RAW_DETAILS,
                    [
                        'card_index' => $index + 1,
                        'payment_id' => $paymentId,
                        'void_data' => $voidResult['data'] ?? []
                    ]
                );
                
                $payment->addTransaction(Transaction::TYPE_VOID);
            }
        }

        if ($allVoidsSuccessful) {
            $order->addStatusHistoryComment(
                __('Payment voided successfully for two credit cards.'),
                false
            );
        } else {
            $order->addStatusHistoryComment(
                __('Failed to void payment for one or both credit cards.'),
                false
            );
            
            throw new LocalizedException(
                __('Failed to void payment for all cards')
            );
        }
    }
}
