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
use Magento\Payment\Model\InfoInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;

/**
 * Txn Data Datail Cc Handler - Reply Flow for Cc data.
 */
class TxnDetailDataCcHandler implements HandlerInterface
{
    /**
     * Cc Authorization Code - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_AUTHORIZATION_CODE = 'cc_authorization_code';

    /**
     *  Cc NSU - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_NSU = 'cc_nsu';

    /**
     * Response Pay Charges - Block name.
     */
    public const RESPONSE_CHARGES = 'charges';

    /**
     * Response Pay Payment Response - Block Name.
     */
    public const RESPONSE_PAYMENT_RESPONSE = 'payment_response';

    /**
     * Response Raw Data - Block name.
     */
    public const RAW_DATA = 'raw_data';

    /**
     * Response Authorization Code - Block name.
     */
    public const RESPONSE_AUTHORIZATION_CODE = 'authorization_code';

    /**
     * Response Pay Payment Method - Block name.
     */
    public const RESPONSE_NSU = 'nsu';

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Json     $json
     * @param Config   $config
     */
    public function __construct(
        Json $json,
        Config $config
    ) {
        $this->json = $json;
        $this->config = $config;
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

        $charges = $response[self::RESPONSE_CHARGES][0];

        $paymentResponse = $charges[self::RESPONSE_PAYMENT_RESPONSE];

        if (isset($paymentResponse)) {
            /** Set Addtional Information */
            $this->setAdditionalInfo($payment, $paymentResponse);
        }
    }

    /**
     * Set Additional Info.
     *
     * @param InfoInterface $payment
     * @param array         $paymentResponse
     *
     * @return void
     */
    public function setAdditionalInfo($payment, $paymentResponse)
    {
        $data = $paymentResponse[self::RAW_DATA];

        if (isset($data[self::RESPONSE_AUTHORIZATION_CODE])) {
            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_CC_AUTHORIZATION_CODE,
                $data[self::RESPONSE_AUTHORIZATION_CODE]
            );
        }

        if (isset($data[self::RESPONSE_NSU])) {
            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_CC_NSU,
                $data[self::RESPONSE_NSU]
            );
        }
    }
}
