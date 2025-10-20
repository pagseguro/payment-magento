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
use Magento\Framework\Lock\LockManagerInterface;

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
            $pagbankPayId = $response[self::RESPONSE_PAGBANK_ID];
            $captureTransactionId = $pagbankPayId . '-capture';

            if ($payment->getTransaction($captureTransactionId)) {
                return;
            }

            if ($order->hasInvoices()) {
                return;
            }

            $payment->setAmountAuthorized($amount);
            $payment->setBaseAmountAuthorized($baseAmount);
            $payment->setParentTransactionId($pagbankPayId);
            $payment->registerAuthorizationNotification($amount);
            $payment->registerCaptureNotification($amount);
            $payment->setIsTransactionApproved(true);
            $payment->setIsTransactionDenied(false);
            $payment->setIsInProcess(true);
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);


        } finally {
            if (isset($response['lock_name']) && $response['lock_name']) {
                $this->lockManager->unlock($response['lock_name']);
            }
        }
    }
}
