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

/**
 * Class Accept Payment Handler - Reply Flow for Accept Cc.
 */
class AcceptPaymentHandler implements HandlerInterface
{
    /**
     * Response Pay PagBank Id - Block Name.
     */
    public const RESPONSE_PAGBANK_ID = 'id';

    /**
     * Handles.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        if ($response['RESULT_CODE']) {
            $paymentDO = $handlingSubject['payment'];

            $payment = $paymentDO->getPayment();

            $order = $payment->getOrder();

            $amount = $order->getTotalDue();

            $baseAmount = $order->getBaseTotalDue();

            $pagbankPayId = $response[self::RESPONSE_PAGBANK_ID];

            $payment->setParentTransactionId($pagbankPayId);
            $payment->registerAuthorizationNotification($amount);
            $payment->registerCaptureNotification($amount);
            $payment->setIsTransactionApproved(true);
            $payment->setIsTransactionDenied(false);
            $payment->setIsInProcess(true);
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
            $payment->setAmountAuthorized($amount);
            $payment->setBaseAmountAuthorized($baseAmount);
            $payment->setShouldCloseParentTransaction(true);
        }
    }
}
