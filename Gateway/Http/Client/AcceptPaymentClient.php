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

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Framework\Lock\LockManagerInterface;

/**
 * Class Accept Payment Client - Returns capture to accept payment.
 */
class AcceptPaymentClient implements ClientInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Approved - Value.
     */
    public const RESPONSE_STATUS_CONFIRMED = 'PAID';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_ERROR = 'ERROR';

    /**
     * Lock timeout in seconds.
     */
    private const LOCK_TIMEOUT = 3600;

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
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $status = 0;
        $response = [];
        $request = $transferObject->getBody();
        $paymentId = $request['payment_id'];

        $lockName = 'pagbank_accept_order_' . $paymentId;

        if (!$this->lockManager->lock($lockName, self::LOCK_TIMEOUT)) {
            return [
                self::RESULT_CODE => 0,
                'error' => __('Could not acquire lock for order processing')
            ];
        }

        try {
            $path = 'charges/' . $paymentId . '/capture';

            $data = $this->api->sendPostRequest($transferObject, $path, $request);

            if (isset($data[self::RESPONSE_STATUS]) &&
                $data[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_CONFIRMED
            ) {
                $status = 1;
            }

            if (is_array($data)) {
                $response = array_merge([self::RESULT_CODE => $status], $data);
                $response['lock_name'] = $lockName;
            }

            return $response;
        } catch (\Exception $e) {
            $this->lockManager->unlock($lockName);
            throw $e;
        }
    }
}
