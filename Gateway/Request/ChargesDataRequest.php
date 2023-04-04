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

use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Charges Data Request - Payment charges structure for orders.
 */
class ChargesDataRequest implements BuilderInterface
{
    /**
     * Charges - Block Name.
     */
    public const CHARGES = 'charges';

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

        $result[self::CHARGES][] = [];

        return $result;
    }
}
