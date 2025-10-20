<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Request\TwoCreditCard;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;
use PagBank\PaymentMagento\Gateway\Config\ConfigTwoCc;
use PagBank\PaymentMagento\Model\Api\ConsultPSInstallments;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Dual Charges Data Request - Structure of payment for Two Credit Cards.
 */
class DualChargesDataRequest implements BuilderInterface
{
    /**
     * Charges - Block name.
     */
    public const CHARGES = 'charges';

    /**
     * Amount - Block name.
     */
    public const AMOUNT = 'amount';

    /**
     * Value - Block name.
     */
    public const VALUE = 'value';

    /**
     * Currency - Block name.
     */
    public const CURRENCY = 'currency';

    /**
     * Currency Value - Value.
     */
    public const CURRENCY_VALUE = 'BRL';

    /**
     * Payment Method block name.
     */
    public const PAYMENT_METHOD = 'payment_method';

    /**
     * Soft Descriptor - Block name.
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
     * Installments - Block name.
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
     * Credit card Store - Block Name.
     */
    public const CREDIT_CARD_STORE = 'store';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigTwoCc
     */
    protected $configTwoCc;

    /**
     * @var ConsultPSInstallments
     */
    protected $consultInstallments;

    /**
     * @param Config                $config
     * @param ConfigTwoCc           $configTwoCc
     * @param ConsultPSInstallments $consultInstallments
     */
    public function __construct(
        Config $config,
        ConfigTwoCc $configTwoCc,
        ConsultPSInstallments $consultInstallments
    ) {
        $this->config = $config;
        $this->configTwoCc = $configTwoCc;
        $this->consultInstallments = $consultInstallments;
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

        /** @var PaymentDataObject $paymentDO */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var InfoInterface $payment */
        $payment = $paymentDO->getPayment();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        // Calculate amounts for each card
        $amounts = $this->calculateCardAmounts($payment, $order, $storeId);

        // First card charge
        $result[self::CHARGES][] = $this->buildFirstCardCharge($payment, $storeId, $amounts['first']);

        // Second card charge
        $result[self::CHARGES][] = $this->buildSecondCardCharge($payment, $storeId, $amounts['second']);

        return $result;
    }

    /**
     * Calculate amounts for each card considering interest.
     *
     * @param InfoInterface                 $payment
     * @param \Magento\Sales\Model\Order   $order
     * @param int                          $storeId
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function calculateCardAmounts($payment, $order, $storeId)
    {
        $orderTotal = $order->getGrandTotalAmount();
        
        $firstCardBaseAmount = $payment->getAdditionalInformation('first_card_amount') ?: 0;
        $firstCardIns = $payment->getAdditionalInformation('first_card_cc_installments') ?: 1;
        
        $firstCardBin = $payment->getAdditionalInformation('first_card_bin') ?: '524008';
        
        $firstCardFinalAmount = $firstCardBaseAmount;
        $firstCardInterest = 0;
        
        if ($firstCardIns > 1 && $firstCardBin) {
            $formattedAmount = $this->config->formatPrice($firstCardBaseAmount);
            
            $installments = $this->consultInstallments->getPagBankInstallments(
                $storeId,
                $firstCardBin,
                $formattedAmount
            );
            
            if (isset($installments[$firstCardIns - 1])) {
                $installment = $installments[$firstCardIns - 1];
                if (isset($installment['amount']['fees']['buyer']['interest']['total'])) {
                    $firstCardInterest = $installment['amount']['fees']['buyer']['interest']['total'] / 100;
                    $firstCardFinalAmount = $firstCardBaseAmount + $firstCardInterest;
                }
            }
        }
        
        $secCardFinalAmount = $orderTotal - $firstCardFinalAmount;
        
        if ($secCardFinalAmount <= 0) {
            throw new LocalizedException(
                __('Invalid payment distribution. First card amount exceeds or equals order total.')
            );
        }

        return [
            'first' => $firstCardFinalAmount,
            'second' => $secCardFinalAmount
        ];
    }

    /**
     * Build First Card Charge.
     *
     * @param InfoInterface $payment
     * @param int           $storeId
     * @param float         $amount
     *
     * @return array
     */
    protected function buildFirstCardCharge($payment, $storeId, $amount)
    {
        $installment = $payment->getAdditionalInformation('first_card_cc_installments') ?: 1;
        $typeCard = $payment->getAdditionalInformation('first_card_card_type_transaction') ?: self::TYPE_VALUE;

        return [
            self::AMOUNT => [
                self::VALUE => $this->config->formatPrice($amount),
                self::CURRENCY => self::CURRENCY_VALUE,
            ],
            self::PAYMENT_METHOD => [
                self::TYPE              => $typeCard,
                self::SOFT_DESCRIPTOR   => $this->config->getSoftDescriptor($storeId),
                self::CAPTURE           => false,
                self::INSTALLMENTS      => $installment,
                self::CREDIT_CARD       => [
                    self::CREDIT_NUMBER_TOKEN   => $payment->getAdditionalInformation('first_card_cc_number_token'),
                ],
            ],
        ];
    }

    /**
     * Build Second Card Charge.
     *
     * @param InfoInterface $payment
     * @param int           $storeId
     * @param float         $amount
     *
     * @return array
     */
    protected function buildSecondCardCharge($payment, $storeId, $amount)
    {
        $installment = $payment->getAdditionalInformation('second_card_cc_installments') ?: 1;
        $typeCard = $payment->getAdditionalInformation('second_card_card_type_transaction') ?: self::TYPE_VALUE;

        return [
            self::AMOUNT => [
                self::VALUE => $this->config->formatPrice($amount),
                self::CURRENCY => self::CURRENCY_VALUE,
            ],
            self::PAYMENT_METHOD => [
                self::TYPE              => $typeCard,
                self::SOFT_DESCRIPTOR   => $this->config->getSoftDescriptor($storeId),
                self::CAPTURE           => $this->configTwoCc->hasCapture($storeId),
                self::INSTALLMENTS      => $installment,
                self::CREDIT_CARD       => [
                    self::CREDIT_NUMBER_TOKEN   => $payment->getAdditionalInformation('second_card_cc_number_token'),
                ],
            ],
        ];
    }
}
