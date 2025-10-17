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
 * Class Fetch Two Card Payment Handler - Reply Flow for Fetch Two Cards.
 */
class FetchTwoCardPaymentHandler implements HandlerInterface
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
     * @SuppressWarnings(PHPMD.NPathComplexity)
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

        if (!isset($response[self::RESPONSE_CHARGES])) {
            return;
        }

        $charges = $response[self::RESPONSE_CHARGES];
        
        if (count($charges) !== 2) {
            return;
        }

        $this->findForPaymentStatus($charges);

        if ($this->finalStatus === 'PAID') {
            $this->processPaymentPaid($payment, $charges);
        }

        if ($this->finalStatus === 'AUTH') {
            $this->processPaymentAuthorized($payment);
        }

        if ($this->finalStatus === 'CANCEL') {
            $this->processPaymentCanceled($payment, $charges);
        }

        if ($this->finalStatus === 'WAITING') {
            $this->processPaymentWaiting($payment);
        }
    }

    /**
     * Find for Payment Status.
     *
     * @param array $charges
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function findForPaymentStatus($charges)
    {
        $statusPriority = [
            self::RESPONSE_STATUS_PAID => 4,
            self::RESPONSE_AUTHORIZED => 3,
            self::RESPONSE_STATUS_WAITING => 2,
            self::RESPONSE_STATUS_CANCELED => 1,
            self::RESPONSE_STATUS_DECLINED => 1,
        ];

        $highestPriority = 0;
        $this->finalStatus = null;

        foreach ($charges as $charge) {
            $status = $charge[self::RESPONSE_STATUS];
            $priority = $statusPriority[$status] ?? 0;

            if ($priority > $highestPriority) {
                $highestPriority = $priority;
                
                switch ($status) {
                    case self::RESPONSE_STATUS_PAID:
                        $this->finalStatus = 'PAID';
                        break;
                    case self::RESPONSE_AUTHORIZED:
                        $this->finalStatus = 'AUTH';
                        break;
                    case self::RESPONSE_STATUS_WAITING:
                        $this->finalStatus = 'WAITING';
                        break;
                    case self::RESPONSE_STATUS_CANCELED:
                    case self::RESPONSE_STATUS_DECLINED:
                        $this->finalStatus = 'CANCEL';
                        break;
                }
            }
        }
    }

    /**
     * Process payment paid.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $charges
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @return void
     */
    protected function processPaymentPaid($payment, $charges)
    {
        $order = $payment->getOrder();
        
        if ($order->getState() !== 'new' && $order->getState() !== 'payment_review') {
            return;
        }
        
        if ($order->hasInvoices()) {
            return;
        }
        
        $amount = $order->getBaseGrandTotal();
        $baseAmount = $order->getBaseGrandTotal();
        $hasProcessedFirst = false;

        foreach ($charges as $charge) {
            $chargeId = $charge[self::RESPONSE_PAGBANK_ID] ?? '';
            if (!$chargeId) {
                continue;
            }
            
            $transactionId = $chargeId . '-capture';
            
            if ($payment->getTransaction($transactionId)) {
                continue;
            }
            
            $payment->setTransactionId($transactionId);
            $payment->setParentTransactionId($chargeId);
            
            if (!$hasProcessedFirst) {
                $payment->setIsTransactionApproved(true);
                $payment->setIsTransactionDenied(false);
                $payment->setIsInProcess(true);
                
                $payment->registerAuthorizationNotification($amount);
                $payment->registerCaptureNotification($amount);
                $payment->setAmountAuthorized($amount);
                $payment->setBaseAmountAuthorized($baseAmount);
                
                $hasProcessedFirst = true;
            } else {
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
                $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
            }
        }
        
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        
        $invoice = $payment->getCreatedInvoice();
        if ($invoice) {
            if (!$invoice->getEmailSent()) {
                $this->invoiceSender->send($invoice, false);
            }
            
            $order->setState(Order::STATE_PROCESSING)
                  ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
                  ->addStatusHistoryComment(__('Payment confirmed for two credit cards.'));
        }
        
        $order->save();
    }

    /**
     * Process payment authorized.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return void
     */
    protected function processPaymentAuthorized($payment)
    {
        $order = $payment->getOrder();
        
        if ($order->getState() === Order::STATE_PAYMENT_REVIEW) {
            return;
        }

        $order->setState(Order::STATE_PAYMENT_REVIEW)
              ->setStatus('payment_review');
        
        $comment = __('Payment authorized for two credit cards. Awaiting capture.');
        $order->addStatusHistoryComment($comment);
        $order->save();
    }

    /**
     * Process payment waiting.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return void
     */
    protected function processPaymentWaiting($payment)
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
        
        $comment = __('Awaiting payment for two credit cards.');
        $order->addStatusHistoryComment($comment, $payment->getOrder()->getStatus());
        $order->save();
    }

    /**
     * Process payment canceled.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $charges
     * @SuppressWarnings(PHPMD.ElseExpression)
     * 
     * @return void
     */
    protected function processPaymentCanceled($payment, $charges)
    {
        $order = $payment->getOrder();
        
        if ($order->getState() === Order::STATE_CANCELED) {
            return;
        }
        
        $amount = $order->getBaseGrandTotal();
        $hasProcessedFirst = false;
        
        foreach ($charges as $charge) {
            $chargeId = $charge[self::RESPONSE_PAGBANK_ID] ?? '';
            if (!$chargeId) {
                continue;
            }
            
            $voidTransactionId = $chargeId . '-void';
            
            if ($payment->getTransaction($voidTransactionId)) {
                continue;
            }
            
            $payment->setTransactionId($voidTransactionId);
            $payment->setParentTransactionId($chargeId);
            
            if (!$hasProcessedFirst) {
                $payment->setPreparedMessage(__('Order Canceled - Two Cards.'));
                $payment->setIsTransactionApproved(false);
                $payment->setIsTransactionDenied(true);
                $payment->setIsTransactionPending(false);
                $payment->setIsInProcess(false);
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
                
                $payment->registerVoidNotification($amount);
                $payment->setAmountCanceled($amount);
                $payment->setBaseAmountCanceled($amount);
                
                $hasProcessedFirst = true;
            } else {
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
                $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID);
            }
        }
        
        $order->registerCancellation(__('Payment denied by PagBank for two credit cards.'), false);
        $order->save();
    }
}