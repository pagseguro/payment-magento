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
 * Class Refund Payment Two Cc Handler - Reply Flow for Refund Two Credit Cards.
 */
class RefundPaymentTwoCcHandler implements HandlerInterface
{
    /**
     * Response Pay PagBank Id - Block Name.
     */
    public const RESPONSE_PAGBANK_ID = 'id';

    /**
     * Refund Results - Block Name.
     */
    public const REFUND_RESULTS = 'refund_results';

    /**
     * Total Refunded Amount - Block Name.
     */
    public const TOTAL_REFUNDED_AMOUNT = 'total_refunded_amount';

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
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();

        $refundResults = $response[self::REFUND_RESULTS] ?? [];

        if (count($refundResults) !== 2) {
            throw new InvalidArgumentException('Two refund results expected for two card payment');
        }

        $allRefundsSuccessful = true;
        $totalRefundedAmount = 0;

        foreach ($refundResults as $refundResult) {
            if (!$refundResult['success']) {
                $allRefundsSuccessful = false;
                continue;
            }

            $paymentId = $refundResult['payment_id'];
            $refundTransactionId = $paymentId . '-refund';
            $refundedAmount = $refundResult['refunded_amount'];
            
            $totalRefundedAmount += $refundedAmount;
            
            $existingTransaction = $payment->getTransaction($refundTransactionId);
            
            if (!$existingTransaction) {
                $payment->setTransactionId($refundTransactionId);
                $payment->setParentTransactionId($paymentId);
                
                $transaction = $payment->addTransaction(
                    Transaction::TYPE_REFUND,
                    null,
                    true
                );
                
                $transaction->setAdditionalInformation(
                    Transaction::RAW_DETAILS,
                    [
                        'card_index' => $refundResult['index'] + 1,
                        'refunded_amount' => $refundedAmount,
                        'payment_id' => $paymentId,
                        'status' => 'REFUNDED'
                    ]
                );
                
                $transaction->save();
            }
        }

        if ($allRefundsSuccessful) {
            $currentRefunded = $payment->getAmountRefunded() ?: 0;
            $currentBaseRefunded = $payment->getBaseAmountRefunded() ?: 0;
            
            $payment->setAmountRefunded($currentRefunded + $totalRefundedAmount);
            $payment->setBaseAmountRefunded($currentBaseRefunded + $totalRefundedAmount);
            
            if (($currentRefunded + $totalRefundedAmount) >= $order->getGrandTotal()) {
                $payment->setShouldCloseParentTransaction(true);
                $payment->setIsTransactionClosed(true);
            }

            $order->addStatusHistoryComment(
                __('Payment refunded successfully for two credit cards. Amount: %1', $totalRefundedAmount),
                false
            );
        } else {
            $order->addStatusHistoryComment(
                __('Failed to refund payment for one or both credit cards.'),
                false
            );

            throw new LocalizedException(
                __('Failed to refund payment for all cards')
            );
        }
    }
}
