<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Model\Adminhtml\Source;

use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;

class OrderStatus implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $statusColFactory;

    /**
     * Constructor
     *
     * @param CollectionFactory $statusColFactory
     */
    public function __construct(
        CollectionFactory $statusColFactory
    ) {
        $this->statusColFactory = $statusColFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $statusCollection = $this->statusColFactory->create();
        foreach ($statusCollection as $status) {
            $options[] = [
                'value' => $status->getStatus(),
                'label' => $status->getLabel()
            ];
        }
        return $options;
    }
}