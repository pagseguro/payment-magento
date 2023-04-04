<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Block\Adminhtml\Sales\Order\Creditmemo\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

/**
 * Totals PagBank Interest Block - Method CreditMemo.
 */
class PagbankInterest extends Template
{
    /**
     * Get data (totals) source model.
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get Invoice data.
     *
     * @return invoice
     */
    public function getCreditmemo()
    {
        return $this->getParentBlock()->getCreditmemo();
    }

    /**
     * Initialize payment PagBank Interest totals.
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getCreditmemo();
        $this->getSource();

        if (!$this->getSource()->getPagbankInterestAmount()
            || (int) $this->getSource()->getPagbankInterestAmount() === 0) {
            return $this;
        }

        $total = new DataObject(
            [
                'code'  => 'pagbank_interest',
                'value' => $this->getSource()->getPagbankInterestAmount(),
                'label' => __('Installments Interest'),
            ]
        );

        $this->getParentBlock()->addTotalBefore($total, 'grand_total');

        return $this;
    }
}
