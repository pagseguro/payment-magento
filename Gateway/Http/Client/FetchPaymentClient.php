<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

declare(strict_types=1);

namespace PagBank\PaymentMagento\Gateway\Http\Client;

use Magento\Framework\Lock\LockManagerInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class Fetch Payment Client - Fetch Transaction in PagBank applying the status.
 */
class FetchPaymentClient implements ClientInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * @var ApiClient
     */
    protected $api;

    /**
     * @var LockManagerInterface
     */
    private $lockManager;

    /**
     * @param ApiClient $api
     * @param LockManagerInterface $lockManager
     * @param Logger $logger
     */
    public function __construct(
        ApiClient $api,
        LockManagerInterface $lockManager
    ) {
        $this->api = $api;
        $this->lockManager = $lockManager;
    }

    /**
     * Places request to gateway.
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = [];

        $request = $transferObject->getBody();

        $paymentId = $request['payment_id'];

        $path = 'orders/'.$paymentId;

        try {
            $data = $this->api->sendGetRequest($transferObject, $path);

            if (is_array($data)) {
                $response = array_merge(
                    [
                        self::RESULT_CODE  => (isset($data['id'])) ? 1 : 0,
                    ],
                    $data
                );
            }
        } finally {
            if (isset($request['order_id'])) {
                $orderId = $request['order_id'];
                $lockName = 'pagbank_order_' . $orderId;

                $this->lockManager->unlock($lockName);

            }
        }

        return $response;
    }
}