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
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

/**
 * Class Fetch Payment Handler - Reply Flow for Fetch.
 */
class FetchPaymentHandler implements HandlerInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Response Pay Charges - Block name.
     */
    public const RESPONSE_CHARGES = 'charges';

    /**
     * Response Pay PagBank Id - Block Name.
     */
    public const RESPONSE_PAGBANK_ID = 'id';

    /**
     * Response Pay Status - Block name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Paid - Value.
     */
    public const RESPONSE_STATUS_PAID = 'PAID';

    /**
     * Response Pay Status Canceled - Value.
     */
    public const RESPONSE_STATUS_CANCELED = 'CANCELED';

    /**
     * Response Pay Status Declined - Value.
     */
    public const RESPONSE_STATUS_DECLINED = 'DECLINED';

    /**
     * Response Qr Codes - Block name.
     */
    public const RESPONSE_QR_CODES = 'qr_codes';

    /**
     * Response Pay Status Waiting - Block name.
     */
    public const RESPONSE_STATUS_WAITING = 'WAITING';

    /**
     * Response Pay Authorized - Block name.
     */
    public const RESPONSE_AUTHORIZED = 'AUTHORIZED';

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var string
     */
    protected $finalStatus;

    /**
     * @param InvoiceSender $invoiceSender
     */
    public function __construct(
        InvoiceSender $invoiceSender
    ) {
        $this->invoiceSender = $invoiceSender;
        $this->finalStatus = null;
    }

    /**
     * Handles.
     *
     * @param array $handlingSubject
     * @param array $response
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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

        if (!$response[self::RESULT_CODE]) {
            return;
        }

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();
        $amount = $order->getBaseGrandTotal();

        if (isset($response[self::RESPONSE_CHARGES])) {
            $charges = $response[self::RESPONSE_CHARGES];
            $pagbankPayId = $charges[0][self::RESPONSE_PAGBANK_ID];
            $paymentParentId = $pagbankPayId;

            if (isset($response[self::RESPONSE_QR_CODES])) {
                $paymentParentId = $response[self::RESPONSE_QR_CODES][0][self::RESPONSE_PAGBANK_ID];
            }

            $this->findForPaymentStatus($response, $charges);

            if ($this->finalStatus === 'PAID') {
                $this->setPaymentPay($payment, $paymentParentId, $pagbankPayId, $amount);
            }

            if ($this->finalStatus === 'AUTH') {
                $this->setPaymentAuth($payment);
            }

            if ($this->finalStatus === 'CANCEL') {
                $this->setPaymentDeny($payment, $paymentParentId, $pagbankPayId, $amount);
            }

            if ($this->finalStatus === 'WAITING') {
                $this->setPaymentWaiting($payment);
            }
        }
    }

    /**
     * Find for Payment Status.
     *
     * @param array $response
     * @param array $charges
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function findForPaymentStatus($response, $charges)
    {
        $isPix = isset($response[self::RESPONSE_QR_CODES]);
        $isTempCancel = false;

        foreach ($charges as $charge) {
            switch ($charge[self::RESPONSE_STATUS]) {
                case self::RESPONSE_STATUS_WAITING:
                    $this->finalStatus = 'WAITING';
                    break;

                case self::RESPONSE_AUTHORIZED:
                    $this->finalStatus = 'AUTH';
                    break;

                case self::RESPONSE_STATUS_PAID:
                    $this->finalStatus = 'PAID';
                    break;

                case self::RESPONSE_STATUS_CANCELED:
                case self::RESPONSE_STATUS_DECLINED:
                    if ($isPix) {
                        $isTempCancel = $charge['amount']['summary']['paid'] === 0 ? 1 : 0;
                    }

                    if (!$isTempCancel && !$this->finalStatus) {
                        $this->finalStatus = 'CANCEL';
                    }
                    break;
            }
        }
    }

    /**
     * Set Payment Waiting.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     *
     * @return void
     */
    public function setPaymentWaiting($payment)
    {
        $order = $payment->getOrder();
        
        if ($order->getState() === Order::STATE_PAYMENT_REVIEW 
            || $order->getState() === Order::STATE_PROCESSING
        ) {
            return;
        }
        
        $payment->setIsTransactionApproved(false);
        $payment->setIsTransactionDenied(false);
        $payment->setIsTransactionPending(true);
        $payment->setIsInProcess(false);
        $payment->setIsTransactionClosed(false);
        
        $comment = __('Awaiting payment.');
        $order->addStatusHistoryComment($comment, $payment->getOrder()->getStatus());
        $order->save();
    }

    /**
     * Set Payment Auth.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     *
     * @return void
     */
    public function setPaymentAuth($payment)
    {
        $order = $payment->getOrder();

        if ($order->getState() === Order::STATE_PAYMENT_REVIEW) {
            return;
        }
        
        $payment->setIsTransactionApproved(false);
        $payment->setIsTransactionDenied(false);
        $payment->setIsInProcess(false);
        
        $order->setState(Order::STATE_PAYMENT_REVIEW)
              ->setStatus('payment_review');
        
        $comment = __('Awaiting payment review.');
        $order->addStatusHistoryComment($comment);
        $order->save();
    }

    /**
     * Set Payment Pay.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $paymentParentId
     * @param string $pagbankPayId
     * @param string $amount
     *
     * @return void
     */
    public function setPaymentPay($payment, $paymentParentId, $pagbankPayId, $amount)
    {
        $order = $payment->getOrder();
        
        if ($order->getState() !== 'new' && $order->getState() !== 'payment_review') {
            return;
        }
        
        $captureTransactionId = $pagbankPayId . '-capture';
        
        if ($payment->getTransaction($captureTransactionId)) {
            return;
        }
        
        if ($order->hasInvoices()) {
            return;
        }
        
        $payment->setTransactionId($captureTransactionId);
        $payment->setParentTransactionId($paymentParentId);
        $payment->setIsTransactionApproved(true);
        $payment->setIsTransactionDenied(false);
        $payment->setIsInProcess(true);
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        
        // $payment->registerAuthorizationNotification($amount);
        $payment->registerCaptureNotification($amount);
        $payment->setAmountAuthorized($amount);
        
        $invoice = $payment->getCreatedInvoice();
        if ($invoice) {
            if (!$invoice->getEmailSent()) {
                $this->invoiceSender->send($invoice, false);
            }
            
            $order->setState(Order::STATE_PROCESSING)
                  ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
                  ->addStatusHistoryComment(__('Payment confirmed by PagBank.'));
        }
        
        $order->save();
    }

    /**
     * Set Payment Deny.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $paymentParentId
     * @param string $pagbankPayId
     * @param string $amount
     *
     * @return void
     */
    public function setPaymentDeny($payment, $paymentParentId, $pagbankPayId, $amount)
    {
        $order = $payment->getOrder();
        
        if ($order->getState() === Order::STATE_CANCELED) {
            return;
        }
        
        $voidTransactionId = $pagbankPayId . '-void';
        
        if ($payment->getTransaction($voidTransactionId)) {
            return;
        }
        
        $payment->setTransactionId($voidTransactionId);
        $payment->setParentTransactionId($paymentParentId);
        $payment->setPreparedMessage(__('Order Canceled.'));
        $payment->setIsTransactionApproved(false);
        $payment->setIsTransactionDenied(true);
        $payment->setIsTransactionPending(false);
        $payment->setIsInProcess(false);
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        
        $payment->registerVoidNotification($amount);
        $payment->setAmountCanceled($amount);
        $payment->setBaseAmountCanceled($amount);
        
        $order->registerCancellation(__('Payment denied by PagBank.'), false);
        $order->save();
    }
}
