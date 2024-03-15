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
 * Class Create Order Payment DeepLink Client - create order for payment for DeepLink.
 */
class CreateOrderPaymentDeepLinkClient implements ClientInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * External Order Id - Block name.
     */
    public const EXT_ORD_ID = 'id';

    /**
     * Reponse Pay Status - Block Name.
     */
    public const STATUS = 'status';

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

        $path = 'orders';

        $data = $this->api->sendPostRequest($transferObject, $path, $request);

        $status = isset($data[self::EXT_ORD_ID]) ?: 0;

        if (is_array($data)) {
            $response = array_merge(
                [
                    self::RESULT_CODE  => ($status) ? 1 : 0,
                ],
                $data
            );
        }

        return $response;
    }
}
