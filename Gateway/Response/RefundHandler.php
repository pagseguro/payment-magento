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
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Class Refund Handler - Reply Flow for Refund.
 */
class RefundHandler implements HandlerInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Response Pay PagBank Id - Block Name.
     */
    public const RESPONSE_PAGBANK_ID = 'id';

    /**
     * Response Pay Payment Response - Block Name.
     */
    public const RESPONSE_PAYMENT_RESPONSE = 'payment_response';

    /**
     * Response Pay Payment Response Code - Value.
     */
    public const RESPONSE_PAYMENT_RESPONSE_CODE = 'code';

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

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $pagbankPayId = $response[self::RESPONSE_PAGBANK_ID];

        $paymentResponse = $response[self::RESPONSE_PAYMENT_RESPONSE];

        $paymentResponseCode = (int) $paymentResponse[self::RESPONSE_PAYMENT_RESPONSE_CODE];

        $payment->setParentTransactionId($pagbankPayId);
        $payment->setTransactionId($pagbankPayId.'-refund');

        if ($paymentResponseCode === 20000) {
            $creditmemo = $payment->getCreditmemo();
            $creditmemo->setState(Creditmemo::STATE_REFUNDED);
        }
    }
}
