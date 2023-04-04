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

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Split Payment Data Request - Payment amount structure.
 */
class SplitPaymentDataRequest implements BuilderInterface
{
    public const BLOCK_NAME_MARKETPLACE_SUBSELLER_PAYMENTS = 'marketplace_subseller_payments';
    public const BLOCK_NAME_SUB_SELLER_ID = 'subseller_id';
    public const BLOCK_NAME_SUBSELLER_SALES_AMOUNT = 'subseller_sales_amount';
    public const BLOCK_NAME_ORDER_ITEMS = 'order_items';
    public const BLOCK_NAME_AMOUNT = 'amount';
    public const BLOCK_NAME_CURRENCY = 'currency';
    public const BLOCK_NAME_ID = 'id';
    public const BLOCK_NAME_DESCRIPTION = 'description';
    public const BLOCK_NAME_TAX_AMOUNT = 'tax_amount';

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $result = [];

        return $result;
    }
}
