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
     * Cc NSU - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_NSU = 'cc_nsu';

    /**
     * Three DS Auth Status - Payment Additional Information.
     */
    public const PAYMENT_INFO_THREE_DS_AUTH_STATUS = 'three_ds_auth_status';

    /**
     * Cc Issuer Name - Payment Additional Information.
     */
    public const PAYMENT_INFO_CC_ISSUER_NAME = 'cc_issuer_name';

    /**
     * Cc Issuer Product - Payment Additional Information.
     */
    public const PAYMENT_INFO_CC_ISSUER_PRODUCT = 'cc_issuer_product';

    /**
     * Response Pay Charges - Block name.
     */
    public const RESPONSE_CHARGES = 'charges';

    /**
     * Response Pay Payment Response - Block Name.
     */
    public const RESPONSE_PAYMENT_RESPONSE = 'payment_response';

    /**
     * Response Pay Payment Method - Block Name.
     */
    public const RESPONSE_PAYMENT_METHOD = 'payment_method';

    /**
     * Response Authentication Method - Block Name.
     */
    public const RESPONSE_AUTHENTICATION_METHOD = 'authentication_method';

    /**
     * Response Authentication Method Status - Block Name.
     */
    public const RESPONSE_AUTH_METHOD_STATUS = 'status';

    /**
     * Response Card - Block Name.
     */
    public const RESPONSE_CARD = 'card';

    /**
     * Response Issuer - Block Name.
     */
    public const RESPONSE_ISSUER = 'issuer';

    /**
     * Response Issuer Name - Block Name.
     */
    public const RESPONSE_ISSUER_NAME = 'name';

    /**
     * Response Issuer Product - Block Name.
     */
    public const RESPONSE_ISSUER_PRODUCT = 'product';

    /**
     * Response Raw Data - Block name.
     */
    public const RAW_DATA = 'raw_data';

    /**
     * Response Authorization Code - Block name.
     */
    public const RESPONSE_AUTHORIZATION_CODE = 'authorization_code';

    /**
     * Response NSU - Block name.
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
     * @param Json   $json
     * @param Config $config
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
        $paymentResponse = $charges[self::RESPONSE_PAYMENT_RESPONSE] ?? null;
        $paymentMethod = $charges[self::RESPONSE_PAYMENT_METHOD] ?? null;

        if ($paymentResponse) {
            $this->setAdditionalInfo($payment, $paymentResponse);
        }

        if ($paymentMethod) {
            $this->setPaymentMethodInfo($payment, $paymentMethod);
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
        $data = $paymentResponse[self::RAW_DATA] ?? [];

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

    /**
     * Set Payment Method Info.
     *
     * @param InfoInterface $payment
     * @param array         $paymentMethod
     *
     * @return void
     */
    public function setPaymentMethodInfo($payment, $paymentMethod)
    {
        $this->setAuthenticationMethodInfo($payment, $paymentMethod);
        $this->setIssuerInfo($payment, $paymentMethod);
    }

    /**
     * Set Authentication Method Info.
     *
     * @param InfoInterface $payment
     * @param array         $paymentMethod
     *
     * @return void
     */
    private function setAuthenticationMethodInfo($payment, $paymentMethod)
    {
        $authMethod = $paymentMethod[self::RESPONSE_AUTHENTICATION_METHOD] ?? null;

        if ($authMethod && isset($authMethod[self::RESPONSE_AUTH_METHOD_STATUS])) {
            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_THREE_DS_AUTH_STATUS,
                $authMethod[self::RESPONSE_AUTH_METHOD_STATUS]
            );
        }
    }

    /**
     * Set Issuer Info.
     *
     * @param InfoInterface $payment
     * @param array         $paymentMethod
     *
     * @return void
     */
    private function setIssuerInfo($payment, $paymentMethod)
    {
        $card = $paymentMethod[self::RESPONSE_CARD] ?? null;

        if (!$card) {
            return;
        }

        $issuer = $card[self::RESPONSE_ISSUER] ?? null;

        if (!$issuer) {
            return;
        }

        if (isset($issuer[self::RESPONSE_ISSUER_NAME])) {
            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_CC_ISSUER_NAME,
                $issuer[self::RESPONSE_ISSUER_NAME]
            );
        }

        if (isset($issuer[self::RESPONSE_ISSUER_PRODUCT])) {
            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_CC_ISSUER_PRODUCT,
                $issuer[self::RESPONSE_ISSUER_PRODUCT]
            );
        }
    }
}
