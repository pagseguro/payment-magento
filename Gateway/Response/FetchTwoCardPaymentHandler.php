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

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use PagBank\PaymentMagento\Gateway\Config\Config;

/**
 * Fetch Two Card Payment Handler - Payment query response flow for two card payments.
 */
class FetchTwoCardPaymentHandler implements HandlerInterface
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
     * Response Pay Charges - Block Name.
     */
    public const RESPONSE_CHARGES = 'charges';

    /**
     * Response Charge Id - Block Name.
     */
    public const RESPONSE_CHARGE_ID = 'id';

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
     * Response Pay Status Canceled - Value.
     */
    public const RESPONSE_STATUS_CANCELED = 'CANCELED';

    /**
     * Response Pay Status Waiting - Value.
     */
    public const RESPONSE_STATUS_WAITING = 'WAITING';

    /**
     * Response Pay Authorized - Block name.
     */
    public const RESPONSE_AUTHORIZED = 'AUTHORIZED';

    /**
     * Response Amount - Block name.
     */
    public const RESPONSE_AMOUNT = 'amount';

    /**
     * Response Amount Value - Block name.
     */
    public const RESPONSE_AMOUNT_VALUE = 'value';

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param InvoiceSender $invoiceSender
     * @param Config        $config
     */
    public function __construct(
        InvoiceSender $invoiceSender,
        Config $config
    ) {
        $this->invoiceSender = $invoiceSender;
        $this->config = $config;
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
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        if (!$response[self::RESULT_CODE] || !isset($response[self::RESPONSE_CHARGES])) {
            return;
        }

        $charges = $response[self::RESPONSE_CHARGES];
        if (count($charges) !== 2) {
            return;
        }

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();
        $finalStatus = $this->determineFinalStatus($charges);

        switch ($finalStatus) {
            case self::RESPONSE_STATUS_PAID:
                $this->processPaymentPaid($payment, $charges, $order->getBaseGrandTotal());
                break;
            case self::RESPONSE_AUTHORIZED:
                $this->processPaymentAuthorized($payment);
                break;
            case self::RESPONSE_STATUS_WAITING:
                $this->processPaymentWaiting($payment, $charges);
                break;
            case self::RESPONSE_STATUS_CANCELED:
            case self::RESPONSE_STATUS_DENIED:
            case self::RESPONSE_STATUS_DECLINED:
                $this->processPaymentDenied($payment, $charges, $order->getBaseGrandTotal());
                break;
        }
    }

    /**
     * Determine final status from synchronized charges.
     *
     * @param array $charges
     * @return string
     */
    protected function determineFinalStatus(array $charges): string
    {
        $firstStatus = $charges[0][self::RESPONSE_STATUS] ?? '';
        $secondStatus = $charges[1][self::RESPONSE_STATUS] ?? '';

        // After sync, both charges should have same or compatible status
        if ($firstStatus === $secondStatus) {
            return $firstStatus;
        }

        // If mixed AUTH and WAITING, return WAITING
        if (($firstStatus === self::RESPONSE_AUTHORIZED && $secondStatus === self::RESPONSE_STATUS_WAITING) ||
            ($firstStatus === self::RESPONSE_STATUS_WAITING && $secondStatus === self::RESPONSE_AUTHORIZED)) {
            return self::RESPONSE_STATUS_WAITING;
        }

        // Default to first charge status
        return $firstStatus;
    }

    /**
     * Process payment paid.
     *
     * @param InfoInterface $payment
     * @param array         $charges
     * @param float         $amount
     * @return void
     * 
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function processPaymentPaid(InfoInterface $payment, array $charges, float $amount): void
    {
        $order = $payment->getOrder();
        $baseAmount = $order->getBaseGrandTotal();
        
        if ($order->getState() === Order::STATE_NEW || $order->getState() === Order::STATE_PAYMENT_REVIEW) {
            // Process each charge
            foreach ($charges as $index => $charge) {
                $chargeId = $charge[self::RESPONSE_CHARGE_ID] ?? '';
                if (!$chargeId) {
                    continue;
                }
                
                $transactionId = $chargeId . '-capture';
                
                // Check if transaction already exists
                if (!$payment->getTransaction($transactionId)) {
                    // Set transaction for this charge
                    $payment->setTransactionId($transactionId);
                    $payment->setParentTransactionId($chargeId);
                    
                    // Register notifications for first charge only to avoid duplication
                    if ($index === 0) {
                        $payment->registerAuthorizationNotification($amount);
                        $payment->registerCaptureNotification($amount);
                        $payment->setIsTransactionApproved(true);
                        $payment->setIsTransactionDenied(false);
                        $payment->setIsInProcess(true);
                        $payment->setAmountAuthorized($amount);
                        $payment->setBaseAmountAuthorized($baseAmount);
                    } else {
                        // Add transaction manually for second charge
                        $payment->setIsTransactionClosed(true);
                        $payment->setShouldCloseParentTransaction(true);
                        $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
                    }
                }
            }
            
            // Set final transaction states
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
            
            $invoice = $payment->getCreatedInvoice();
            if ($invoice && !$invoice->getEmailSent()) {
                $this->invoiceSender->send($invoice, false);
            }
            
            $comment = __('Payment confirmed for two credit cards.');
            $order->addStatusHistoryComment($comment);
            $order->save();
        }
    }

    /**
     * Process payment authorized.
     *
     * @param InfoInterface $payment
     * @return void
     */
    protected function processPaymentAuthorized(InfoInterface $payment): void
    {
        $order = $payment->getOrder();
        
        if ($order->getState() !== Order::STATE_PAYMENT_REVIEW) {

            $order->setState(Order::STATE_PAYMENT_REVIEW)
                  ->setStatus('payment_review');
            
            $comment = __('Payment authorized for two credit cards. Awaiting capture.');
            $order->addStatusHistoryComment($comment);
            $order->save();
        }
    }

    /**
     * Process payment waiting.
     *
     * @param InfoInterface $payment
     * @param array         $charges
     * @return void
     */
    protected function processPaymentWaiting(InfoInterface $payment, array $charges): void
    {
        $order = $payment->getOrder();
        
        // Process each charge
        foreach ($charges as $charge) {
            $chargeId = $charge[self::RESPONSE_CHARGE_ID] ?? '';
            if (!$chargeId) {
                continue;
            }
            
            if (!$payment->getTransaction($chargeId)) {
                $payment->setTransactionId($chargeId);
                $payment->setIsTransactionPending(true);
                $payment->setIsTransactionClosed(false);
                $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);
            }
        }
        
        $order->setState(Order::STATE_PENDING_PAYMENT)
              ->setStatus('pending_payment');
        
        $comment = __('Awaiting payment confirmation for two credit cards.');
        $order->addStatusHistoryComment($comment);
        $order->save();
    }

    /**
     * Process payment denied.
     *
     * @param InfoInterface $payment
     * @param array         $charges
     * @param float         $amount
     * @return void
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function processPaymentDenied(InfoInterface $payment, array $charges, float $amount): void
    {
        $order = $payment->getOrder();
        $baseAmount = $order->getBaseGrandTotal();
        
        // Process each charge
        foreach ($charges as $index => $charge) {
            $chargeId = $charge[self::RESPONSE_CHARGE_ID] ?? '';
            if (!$chargeId) {
                continue;
            }
            
            $transactionId = $chargeId . '-void';
            
            // Check if transaction already exists
            if (!$payment->getTransaction($transactionId)) {
                $payment->setTransactionId($transactionId);
                $payment->setParentTransactionId($chargeId);

                // Register void for first charge only to avoid duplication
                if ($index === 0) {
                    $payment->registerVoidNotification($amount);
                    $payment->setIsTransactionApproved(false);
                    $payment->setIsTransactionDenied(true);
                    $payment->setIsInProcess(false);
                    $payment->setAmountCanceled($amount);
                    $payment->setBaseAmountCanceled($baseAmount);
                } else {
                    // Add transaction manually for second charge
                    $payment->setIsTransactionClosed(true);
                    $payment->setShouldCloseParentTransaction(true);
                    $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID);
                }
            }
        }
        
        // Set final transaction states
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        
        $order->cancel();
        
        $comment = __('Payment denied for two credit cards.');
        $order->addStatusHistoryComment($comment);
        $order->save();
    }
}