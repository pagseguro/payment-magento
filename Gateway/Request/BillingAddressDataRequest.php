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
 * Class Billing Address Data Builder - Structure for billing address on customer.
 */
class BillingAddressDataRequest implements BuilderInterface
{
    /**
     * Shipping block name.
     */
    public const CUSTOMER = 'customer';

    /**
     * Address block name.
     */
    public const ADDRESS = 'address';

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
        if ($billingAddress) {
            $result[self::CUSTOMER] = [
                self::ADDRESS           => [
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::POSTAL_CODE   => preg_replace('/[^0-9]/', '', (string) $billingAddress->getPostcode()),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::STREET        => $this->config->getValueForAddress($billingAddress, AddressDataRequest::STREET),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::NUMBER        => $this->config->getValueForAddress($billingAddress, AddressDataRequest::NUMBER),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::LOCALITY      => $this->config->getValueForAddress($billingAddress, AddressDataRequest::LOCALITY),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::COMPLEMENT    => $this->config->getValueForAddress($billingAddress, AddressDataRequest::COMPLEMENT),
                    AddressDataRequest::CITY          => $billingAddress->getCity(),
                    AddressDataRequest::STATE         => $billingAddress->getRegion(),
                    AddressDataRequest::STATE_CODE    => $billingAddress->getRegionCode(),
                    AddressDataRequest::COUNTRY_CODE  => 'BRA',
                ],
            ];
        }

        return $result;
    }
}
