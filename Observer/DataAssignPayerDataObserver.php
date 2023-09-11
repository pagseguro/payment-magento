<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class Data Assign Payer Data Observer - Captures payment information for Boleto and Pix.
 */
class DataAssignPayerDataObserver extends AbstractDataAssignObserver
{
    /**
     * @const string
     */
    public const PAYMENT_INFO_PAYER_NAME = 'payer_name';

    /**
     * @const string
     */
    public const PAYMENT_INFO_PAYER_TAX_ID = 'payer_tax_id';

    /**
     * @const string
     */
    public const PAYMENT_INFO_PAYER_PHONE = 'payer_phone';

    /**
     * @var array
     */
    protected $payerData = [
        self::PAYMENT_INFO_PAYER_NAME,
        self::PAYMENT_INFO_PAYER_TAX_ID,
        self::PAYMENT_INFO_PAYER_PHONE,
    ];

    /**
     * Execute.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $paymentInfo = $this->readPaymentModelArgument($observer);

        $paymentInfo->unsAdditionalInformation();

        foreach ($this->payerData as $addInformationKey) {
            if (isset($additionalData[$addInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $addInformationKey,
                    ($additionalData[$addInformationKey]) ?: null
                );
            }
        }
    }
}
