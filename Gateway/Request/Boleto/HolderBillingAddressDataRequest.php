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
use PagBank\PaymentMagento\Gateway\Request\AddressDataRequest;
use PagBank\PaymentMagento\Gateway\Request\ChargesDataRequest;

/**
 * Class Holder Billing Address Data Request - Payer address structure for the Boleto method.
 */
class HolderBillingAddressDataRequest implements BuilderInterface
{
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

            $postalCode = preg_replace('/[^0-9]/', '', (string) $billingAddress->getPostcode());
            $postalCode = str_pad($postalCode, 8, '0', STR_PAD_RIGHT);

            $result[ChargesDataRequest::CHARGES][][PaymentMethodDataRequest::PAYMENT_METHOD] = [
                strtolower(PaymentMethodDataRequest::METHOD)    => [
                    HolderDataRequest::HOLDER  => [
                        AddressDataRequest::ADDRESS => [
                            // phpcs:ignore Generic.Files.LineLength
                            AddressDataRequest::POSTAL_CODE   => $postalCode,
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
                            AddressDataRequest::COUNTRY_CODE  => $billingAddress->getCountryId(),
                        ],
                    ],
                ],
            ];
        }

        return $result;
    }
}
