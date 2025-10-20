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
use PagBank\PaymentMagento\Gateway\Request\TwoCreditCard\AuthTransactionIdTwoCcRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Lock\LockManagerInterface;

/**
 * Class Accept Payment Two Cc Client - Returns capture for two card payments.
 */
class AcceptPaymentTwoCcClient implements ClientInterface
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
     * Response Pay Status Confirmed - Value.
     */
    public const RESPONSE_STATUS_CONFIRMED = 'PAID';

    /**
     * Response Pay Status Error - Value.
     */
    public const RESPONSE_STATUS_ERROR = 'ERROR';

    /**
     * Response Amount - Block Name.
     */
    public const RESPONSE_AMOUNT = 'amount';

    /**
     * Response Amount Value - Block Name.
     */
    public const RESPONSE_AMOUNT_VALUE = 'value';

    /**
     * Capture Results - Block Name.
     */
    public const CAPTURE_RESULTS = 'capture_results';

    /**
     * Lock timeout in seconds.
     */
    private const LOCK_TIMEOUT = 360;

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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = [
            self::CAPTURE_RESULTS => []
        ];
        $status = 0;
        $captureResults = [];
        $totalAuthAmount = 0;

        $request = $transferObject->getBody();
        
        $paymentIds = $request[AuthTransactionIdTwoCcRequest::PAGBANK_PAYMENT_IDS] ?? [];
        $transactionAmounts = $request[AuthTransactionIdTwoCcRequest::TRANSACTION_AMOUNTS] ?? [];
        
        if (count($paymentIds) !== 2) {
            throw new LocalizedException(
                __('Two payment IDs are required for two card capture')
            );
        }

        $lockName = 'pagbank_accept_order_' . implode('_', $paymentIds);

        if (!$this->lockManager->lock($lockName, self::LOCK_TIMEOUT)) {
            return [
                self::RESULT_CODE => 0,
                self::CAPTURE_RESULTS => [],
                'error' => __('Could not acquire lock for order processing')
            ];
        }

        try {
            $allSuccess = true;
            
            foreach ($paymentIds as $index => $paymentId) {
                $path = 'charges/' . $paymentId . '/capture';
                
                $captureRequest = [];
                if (isset($transactionAmounts[$paymentId])) {
                    $captureRequest[AuthTransactionIdTwoCcRequest::AMOUNT] = $transactionAmounts[$paymentId];
                }
                
                $data = $this->api->sendPostRequest($transferObject, $path, $captureRequest);
                
                $captureResult = [
                    'payment_id' => $paymentId,
                    'index' => $index,
                    'success' => false,
                    'data' => $data,
                    'authorized_amount' => 0
                ];
                
                if (isset($data[self::RESPONSE_STATUS]) &&
                    $data[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_CONFIRMED
                ) {
                    $captureResult['success'] = true;
                    
                    if (isset($data[self::RESPONSE_AMOUNT][self::RESPONSE_AMOUNT_VALUE])) {
                        $authorizedAmount = $data[self::RESPONSE_AMOUNT][self::RESPONSE_AMOUNT_VALUE] / 100;
                        $captureResult['authorized_amount'] = $authorizedAmount;
                        $totalAuthAmount += $authorizedAmount;
                    } elseif (isset($transactionAmounts[$paymentId][AuthTransactionIdTwoCcRequest::AMOUNT_VALUE])) {
                        $authorizedAmount = $transactionAmounts[$paymentId][AuthTransactionIdTwoCcRequest::AMOUNT_VALUE] / 100;
                        $captureResult['authorized_amount'] = $authorizedAmount;
                        $totalAuthAmount += $authorizedAmount;
                    }
                } else {
                    $allSuccess = false;
                }
                
                $captureResults[] = $captureResult;
            }
            
            if ($allSuccess) {
                $status = 1;
            }
            
            $response = [
                self::RESULT_CODE => $status,
                self::CAPTURE_RESULTS => $captureResults,
                'total_authorized_amount' => $totalAuthAmount,
                'lock_name' => $lockName
            ];
            
            if ($allSuccess && !empty($captureResults)) {
                $lastCapture = end($captureResults);
                if (is_array($lastCapture['data'])) {
                    $response = array_merge($response, $lastCapture['data']);
                }
            }
            
            return $response;
        } catch (\Exception $e) {
            $this->lockManager->unlock($lockName);
            throw $e;
        }
    }
}
