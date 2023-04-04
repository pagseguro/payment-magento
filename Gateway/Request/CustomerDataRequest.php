<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;
use PagBank\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;

/**
 * Class Customer Data Request - Customer Data structure for orders.
 */
class CustomerDataRequest implements BuilderInterface
{
    /**
     * Customer block name.
     */
    public const CUSTOMER = 'customer';

    /**
     * Customer name block name.
     */
    public const CUSTOMER_NAME = 'name';

    /**
     * Customer email block name.
     */
    public const CUSTOMER_EMAIL = 'email';

    /**
     * Customer Tax Id block name.
     */
    public const CUSTOMER_TAX_ID = 'tax_id';

    /**
     * Customer Phones block name.
     */
    public const CUSTOMER_PHONES = 'phones';

    /**
     * Customer Phone Country block name.
     */
    public const CUSTOMER_PHONE_COUNTRY = 'country';

    /**
     * Customer Phone Area block name.
     */
    public const CUSTOMER_PHONE_AREA = 'area';

    /**
     * Customer Phone Number block name.
     */
    public const CUSTOMER_PHONE_NUMBER = 'number';

    /**
     * Customer Phone Type block name.
     */
    public const CUSTOMER_PHONE_TYPE = 'type';

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param Config              $config
     */
    public function __construct(
        OrderAdapterFactory $orderAdapterFactory,
        Config $config
    ) {
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

        $taxId = $this->getTaxId($payment, $orderAdapter);

        $phones = $this->structurePhone($billingAddress->getTelephone(), $billingAddress->getCountryId());

        $result[self::CUSTOMER] = [
            self::CUSTOMER_NAME     => $name,
            self::CUSTOMER_EMAIL    => $billingAddress->getEmail(),
            self::CUSTOMER_TAX_ID   => preg_replace('/[^0-9]/', '', $taxId),
            self::CUSTOMER_PHONES   => [$phones],
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

    /**
     * Structure Phone.
     *
     * @param string $phone
     * @param string $country
     *
     * @return array
     */
    public function structurePhone($phone, $country)
    {
        $defaultCountryCode = 1;

        if ($country == 'BR') {
            $defaultCountryCode = '55';
        }

        return [
            self::CUSTOMER_PHONE_COUNTRY    => (int) $defaultCountryCode,
            self::CUSTOMER_PHONE_AREA       => (int) $this->getNumberOrDDD($phone, true),
            self::CUSTOMER_PHONE_NUMBER     => (int) $this->getNumberOrDDD($phone, false),
            self::CUSTOMER_PHONE_TYPE       => 'MOBILE',
        ];
    }

    /**
     * Value For Field Address.
     *
     * @param string $paramTelephone
     * @param bool   $returnDDD
     *
     * @return string
     */
    public function getNumberOrDDD($paramTelephone, $returnDDD)
    {
        $custDDD = '11';
        $custTelephone = preg_replace('/[^0-9]/', '', $paramTelephone);

        $str = strlen($custTelephone) - 9;
        $indice = 9;

        if (strlen($custTelephone) === 10) {
            $str = strlen($custTelephone) - 8;
            $indice = 8;
        }

        if ($str > 0) {
            $custDDD = substr($custTelephone, 0, 2);
            $custTelephone = substr($custTelephone, $str, $indice);
        }

        $result = $custTelephone;

        if ($returnDDD) {
            $result = $custDDD;
        }

        return $result;
    }
}
