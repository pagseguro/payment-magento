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
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

/**
 * Fetch Payment Handler - Payment query response flow.
 */
class FetchPaymentHandler implements HandlerInterface
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
     * Response Pay Qr Codes - Block name.
     */
    public const RESPONSE_QR_CODES = 'qr_codes';

    /**
     * Response Pay Charges - Block Name.
     */
    public const RESPONSE_CHARGES = 'charges';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Paid - Value.
     */
    public const RESPONSE_STATUS_PAID = 'PAID';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_DENIED = 'DENIED';

    /**
     * Response Pay Status Declined - Value.
     */
    public const RESPONSE_STATUS_DECLINED = 'DECLINED';

    /**
     * Response Pay Authorized - Block name.
     */
    public const RESPONSE_AUTHORIZED = 'AUTHORIZED';

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @param InvoiceSender $invoiceSender
     */
    public function __construct(
        InvoiceSender $invoiceSender
    ) {
        $this->invoiceSender = $invoiceSender;
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

        if ($response[self::RESULT_CODE]) {
            $paymentDO = $handlingSubject['payment'];

            $payment = $paymentDO->getPayment();

            $order = $payment->getOrder();

            $amount = $order->getBaseGrandTotal();

            $order = $payment->getOrder();

            if (isset($response[self::RESPONSE_CHARGES])) {
                $charges = $response[self::RESPONSE_CHARGES][0];
                $pagbankPayId = $charges[self::RESPONSE_PAGBANK_ID];
                $paymentParentId = $pagbankPayId;

                if (isset($response[self::RESPONSE_QR_CODES])) {
                    $qrCodes = $response[self::RESPONSE_QR_CODES][0];
                    $paymentParentId = $qrCodes[self::RESPONSE_PAGBANK_ID];
                }

                if ($charges[self::RESPONSE_STATUS] === self::RESPONSE_AUTHORIZED) {
                    $payment->setIsTransactionApproved(false);
                    $payment->setIsTransactionDenied(false);
                    $payment->setIsInProcess(false);
                    $order->setStatus('payment_review');
                    $comment = __('Awaiting payment review.');
                    $order->addStatusHistoryComment($comment, $payment->getOrder()->getStatus());
                }

                if ($charges[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_PAID) {
                    $charges = $response[self::RESPONSE_CHARGES][0];
                    $pagbankPayId = $charges[self::RESPONSE_PAGBANK_ID];
                    $this->setPaymentPay($payment, $paymentParentId, $pagbankPayId, $amount);
                }

                if ($charges[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_DECLINED) {
                    $this->setPaymentDeny($payment, $paymentParentId, $pagbankPayId, $amount);
                }
            }
        }
    }

    /**
     * Set Payment Pay.
     *
     * @param InfoInterface $payment
     * @param string        $paymentParentId
     * @param string        $pagbankPayId
     * @param string        $amount
     *
     * @return void
     */
    public function setPaymentPay($payment, $paymentParentId, $pagbankPayId, $amount)
    {
        $order = $payment->getOrder();
        $payment->setIsInProcess(true);
        $payment->setIsTransactionApproved(true);
        $payment->setIsTransactionDenied(false);
        $payment->setIsTransactionClosed(true);
        if ($order->getState() === 'new' || $order->getState() === 'payment_review') {
            $payment->setTransactionId($pagbankPayId.'-capture');
            $payment->setParentTransactionId($paymentParentId);
            $payment->registerAuthorizationNotification($amount);
            $payment->registerCaptureNotification($amount);
            $payment->setShouldCloseParentTransaction(true);
            $payment->setAmountAuthorized($amount);
            $invoice = $payment->getCreatedInvoice();
            if ($invoice && !$invoice->getEmailSent()) {
                $this->invoiceSender->send($invoice, false);
            }
        }
    }

    /**
     * Set Payment Deny.
     *
     * @param InfoInterface $payment
     * @param string        $paymentParentId
     * @param string        $pagbankPayId
     * @param string        $amount
     *
     * @return void
     */
    public function setPaymentDeny($payment, $paymentParentId, $pagbankPayId, $amount)
    {
        $payment->setPreparedMessage(__('Order Canceled.'));
        $payment->setTransactionId($pagbankPayId.'-void');
        $payment->setParentTransactionId($paymentParentId);
        $payment->registerVoidNotification($amount);
        $payment->setIsTransactionApproved(false);
        $payment->setIsTransactionDenied(true);
        $payment->setIsTransactionPending(false);
        $payment->setIsInProcess(true);
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        $payment->setAmountCanceled($amount);
        $payment->setBaseAmountCanceled($amount);
    }
}
