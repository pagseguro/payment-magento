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
use PagBank\PaymentMagento\Gateway\Request\ChargesDataRequest;
use PagBank\PaymentMagento\Gateway\Request\CreditCard\PaymentCcDataRequest;

/**
 * Class 3ds Auth Data Request - Payment structure for credit card with 3ds authentication.
 */
class ThreeDsAuthDataRequest implements BuilderInterface
{
    /**
     * Authentication Method - block name.
     */
    public const AUTHENTICATION_METHOD = 'authentication_method';

    /**
     * Type - Block name.
     */
    public const TYPE = 'type';

    /**
     * Id - Block name.
     */
    public const ID = 'id';

    /**
     * Type Value - Value.
     */
    public const TYPE_VALUE = 'THREEDS';

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

        $result[ChargesDataRequest::CHARGES][] = [
            PaymentCcDataRequest::PAYMENT_METHOD => $this->getDataThreeDs($payment),
        ];

        return $result;
    }

    /**
     * Get data for 3ds.
     *
     * @param InfoInterface $payment
     *
     * @return array
     */
    public function getDataThreeDs($payment)
    {
        $dataThreeDs = [];
        $sessionID = $payment->getAdditionalInformation('three_ds_session');

        if ($sessionID) {
            $dataThreeDs = [
                self::AUTHENTICATION_METHOD => [
                    self::TYPE  => self::TYPE_VALUE,
                    self::ID    => $sessionID,
                ],
            ];
        }

        return $dataThreeDs;
    }
}
