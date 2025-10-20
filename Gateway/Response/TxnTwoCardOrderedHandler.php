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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use PagBank\PaymentMagento\Gateway\Config\Config;
use PagBank\PaymentMagento\Gateway\Config\ConfigCc;

/**
 * Txn Two Card Ordered Handler - Reply Flow for Two Card Payment.
 */
class TxnTwoCardOrderedHandler implements HandlerInterface
{
    /**
     * Response Pay PAGBANK Id - Block name.
     */
    public const RESPONSE_PAGBANK_ID = 'id';

    /**
     * Response Pay Charges - Block name.
     */
    public const RESPONSE_CHARGES = 'charges';

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param Json     $json
     * @param Config   $config
     * @param ConfigCc $configCc
     */
    public function __construct(
        Json $json,
        Config $config,
        ConfigCc $configCc
    ) {
        $this->json = $json;
        $this->config = $config;
        $this->configCc = $configCc;
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

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();

        $pagbankOrderId = $response[self::RESPONSE_PAGBANK_ID];

        /** Create Order Transaction */
        $this->createTransactionOrder($payment, $pagbankOrderId);

        /** Create Auth Transactions for both cards */
        $totalAuthorized = 0;
        $baseTotalAuthorized = 0;
        
        if (isset($response[self::RESPONSE_CHARGES])) {
            foreach ($response[self::RESPONSE_CHARGES] as $index => $charge) {
                $pagbankPayId = $charge[self::RESPONSE_PAGBANK_ID];
                
                // Calculate amounts
                $chargeAmount = 0;
                $baseChargeAmount = 0;
                if (isset($charge['amount']['value'])) {
                    $chargeAmount = $charge['amount']['value'] / 100;
                    $totalAuthorized += $chargeAmount;
                    
                    $rate = $order->getBaseToOrderRate() ?: 1;
                    $baseChargeAmount = $chargeAmount / $rate;
                    $baseTotalAuthorized += $baseChargeAmount;
                }
                
                $this->createTransactionAuth($payment, $pagbankOrderId, $pagbankPayId, $charge, $index + 1);
            }
        }

        // Set total authorized amounts
        $payment->setAmountAuthorized($totalAuthorized);
        $payment->setBaseAmountAuthorized($baseTotalAuthorized);

        $order->setState(Order::STATE_NEW)
            ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_NEW));
        $comment = __('Awaiting payment.');
        $order->setCustomerNote($comment);
    }

    /**
     * Create Transaction Auth.
     *
     * @param InfoInterface $payment
     * @param string        $pagbankOrderId
     * @param string        $pagbankPayId
     * @param array         $charge
     * @param int           $cardNumber
     *
     * @return void
     */
    public function createTransactionAuth(
        $payment,
        $pagbankOrderId,
        $pagbankPayId,
        $charge,
        $cardNumber
    ) {
        $existingTransaction = $payment->getTransaction($pagbankPayId);
        if (!$existingTransaction) {
            $payment->setTransactionId($pagbankPayId);
            $payment->setParentTransactionId($pagbankOrderId);
            $payment->setIsTransactionApproved(false);
            $payment->setIsTransactionDenied(false);
            $payment->setIsTransactionPending(true);
            $payment->setIsTransactionClosed(false);
            $payment->setShouldCloseParentTransaction(true);
            
            // Calculate individual amounts
            $chargeAmount = 0;
            if (isset($charge['amount']['value'])) {
                $chargeAmount = $charge['amount']['value'] / 100;
            }
            
            // Set transaction additional info
            $payment->setTransactionAdditionalInfo(
                Transaction::RAW_DETAILS,
                [
                    'card_index_number' => $cardNumber,
                    'amount_authorized' => $chargeAmount,
                    'charge_id' => $pagbankPayId,
                    'status' => $charge['status'] ?? 'PENDING'
                ]
            );
            
            $payment->addTransaction(Transaction::TYPE_AUTH);
        }
    }

    /**
     * Create Transaction Order.
     *
     * @param InfoInterface $payment
     * @param string        $pagbankOrderId
     *
     * @return void
     */
    public function createTransactionOrder($payment, $pagbankOrderId)
    {
        $existingTransaction = $payment->getTransaction($pagbankOrderId);
        if (!$existingTransaction) {
            $payment->setTransactionId($pagbankOrderId);
            $payment->setIsTransactionApproved(false);
            $payment->setIsTransactionDenied(false);
            $payment->setIsTransactionPending(true);
            $payment->addTransaction(Transaction::TYPE_ORDER);
        }
    }
}
