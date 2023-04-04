<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Block\Sales\Order\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

/**
 * Information block on the PagBank Interest.
 */
class PagbankInterest extends Template
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var DataObject
     */
    protected $source;

    /**
     * Type display in Full Sumary.
     *
     * @return bool
     */
    public function displayFullSummary()
    {
        return true;
    }

    /**
     * Get data (totals) source model.
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get Store.
     *
     * @return string
     */
    public function getStore()
    {
        return $this->order->getStore();
    }

    /**
     * Get Order.
     *
     * @return order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Initialize payment pagbank interest totals.
     *
     * @return void
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->order = $parent->getOrder();
        $this->source = $parent->getSource();

        if (!$this->source->getPagbankInterestAmount() || (int) $this->source->getPagbankInterestAmount() === 0) {
            return $this;
        }

        $psInterest = $this->source->getPagbankInterestAmount();

        $basePsInterest = $this->source->getBasePagbankInterestAmount();

        $label = $this->getLabel($psInterest);

        $psInterestAmount = new DataObject(
            [
                'code'          => 'pagbank_interest',
                'strong'        => false,
                'value'         => $psInterest,
                'base_value'    => $basePsInterest,
                'label'         => $label,
            ]
        );

        $parent->addTotal($psInterestAmount, 'pagbank_interest');
    }

    /**
     * Get Subtotal label.
     *
     * @param string|null $psInterest
     *
     * @return Phrase
     */
    public function getLabel($psInterest)
    {
        if ($psInterest >= 0) {
            return __('Installments Interest');
        }

        return __('Discount in cash');
    }
}
