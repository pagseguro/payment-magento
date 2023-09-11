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
 * Class Data Assign Cc Observer - Capture credit card payment information.
 */
class DataAssignCcObserver extends AbstractDataAssignObserver
{
    /**
     * @const string
     */
    public const PAYMENT_INFO_NUMBER_TOKEN = 'cc_number_token';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_INSTALLMENTS = 'cc_installments';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CARDHOLDER_NAME = 'cc_cardholder_name';

    /**
     * @const string
     */
    public const PAYMENT_INFO_PAYER_TAX_ID = 'payer_tax_id';

    /**
     * @const string
     */
    public const PAYMENT_INFO_PAYER_PHONE = 'payer_phone';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_SAVE = 'is_active_payment_token_enabler';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_CID = 'cc_cid';

    /**
     * @var array
     */
    protected $addInformationList = [
        self::PAYMENT_INFO_NUMBER_TOKEN,
        self::PAYMENT_INFO_CARDHOLDER_NAME,
        self::PAYMENT_INFO_CC_INSTALLMENTS,
        self::PAYMENT_INFO_CC_SAVE,
        self::PAYMENT_INFO_CC_CID,
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

        foreach ($this->addInformationList as $addInformationKey) {
            if (isset($additionalData[$addInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $addInformationKey,
                    ($additionalData[$addInformationKey]) ?: null
                );
            }
        }
    }
}
