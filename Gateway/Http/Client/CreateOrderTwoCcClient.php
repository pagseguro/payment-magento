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
 * Class Create Order Two Cc Client - create order for payment with two credit cards.
 */
class CreateOrderTwoCcClient implements ClientInterface
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
     * Order ID - Block name.
     */
    public const ORDER_ID = 'id';

    /**
     * Response Pay Status - Block Name.
     */
    public const STATUS = 'status';

    /**
     * Response Pay Status Declined - Value.
     */
    public const STATUS_DECLINED = 'DECLINED';

    /**
     * Response Pay Status Paid - Value.
     */
    public const RESPONSE_STATUS_PAID = 'PAID';

    /**
     * Response Pay Status Waiting - Value.
     */
    public const RESPONSE_STATUS_WAITING = 'WAITING';

    /**
     * Response Pay Authorized - Block name.
     */
    public const RESPONSE_AUTHORIZED = 'AUTHORIZED';

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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = [];
        $path = 'orders';
        $request = $transferObject->getBody();
        
        $charges = [];
        if (isset($request[self::CHARGES])) {
            $charges = $request[self::CHARGES];
            unset($request[self::CHARGES]);
        }
        
        try {
            $orderData = $this->api->sendPostRequest($transferObject, $path, $request);
        } catch (\Exception $e) {
            return [
                self::RESULT_CODE => 0,
                'error' => 'Failed to create order: ' . $e->getMessage()
            ];
        }
        
        if (!isset($orderData[self::ORDER_ID])) {
            return [
                self::RESULT_CODE => 0,
                'error' => 'Failed to create order'
            ];
        }
        
        $orderId = $orderData[self::ORDER_ID];
        $paymentPath = sprintf('orders/%s/pay', $orderId);
        $successfulCharges = [];
        $lastChargeResponse = null;
        
        foreach ($charges as $index => $charge) {
            $chargeRequest = [
                self::CHARGES => [$charge]
            ];
            
            try {
                $chargeResponse = $this->api->sendPostRequest($transferObject, $paymentPath, $chargeRequest);
            } catch (\Exception $e) {
                $this->cancelCharges($transferObject, $successfulCharges);
                return [
                    self::RESULT_CODE => 0,
                    'error' => sprintf('Failed to process charge %d: %s', $index + 1, $e->getMessage())
                ];
            }
            
            // Get the current charge from response (API returns all charges, we need the one at current index)
            if (!isset($chargeResponse[self::CHARGES][$index])) {
                $this->cancelCharges($transferObject, $successfulCharges);
                return [
                    self::RESULT_CODE => 0,
                    'error' => sprintf('Failed to process charge %d', $index + 1)
                ];
            }

            $currentCharge = $chargeResponse[self::CHARGES][$index];
            $chargeStatus = $currentCharge[self::STATUS] ?? null;
            $chargeId = $currentCharge['id'] ?? null;

            if (!$this->isValidStatus($chargeStatus)) {
                $this->cancelCharges($transferObject, $successfulCharges);
                return [
                    self::RESULT_CODE => 0,
                    self::CHARGES => [$currentCharge]
                ];
            }

            if ($chargeId) {
                $successfulCharges[] = [
                    'id' => $chargeId,
                    'amount' => $charge['amount'] ?? []
                ];
            }
            
            $lastChargeResponse = $chargeResponse;
        }
        
        if ($lastChargeResponse && is_array($lastChargeResponse)) {
            $response = array_merge(
                [
                    self::RESULT_CODE => 1,
                ],
                $lastChargeResponse
            );
        }
        
        return $response;
    }

    /**
     * Check if charge status is valid.
     *
     * @param string|null $status
     * @return bool
     */
    protected function isValidStatus($status): bool
    {
        return in_array($status, [
            self::RESPONSE_STATUS_PAID,
            self::RESPONSE_STATUS_WAITING,
            self::RESPONSE_AUTHORIZED
        ], true);
    }

    /**
     * Cancel successful charges.
     *
     * @param TransferInterface $transferObject
     * @param array $successfulCharges
     * @return void
     */
    protected function cancelCharges(TransferInterface $transferObject, array $successfulCharges): void
    {
        foreach ($successfulCharges as $chargeData) {
            try {
                $cancelPath = sprintf('charges/%s/cancel', $chargeData['id']);
                $cancelBody = [
                    'amount' => $chargeData['amount']
                ];
                $this->api->sendPostRequest($transferObject, $cancelPath, $cancelBody);
            } catch (\Exception $e) {
                // Log cancellation error but continue with other cancellations
                continue;
            }
        }
    }
}