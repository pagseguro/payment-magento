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
 * Class Deny Payment Client - Create authorization for payment deny.
 */
class DenyPaymentClient implements ClientInterface
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
        $response = [];

        $request = $transferObject->getBody();

        $paymentId = $request['payment_id'];

        $path = 'charges/'.$paymentId.'/cancel';

        $data = $this->api->sendPostRequest($transferObject, $path, $request);

        $response = array_merge(
            [
                self::RESULT_CODE  => (isset($data['id'])) ? 1 : 0,
            ],
            $data
        );

        return $response;
    }
}
