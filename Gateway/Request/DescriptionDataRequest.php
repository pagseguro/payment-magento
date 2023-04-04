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

/**
 * Class Description Data Request - Payment description structure for orders.
 */
class DescriptionDataRequest implements BuilderInterface
{
    /**
     * Description block name.
     */
    public const DESCRIPTION = 'description';

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

        /** @var \Magento\Sales\Model\Order $order * */
        $order = $paymentDO->getOrder();

        $result[self::DESCRIPTION] = __('Pay for Order #%1', $order->getOrderIncrementId());

        return $result;
    }
}
