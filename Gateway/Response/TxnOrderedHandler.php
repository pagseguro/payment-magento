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
 * Txn Ordered Handler - Reply Flow for Ordered All Methods.
 */
class TxnOrderedHandler implements HandlerInterface
{
    /**
     * Response Pay PAGBANK Id - Block name.
     */
    public const RESPONSE_PAGBANK_ID = 'id';

    /**
     * Response Pay Qr Codes - Block name.
     */
    public const RESPONSE_QR_CODES = 'qr_codes';

    /**
     * Response Pay Charges - Block name.
     */
    public const RESPONSE_CHARGES = 'charges';

    /**
     * Response Pay Status - Block name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Authorized - Block name.
     */
    public const RESPONSE_AUTHORIZED = 'AUTHORIZED';

    /**
     * Response Pay In Analysis - Block name.
     */
    public const RESPONSE_IN_ANALYSIS = 'IN_ANALYSIS';

    /**
     * Response Pay Paid - Block name.
     */
    public const RESPONSE_PAID = 'PAID';

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
        $pagbankPayId = null;

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $pagbankOrderId = $response[self::RESPONSE_PAGBANK_ID];

        if (isset($response[self::RESPONSE_CHARGES])) {
            $charges = $response[self::RESPONSE_CHARGES][0];

            $pagbankPayId = $charges[self::RESPONSE_PAGBANK_ID];
        }

        if (isset($response[self::RESPONSE_QR_CODES])) {
            $qrCodes = $response[self::RESPONSE_QR_CODES][0];

            $pagbankPayId = $qrCodes[self::RESPONSE_PAGBANK_ID];
        }

        /** Create Order */
        $this->createTransactionOrder($payment, $pagbankOrderId);

        /** Create Auth */
        $this->createTransactionAuth($payment, $pagbankOrderId, $pagbankPayId);

        $order = $payment->getOrder();
        $order->setState(Order::STATE_NEW);
        $order->setStatus('pending');
        $comment = __('Awaiting payment.');
        $order->setCustomerNote($comment);
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
        $payment->setTransactionId($pagbankOrderId);
        $payment->setIsTransactionApproved(false);
        $payment->setIsTransactionDenied(false);
        $payment->setIsTransactionPending(true);
        $payment->addTransaction(Transaction::TYPE_ORDER);
    }

    /**
     * Create Transaction Order.
     *
     * @param InfoInterface $payment
     * @param string        $pagbankOrderId
     * @param string        $pagbankPayId
     *
     * @return void
     */
    public function createTransactionAuth(
        $payment,
        $pagbankOrderId,
        $pagbankPayId
    ) {
        $payment->setTransactionId($pagbankPayId);
        $payment->setParentTransactionId($pagbankOrderId);
        $payment->setIsTransactionApproved(false);
        $payment->setIsTransactionDenied(false);
        $payment->setIsTransactionPending(true);
        $payment->setIsTransactionClosed(false);
        $payment->setShouldCloseParentTransaction(true);
        $payment->addTransaction(Transaction::TYPE_AUTH);
    }
}
