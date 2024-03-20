<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Request\DeepLink;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;
use PagBank\PaymentMagento\Gateway\Config\ConfigDeepLink;

/**
 * Class Deep Link Data Request - Structure for QRCode information in orders.
 */
class DeepLinkDataRequest implements BuilderInterface
{
    /**
     * Deep Link block name.
     */
    public const DEEP_LINK = 'deep_links';

    /**
     * Amount block name.
     */
    public const AMOUNT = 'amount';

    /**
     * Amount Value block name.
     */
    public const AMOUNT_VALUE = 'value';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigDeepLink
     */
    protected $configDeepLink;

    /**
     * @param Config $config
     * @param ConfigDeepLink $configDeepLink
     */
    public function __construct(
        Config $config,
        ConfigDeepLink $configDeepLink
    ) {
        $this->config = $config;
        $this->configDeepLink = $configDeepLink;
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

        /** @var \Magento\Sales\Model\Order $order */
        $order = $paymentDO->getOrder();

        $grandTotal = $order->getGrandTotalAmount();

        $result[self::DEEP_LINK][] = [
            self::AMOUNT => [
                self::AMOUNT_VALUE => $this->config->formatPrice($grandTotal),
            ]
        ];

        return $result;
    }
}
