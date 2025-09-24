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
        $needToCreateVoid = false;

        foreach ($voidResults as $voidResult) {
            if (!$voidResult['success']) {
                $allVoidsSuccessful = false;
                continue;
            }

            $paymentId = $voidResult['payment_id'];
            $voidTransactionId = $paymentId . '-void';
            $existingTransaction = $payment->getTransaction($voidTransactionId);
            
            if (!$existingTransaction) {
                $needToCreateVoid = true;
                $voidTransactionIds[] = [
                    'id' => $voidTransactionId,
                    'parent_id' => $paymentId,
                    'index' => $voidResult['index'],
                    'amount' => $voidResult['voided_amount']
                ];
            }
        }

        if ($allVoidsSuccessful) {
            if ($needToCreateVoid && !empty($voidTransactionIds)) {
                $firstVoid = reset($voidTransactionIds);
                $payment->setParentTransactionId($firstVoid['parent_id']);
                $payment->registerVoidNotification($amount);
                $secondVoid = end($voidTransactionIds);
                if (count($voidTransactionIds) === 2 && $secondVoid['id'] !== $firstVoid['id']) {
                    $payment->setTransactionId($secondVoid['id']);
                    $payment->setParentTransactionId($secondVoid['parent_id']);
                    $payment->setIsTransactionClosed(true);
                    $payment->setShouldCloseParentTransaction(true);
                    $payment->setTransactionAdditionalInfo(
                        Transaction::RAW_DETAILS,
                        [
                            'card_index' => $secondVoid['index'] + 1,
                            'voided_amount' => $secondVoid['amount'],
                            'payment_id' => $secondVoid['parent_id']
                        ]
                    );
                    $payment->addTransaction(Transaction::TYPE_VOID);
                }
            }
            
            $payment->setIsTransactionApproved(false);
            $payment->setIsTransactionDenied(true);
            $payment->setIsInProcess(false);
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
            $payment->setAmountCanceled($amount);
            $payment->setBaseAmountCanceled($baseAmount);
            $order->addStatusHistoryComment(
                __('Payment voided successfully for two credit cards.'),
                false
            );
        } else {
            $payment->setIsTransactionApproved(false);
            $payment->setIsTransactionDenied(false);
            $payment->setIsInProcess(true);
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
