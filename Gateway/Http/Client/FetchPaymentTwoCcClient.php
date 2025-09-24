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
 * Class Fetch Payment Client - Fetch Transaction in PagBank applying the status.
 */
class FetchPaymentTwoCcClient implements ClientInterface
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
        $path = 'orders/'.$paymentId;

        $data = $this->api->sendGetRequest($transferObject, $path);

        if (!is_array($data) || !isset($data['charges']) || count($data['charges']) !== 2) {
            return $response;
        }

        // Sync charge statuses
        $this->synchronizeCharges($data['charges'], $transferObject);
        
        // Re-fetch order after synchronization
        $data = $this->api->sendGetRequest($transferObject, $path);

        if (is_array($data)) {
            $response = array_merge(
                [
                    self::RESULT_CODE  => (isset($data['id'])) ? 1 : 0,
                ],
                $data
            );
        }

        return $response;
    }

    /**
     * Synchronize charges status.
     *
     * @param array $charges
     * @param TransferInterface $transferObject
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function synchronizeCharges(array $charges, TransferInterface $transferObject): void
    {
        $firstCharge = $charges[0];
        $secondCharge = $charges[1];
        
        $firstStatus = $firstCharge['status'] ?? '';
        $secondStatus = $secondCharge['status'] ?? '';

        // If one is CANCELED, cancel the other
        if ($firstStatus === 'CANCELED' && $secondStatus !== 'CANCELED') {
            $this->cancelCharge($secondCharge, $transferObject);
            return;
        }
        
        if ($secondStatus === 'CANCELED' && $firstStatus !== 'CANCELED') {
            $this->cancelCharge($firstCharge, $transferObject);
            return;
        }

        // If one is DENIED/DECLINED, cancel the other if it's valid
        if ($this->isDeniedStatus($firstStatus) && $this->isValidStatus($secondStatus)) {
            $this->cancelCharge($secondCharge, $transferObject);
            return;
        }
        
        if ($this->isDeniedStatus($secondStatus) && $this->isValidStatus($firstStatus)) {
            $this->cancelCharge($firstCharge, $transferObject);
            return;
        }

        // If one is PAID, capture the other
        if ($firstStatus === 'PAID' && $secondStatus === 'AUTHORIZED') {
            if (!$this->captureCharge($secondCharge, $transferObject)) {
                $this->cancelCharge($firstCharge, $transferObject);
            }
            return;
        }
        
        if ($secondStatus === 'PAID' && $firstStatus === 'AUTHORIZED') {
            if (!$this->captureCharge($firstCharge, $transferObject)) {
                $this->cancelCharge($secondCharge, $transferObject);
            }
            return;
        }
    }

    /**
     * Check if status is denied.
     *
     * @param string $status
     * @return bool
     */
    private function isDeniedStatus(string $status): bool
    {
        return in_array($status, ['DENIED', 'DECLINED'], true);
    }

    /**
     * Check if status is valid.
     *
     * @param string $status
     * @return bool
     */
    private function isValidStatus(string $status): bool
    {
        return in_array($status, ['AUTHORIZED', 'PAID', 'WAITING'], true);
    }

    /**
     * Cancel charge.
     *
     * @param array $charge
     * @param TransferInterface $transferObject
     * @return bool
     */
    private function cancelCharge(array $charge, TransferInterface $transferObject): bool
    {
        try {
            $chargeId = $charge['id'];
            $path = 'charges/' . $chargeId . '/cancel';
            
            $body = [
                'amount' => [
                    'value' => $charge['amount']['value'] ?? 0
                ]
            ];
            
            $result = $this->api->sendPostRequest($transferObject, $path, $body);
            return is_array($result) && isset($result['status']) && $result['status'] === 'CANCELED';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Capture charge.
     *
     * @param array $charge
     * @param TransferInterface $transferObject
     * @return bool
     */
    private function captureCharge(array $charge, TransferInterface $transferObject): bool
    {
        try {
            $chargeId = $charge['id'];
            $path = 'charges/' . $chargeId . '/capture';
            
            $body = [
                'amount' => [
                    'value' => $charge['amount']['value'] ?? 0
                ]
            ];
            
            $result = $this->api->sendPostRequest($transferObject, $path, $body);
            return is_array($result) && isset($result['status']) && $result['status'] === 'PAID';
        } catch (\Exception $e) {
            return false;
        }
    }
}