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
    protected $statusCollectionFactory;

    /**
     * Constructor
     *
     * @param CollectionFactory $statusCollectionFactory
     */
    public function __construct(
        CollectionFactory $statusCollectionFactory
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $statusCollection = $this->statusCollectionFactory->create();
        foreach ($statusCollection as $status) {
            $options[] = [
                'value' => $status->getStatus(),
                'label' => $status->getLabel()
            ];
        }
        return $options;
    }
}