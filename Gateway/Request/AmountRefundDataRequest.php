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

/**
 * Class Amount Refund Data Request - Payment amount structure for refund payment.
 */
class AmountRefundDataRequest implements BuilderInterface
{
    /**
     * Amount block name.
     */
    public const AMOUNT = 'amount';

    /**
     * Amount Value block name.
     */
    public const AMOUNT_VALUE = 'value';

    /**
     * Amount Value block name.
     */
    public const AMOUNT_CURRENCY = 'currency';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
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

        /** @var \Magento\Sales\Model\Order $order * */
        $order = $payment->getOrder();

        $creditmemo = $payment->getCreditMemo();

        $totalCreditmemo = $creditmemo->getGrandTotal();

        $result[self::AMOUNT] = [
            self::AMOUNT_VALUE      => $this->config->formatPrice($totalCreditmemo),
            self::AMOUNT_CURRENCY   => $order->getCurrencyCode(),
        ];

        return $result;
    }
}
