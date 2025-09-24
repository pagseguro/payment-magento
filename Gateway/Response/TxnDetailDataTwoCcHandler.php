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
 * Txn Data Detail Two Cc Handler - Reply Flow for Two Cc data.
 */
class TxnDetailDataTwoCcHandler implements HandlerInterface
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
     * Cc Amount - Payment Additional Information.
     */
    public const PAYMENT_INFO_CC_AMOUNT = 'cc_amount';

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
     * Response Amount - Block name.
     */
    public const RESPONSE_AMOUNT = 'amount';

    /**
     * Response Amount Value - Block name.
     */
    public const RESPONSE_AMOUNT_VALUE = 'value';

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
        
        $charges = $response[self::RESPONSE_CHARGES];
        
        // Process both credit cards
        $this->setAdditionalInfoForTwoCards($payment, $charges);
    }

    /**
     * Set Additional Info for Two Cards.
     *
     * @param InfoInterface $payment
     * @param array         $charges
     *
     * @return void
     */
    public function setAdditionalInfoForTwoCards($payment, $charges)
    {
        $prefixes = ['first', 'second'];
        
        // Process each charge
        foreach ($charges as $index => $charge) {
            if ($index > 1) {
                continue;
            }
            
            $prefix = $prefixes[$index];
            
            // Process amount
            if (isset($charge[self::RESPONSE_AMOUNT][self::RESPONSE_AMOUNT_VALUE])) {
                $amount = $charge[self::RESPONSE_AMOUNT][self::RESPONSE_AMOUNT_VALUE] / 100; // Convert from cents
                $payment->setAdditionalInformation(
                    $prefix . '_' . self::PAYMENT_INFO_CC_AMOUNT,
                    $amount
                );
            }
            
            // Process payment response data
            if (!isset($charge[self::RESPONSE_PAYMENT_RESPONSE])) {
                continue;
            }
            
            $paymentResponse = $charge[self::RESPONSE_PAYMENT_RESPONSE];
            
            if (!isset($paymentResponse[self::RAW_DATA])) {
                continue;
            }
            
            $data = $paymentResponse[self::RAW_DATA];
            
            // Authorization Code
            if (isset($data[self::RESPONSE_AUTHORIZATION_CODE])) {
                $payment->setAdditionalInformation(
                    $prefix . '_' . self::PAYMENT_INFO_CC_AUTHORIZATION_CODE,
                    $data[self::RESPONSE_AUTHORIZATION_CODE]
                );
            }
            
            // NSU
            if (isset($data[self::RESPONSE_NSU])) {
                $payment->setAdditionalInformation(
                    $prefix . '_' . self::PAYMENT_INFO_CC_NSU,
                    $data[self::RESPONSE_NSU]
                );
            }
        }
    }
}
