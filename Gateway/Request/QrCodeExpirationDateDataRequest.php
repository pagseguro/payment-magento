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
use PagBank\PaymentMagento\Gateway\Config\ConfigPix;

/**
 * Class Qr Code Expiration Date Data Request - Structure for QRCode information in orders.
 */
class QrCodeExpirationDateDataRequest implements BuilderInterface
{
    /**
     * Expiration Date block name.
     */
    public const EXPIRATION_DATE = 'expiration_date';

    /**
     * @var ConfigPix
     */
    protected $configPix;

    /**
     * @param Config $configPix
     */
    public function __construct(
        ConfigPix $configPix
    ) {
        $this->configPix = $configPix;
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

        $storeId = $order->getStoreId();

        $result[QrCodeDataRequest::QR_CODES][] = [
            self::EXPIRATION_DATE => $this->configPix->getExpirationFormat($storeId),
        ];

        return $result;
    }
}
