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
 * Class Metadata Request - Metadata structure for orders.
 */
class MetadataRequest implements BuilderInterface
{
    /**
     * Store Id block name.
     */
    public const METADATA = 'metadata';

    /**
     * Store Id block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * PagBank Payment Id Block name.
     */
    public const PAGBANK_PAYMENT_ID = 'payment_id';

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function build(array $buildSubject): array
    {
        $result = [];

        /** @var PaymentDataObject $paymentDO * */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order $order * */
        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        $result[self::METADATA][] = [
            self::STORE_ID  => $storeId ?: 0,
        ];

        return $result;
    }
}
