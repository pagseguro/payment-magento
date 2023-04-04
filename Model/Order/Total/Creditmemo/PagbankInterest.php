<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Model\Order\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Class Pagbank Interest - Model for implementing the PagBank Interest in Creditmemo.
 */
class PagbankInterest extends AbstractTotal
{
    /**
     * Collect Pagbank Interest.
     *
     * @param Creditmemo $creditmemo
     *
     * @return void
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $psInterest = $order->getPagbankInterestAmount();
        $basePsInterest = $order->getBasePagbankInterestAmount();

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $psInterest);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $psInterest);
        $creditmemo->setPagbankInterestAmount($psInterest);
        $creditmemo->setBasePagbankInterestAmount($basePsInterest);
        $order->setPagbankInterestAmountRefunded($psInterest);
        $order->setBasePagbankInterestAmountRefunded($basePsInterest);
    }
}
