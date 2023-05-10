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
 * Class Shippings Data Builder - Structure for shipping address on orders.
 */
class ShippingsDataRequest implements BuilderInterface
{
    /**
     * Shipping block name.
     */
    public const SHIPPING = 'shipping';

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

        $shippingAddress = $orderAdapter->getShippingAddress();
        if ($shippingAddress) {
            $result[self::SHIPPING] = [
                self::ADDRESS           => [
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::POSTAL_CODE   => preg_replace('/[^0-9]/', '', (string) $shippingAddress->getPostcode()),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::STREET        => $this->config->getValueForAddress($shippingAddress, AddressDataRequest::STREET),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::NUMBER        => $this->config->getValueForAddress($shippingAddress, AddressDataRequest::NUMBER),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::LOCALITY      => $this->config->getValueForAddress($shippingAddress, AddressDataRequest::LOCALITY),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::COMPLEMENT    => $this->config->getValueForAddress($shippingAddress, AddressDataRequest::COMPLEMENT),
                    AddressDataRequest::CITY          => $shippingAddress->getCity(),
                    AddressDataRequest::STATE         => $shippingAddress->getRegion(),
                    AddressDataRequest::STATE_CODE    => $shippingAddress->getRegionCode(),
                    AddressDataRequest::COUNTRY_CODE  => 'BRA',
                ],
            ];
        }

        return $result;
    }
}
