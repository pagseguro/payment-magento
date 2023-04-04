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
 * Class Create Order Client - create authorization for payment for Cc and Boleto.
 */
class CreateOrderClient implements ClientInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Charges - Block name.
     */
    public const CHARGES = 'charges';

    /**
     * Reponse Pay Status - Block Name.
     */
    public const STATUS = 'status';

    /**
     * Reponse Pay Status Declined - Value.
     */
    public const STATUS_DECLINED = 'DECLINED';

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

        $path = 'orders';

        $request = $transferObject->getBody();

        $data = $this->api->sendPostRequest($transferObject, $path, $request);

        $status = isset($data[self::CHARGES][0][self::STATUS]) ? $data[self::CHARGES][0][self::STATUS] : 0;

        $blockByState = ($status === self::STATUS_DECLINED) ? 1 : 0;

        $response = array_merge(
            [
                self::RESULT_CODE  => ($status && !$blockByState) ? 1 : 0,
            ],
            $data
        );

        return $response;
    }
}
