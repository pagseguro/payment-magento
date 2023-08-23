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

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;

/**
 * Class Items Data Request - Item structure for orders.
 */
class ItemsDataRequest implements BuilderInterface
{
    /**
     * Items block name.
     */
    public const ITEMS = 'items';

    /**
     * Itens Reference Id block Name.
     */
    public const ITEM_REFERENCE_ID = 'reference_id';

    /**
     * Item name block name.
     */
    public const ITEM_NAME = 'name';

    /**
     * Item quantity block name.
     */
    public const ITEM_QUANTITY = 'quantity';

    /**
     * Item unit amount block name.
     */
    public const ITEM_UNIT_AMOUNT = 'unit_amount';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function build(array $buildSubject): array
    {
        $result = [];

        /** @var PaymentDataObject $paymentDO * */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order $order * */
        $order = $paymentDO->getOrder();

        $result[self::ITEMS] = $this->getPurchaseItems($order);

        return $result;
    }

    /**
     * Get Purchase Items.
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function getPurchaseItems(
        $order
    ) {
        $result = [];
        $items = $order->getItems();

        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            
            $productName = preg_replace('/[^\p{L}0-9\s]/u', '', $item->getName());

            $result[] = [
                self::ITEM_REFERENCE_ID => substr($item->getSku(), 0, 60),
                self::ITEM_NAME         => substr($productName, 0, 55),
                self::ITEM_QUANTITY     => $item->getQtyOrdered(),
                self::ITEM_UNIT_AMOUNT  => $this->config->formatPrice($item->getPrice()),
            ];
        }

        return $result;
    }
}
