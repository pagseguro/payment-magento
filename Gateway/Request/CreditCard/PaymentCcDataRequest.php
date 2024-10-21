<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Request\CreditCard;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;
use PagBank\PaymentMagento\Gateway\Config\ConfigCc;
use PagBank\PaymentMagento\Gateway\Request\ChargesDataRequest;

/**
 * Class Payment Cc Data Request - Structure of payment for Credit Card.
 */
class PaymentCcDataRequest implements BuilderInterface
{
    /**
     * Payment Method block name.
     */
    public const PAYMENT_METHOD = 'payment_method';

    /**
     * Soft Descriptior - Block name.
     */
    public const SOFT_DESCRIPTOR = 'soft_descriptor';

    /**
     * Type - Block name.
     */
    public const TYPE = 'type';

    /**
     * Type Value - Value.
     */
    public const TYPE_VALUE = 'CREDIT_CARD';

    /**
     * Capture - Block name.
     */
    public const CAPTURE = 'capture';

    /**
     * Installment - Block name.
     */
    public const INSTALLMENTS = 'installments';

    /**
     * Credit card - Block name.
     */
    public const CREDIT_CARD = 'card';

    /**
     * Credit card Number Token - Block Name.
     */
    public const CREDIT_NUMBER_TOKEN = 'encrypted';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param Config   $config
     * @param ConfigCc $configCc
     */
    public function __construct(
        Config $config,
        ConfigCc $configCc
    ) {
        $this->config = $config;
        $this->configCc = $configCc;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function build(array $buildSubject)
    {
        $result = [];

        /** @var PaymentDataObject $paymentDO * */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var InfoInterface $payment * */
        $payment = $paymentDO->getPayment();

        /** @var \Magento\Sales\Model\Order $order * */
        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        $result[ChargesDataRequest::CHARGES][] = [
            self::PAYMENT_METHOD => $this->getDataPaymetCc($payment, $storeId),
        ];

        return $result;
    }

    /**
     * Data for Payment Cc.
     *
     * @param InfoInterface $payment
     * @param int           $storeId
     *
     * @return array
     */
    public function getDataPaymetCc($payment, $storeId)
    {
        $instruction = [];
        $installment = $payment->getAdditionalInformation('cc_installments') ?: 1;
        $typeCard = $payment->getAdditionalInformation('card_type_transaction') ?: self::TYPE_VALUE;

        $instruction = [
            self::TYPE              => $typeCard,
            self::SOFT_DESCRIPTOR   => $this->config->getSoftDescriptor($storeId),
            self::CAPTURE           => $this->configCc->hasCapture($storeId),
            self::INSTALLMENTS      => $installment,
            self::CREDIT_CARD       => [
                self::CREDIT_NUMBER_TOKEN   => $payment->getAdditionalInformation('cc_number_token'),
            ],
        ];

        return $instruction;
    }
}
