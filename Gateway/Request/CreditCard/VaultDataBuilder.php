<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Request\CreditCard;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use PagBank\PaymentMagento\Gateway\Request\ChargesDataRequest;

/**
 * Class Vault Data Builder - Structure of payment for Credit Card Vault.
 */
class VaultDataBuilder implements BuilderInterface
{
    /**
     * Payment Method - Block name.
     */
    public const PAYMENT_METHOD = 'payment_method';

    /**
     * Credit card - Block name.
     */
    public const CREDIT_CARD = 'card';

    /**
     * Credit card store - Block Name.
     */
    public const CREDIT_CARD_STORE = 'store';

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

        $save = false;

        /** @var PaymentDataObject $paymentDO * */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var InfoInterface $payment * */
        $payment = $paymentDO->getPayment();

        if (!empty($payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE))) {
            $save = true;
        }

        $result[ChargesDataRequest::CHARGES][] = [
            self::PAYMENT_METHOD => [
                self::CREDIT_CARD => [
                    self::CREDIT_CARD_STORE => $save,
                ],
            ],
        ];

        return $result;
    }
}
