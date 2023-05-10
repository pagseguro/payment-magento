<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Request\Boleto;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;
use PagBank\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use PagBank\PaymentMagento\Gateway\Request\ChargesDataRequest;

/**
 * Class Holder Data Request - Payer info structure for the Boleto method.
 */
class HolderDataRequest implements BuilderInterface
{
    /**
     * Holder block name.
     */
    public const HOLDER = 'holder';

    /**
     * Holder name block name.
     */
    public const HOLDER_NAME = 'name';

    /**
     * Holder email block name.
     */
    public const HOLDER_EMAIL = 'email';

    /**
     * Holder Tax Id block name.
     */
    public const HOLDER_TAX_ID = 'tax_id';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param Config              $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
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

        /** @var OrderAdapterFactory $orderAdapter * */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $billingAddress = $orderAdapter->getBillingAddress();

        $name = $billingAddress->getFirstname().' '.$billingAddress->getLastname();

        if ($payment->getAdditionalInformation('payer_name')) {
            $name = $payment->getAdditionalInformation('payer_name');
        }

        $taxId = $this->getTaxId($payment, $orderAdapter);

        $result[ChargesDataRequest::CHARGES][] = [
            PaymentMethodDataRequest::PAYMENT_METHOD => [
                strtolower(PaymentMethodDataRequest::METHOD)    => [
                    self::HOLDER  => [
                        self::HOLDER_NAME     => $name,
                        self::HOLDER_EMAIL    => $billingAddress->getEmail(),
                        self::HOLDER_TAX_ID   => preg_replace('/[^0-9]/', '', (string) $taxId),
                    ],
                ],
            ],
        ];

        return $result;
    }

    /**
     * Get Tax Id.
     *
     * @param InfoInterface       $payment
     * @param OrderAdapterFactory $orderAdapter
     *
     * @return string|null
     */
    public function getTaxId($payment, $orderAdapter): ?string
    {
        $taxId = null;

        if ($payment->getAdditionalInformation('payer_tax_id')) {
            $taxId = $payment->getAdditionalInformation('payer_tax_id');
        }

        if (!$taxId) {
            $taxId = $this->getValueForTaxId($orderAdapter);
        }

        return $taxId;
    }

    /**
     * Get Value For Tax Id.
     *
     * @param OrderAdapterFactory $orderAdapter
     *
     * @return string
     */
    public function getValueForTaxId($orderAdapter)
    {
        $obtainTaxDocFrom = $this->config->getAddtionalValue('get_tax_id_from');

        $taxId = $orderAdapter->getCustomerTaxvat();

        if ($obtainTaxDocFrom === 'address') {
            $taxId = $orderAdapter->getBillingAddress()->getVatId();
        }

        return $taxId;
    }
}
