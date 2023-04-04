<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Model\Order\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Class Pagbank Interest - Model for implementing the PagBank Interest in Invoice.
 */
class PagbankInterest extends AbstractTotal
{
    /**
     * Collect Pagbank Interest.
     *
     * @param Invoice $invoice
     *
     * @return void
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();

        $psInterest = $order->getPagbankInterestAmount();
        $basePsInterest = $order->getBasePagbankInterestAmount();

        $invoice->setGrandTotal($invoice->getGrandTotal() + $psInterest);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $psInterest);
        $invoice->setPagbankInterestAmount($psInterest);
        $invoice->setBasePagbankInterestAmount($basePsInterest);
        $invoice->setPagbankInterestAmountInvoiced($psInterest);
        $invoice->setBasePagbankInterestAmountInvoiced($basePsInterest);

        $order->setPagbankInterestAmountInvoiced($psInterest);
        $order->setBasePagbankInterestAmountInvoiced($basePsInterest);
    }
}
