<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Block\Adminhtml\Sales\Order\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

/**
 * Totals PagBank Interest Block - Method Order.
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
     * Initialize payment PagBank Interest totals.
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->order = $parent->getOrder();
        $this->source = $parent->getSource();

        if (!$this->source->getPagbankInterestAmount()
            || (int) $this->source->getPagbankInterestAmount() === 0) {
            return $this;
        }

        $pagbankInterest = $this->source->getPagbankInterestAmount();
        if ($pagbankInterest) {
            $label = $this->getLabel($pagbankInterest);
            $psInterestAmount = new DataObject(
                [
                    'code'   => 'pagbank_interest',
                    'strong' => false,
                    'value'  => $pagbankInterest,
                    'label'  => $label,
                ]
            );

            if ((int) $pagbankInterest !== 0.0000) {
                $parent->addTotal($psInterestAmount, 'pagbank_interest');
            }
        }

        return $this;
    }

    /**
     * Get Subtotal label.
     *
     * @param string|null $pagbankInterest
     *
     * @return Phrase
     */
    public function getLabel($pagbankInterest)
    {
        if ($pagbankInterest >= 0) {
            return __('Installments Interest');
        }

        return __('Discount in cash');
    }
}
