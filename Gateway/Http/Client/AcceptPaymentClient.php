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
     * @var ApiClient
     */
    protected $api;

    /**
     * @param ApiClient $api
     */
    public function __construct(
        ApiClient $api
    ) {
        $this->api = $api;
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

        $path = 'charges/'.$paymentId.'/capture';

        $data = $this->api->sendPostRequest($transferObject, $path, $request);

        if (isset($data[self::RESPONSE_STATUS]) &&
            $data[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_CONFIRMED
        ) {
            $status = 1;
        }

        if (is_array($data)) {
            $response = array_merge([self::RESULT_CODE => $status], $data);
        }

        return $response;
    }
}
