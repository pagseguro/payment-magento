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
use PagBank\PaymentMagento\Gateway\Config\ConfigCc;

/**
 * Txn Data Cc Handler - Reply Flow for Cc data.
 */
class TxnDataCcHandler implements HandlerInterface
{
    /**
     * Cc Installments - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_INSTALLMENTS = 'cc_installments';

    /**
     * Cc Type - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_TYPE = 'cc_type';

    /**
     * Cc Number - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_NUMBER = 'cc_number';

    /**
     * Cc Owner - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_OWNER = 'cc_cardholder_name';

    /**
     * Cc Exp Month - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_EXP_MONTH = 'cc_exp_month';

    /**
     * Cc Exp Year - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_EXP_YEAR = 'cc_exp_year';

    /**
     *  Cc Number Token - Payment Addtional Information.
     */
    public const PAYMENT_INFO_NUMBER_TOKEN = 'cc_number_token';

    /**
     * Response Pay Charges - Block name.
     */
    public const RESPONSE_CHARGES = 'charges';

    /**
     * Response Pay Credit - Block name.
     */
    public const CREDIT = 'credit';

    /**
     * Response Pay PagBank Id - Block name.
     */
    public const RESPONSE_PAGBANK_ID = 'id';

    /**
     * Response Pay Payment Method - Block name.
     */
    public const RESPONSE_PAYMENT_METHOD = 'payment_method';

    /**
     * Response Pay Payment Method Card - Block name.
     */
    public const RESPONSE_PAYMENT_METHOD_CARD = 'card';

    /**
     * Response Pay Payment Method Installments - Block name.
     */
    public const RESPONSE_PM_INSTALLMENTS = 'installments';

    /**
     * Response Pay Payment Method Card Brand - Block name.
     */
    public const RESPONSE_PM_CARD_BRAND = 'brand';

    /**
     * Response Pay Payment Method Card First Digits - Block name.
     */
    public const RESPONSE_PM_CARD_FIRST_DIGITS = 'first_digits';

    /**
     * Response Pay Payment Method Card Last Digits - Block name.
     */
    public const RESPONSE_PM_CARD_LAST_DIGITS = 'last_digits';

    /**
     * Response Pay Payment Method Card Exp Month - Block name.
     */
    public const RESPONSE_PM_CARD_EXP_MONTH = 'exp_month';

    /**
     * Response Pay Payment Method Card Exp Year - Block name.
     */
    public const RESPONSE_PM_CARD_EXP_YEAR = 'exp_year';

    /**
     * Response Pay Payment Method Card Holder - Block name.
     */
    public const RESPONSE_PM_CARD_HOLDER = 'holder';

    /**
     * Response Pay Payment Method Card Holder Name - Block name.
     */
    public const RESPONSE_PM_CARD_HOLDER_NAME = 'name';

    /**
     * Response Pay Status - Block name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Approved - Block name.
     */
    public const RESPONSE_APPROVED = 'APPROVED';

    /**
     * Response Pay Authorized - Block name.
     */
    public const RESPONSE_AUTHORIZED = 'AUTHORIZED';

    /**
     * Response Pay Pending - Block name.
     */
    public const RESPONSE_PENDING = 'PENDING';

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

        $charges = $response[self::RESPONSE_CHARGES][0];

        $paymentMethod = $charges[self::RESPONSE_PAYMENT_METHOD];

        $cardData = $paymentMethod[self::RESPONSE_PAYMENT_METHOD_CARD];

        /** Set Addtional Information */
        $this->setAdditionalInfo($payment, $paymentMethod, $cardData);
    }

    /**
     * Set Additional Info.
     *
     * @param InfoInterface $payment
     * @param array         $paymentMethod
     * @param array         $cardData
     *
     * @return void
     */
    public function setAdditionalInfo($payment, $paymentMethod, $cardData)
    {
        $ccType = null;

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_CC_INSTALLMENTS,
            $paymentMethod[self::RESPONSE_PM_INSTALLMENTS]
        );

        $ccType = $cardData[self::RESPONSE_PM_CARD_BRAND];
        if ($ccType) {
            $ccType = $this->getCreditCardType($ccType);
        }
        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_CC_TYPE,
            $ccType
        );
        $payment->setCcType($ccType);

        $ccFirst = $cardData[self::RESPONSE_PM_CARD_FIRST_DIGITS];
        $ccLast4 = $cardData[self::RESPONSE_PM_CARD_LAST_DIGITS];

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_CC_NUMBER,
            $ccFirst.'xxxxxx'.$ccLast4
        );
        $payment->setCcLast4($ccLast4);

        $ccHolder = $cardData[self::RESPONSE_PM_CARD_HOLDER];
        $ccHolderName = $ccHolder[self::RESPONSE_PM_CARD_HOLDER_NAME];
        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_CC_OWNER,
            $ccHolderName
        );
        $payment->setCcOwner($ccHolderName);

        $ccExpMonth = $cardData[self::RESPONSE_PM_CARD_EXP_MONTH];
        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_CC_EXP_MONTH,
            $ccExpMonth
        );
        $payment->setCcExpMonth($ccExpMonth);

        $ccExpYear = $cardData[self::RESPONSE_PM_CARD_EXP_YEAR];
        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_CC_EXP_YEAR,
            $ccExpYear
        );
        $payment->setCcExpYear($ccExpMonth);

        $ccNumberEnc = $payment->getAdditionalInformation(self::PAYMENT_INFO_NUMBER_TOKEN);
        $payment->setCcNumberEnc($ccNumberEnc);
    }

    /**
     * Get type of credit card mapped from PagBank.
     *
     * @param string $type
     *
     * @return string
     */
    public function getCreditCardType(string $type): ?string
    {
        $type = strtoupper($type);
        $mapper = $this->configCc->getCcTypesMapper();

        return $mapper[$type] ?: $type;
    }
}
