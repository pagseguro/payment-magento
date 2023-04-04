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

use Magento\Payment\Gateway\Request\BuilderInterface;
use PagBank\PaymentMagento\Gateway\Request\ChargesDataRequest;

/**
 * Class Payment Boleto Method Request - Payment method Boleto structure.
 */
class PaymentBoletoDataRequest implements BuilderInterface
{
    /**
     * Payment Method block name.
     */
    public const PAYMENT_METHOD = 'payment_method';

    /**
     * Payment Type block name.
     */
    public const PAYMENT_TYPE = 'type';

    /**
     * Method block name.
     */
    public const METHOD = 'BOLETO';

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(array $buildSubject)
    {
        $result = [];

        $result[ChargesDataRequest::CHARGES][] = [
            PaymentMethodDataRequest::PAYMENT_METHOD => [
                PaymentMethodDataRequest::PAYMENT_TYPE  => self::METHOD,
            ],
        ];

        return $result;
    }
}
