<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author Bruno Elisei <brunoelisei@o2ti.com>
 * @license See LICENSE for license details.
 */

declare(strict_types=1);

namespace PagBank\PaymentMagento\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use PagBank\PaymentMagento\Gateway\Request\TwoCreditCard\AuthTransactionIdTwoCcRequest;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Denied Payment Two Cc Client - Returns void/cancel for two card payments.
 */
class DenyPaymentTwoCcClient implements ClientInterface
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
     * Response Pay Status Canceled - Value.
     */
    public const RESPONSE_STATUS_CANCELED = 'CANCELED';

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
     * Void Results - Block Name.
     */
    public const VOID_RESULTS = 'void_results';

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
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = [
            self::VOID_RESULTS => []
        ];
        $status = 0;
        $voidResults = [];
        $totalVoidedAmount = 0;

        $request = $transferObject->getBody();

        // Get both payment IDs from the request
        $paymentIds = $request[AuthTransactionIdTwoCcRequest::PAGBANK_PAYMENT_IDS] ?? [];
        $transactionAmounts = $request[AuthTransactionIdTwoCcRequest::TRANSACTION_AMOUNTS] ?? [];

        if (count($paymentIds) !== 2) {
            throw new LocalizedException(
                __('Two payment IDs are required for two card void')
            );
        }

        $allSuccess = true;

        // Void each payment
        foreach ($paymentIds as $index => $paymentId) {
            $path = 'charges/' . $paymentId . '/cancel';

            // Build void request with specific amount for this transaction if partial void
            $voidRequest = [];
            if (isset($transactionAmounts[$paymentId])) {
                $voidRequest[AuthTransactionIdTwoCcRequest::AMOUNT] = $transactionAmounts[$paymentId];
            }

            $data = $this->api->sendPostRequest($transferObject, $path, $voidRequest);

            $voidResult = [
                'payment_id' => $paymentId,
                'index' => $index,
                'success' => false,
                'data' => $data,
                'voided_amount' => 0
            ];

            if (isset($data[self::RESPONSE_STATUS]) &&
                $data[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_CANCELED
            ) {
                $voidResult['success'] = true;

                // Get the actual voided amount from response
                if (isset($data[self::RESPONSE_AMOUNT][self::RESPONSE_AMOUNT_VALUE])) {
                    // Value from PagBank comes in cents
                    $voidedAmount = $data[self::RESPONSE_AMOUNT][self::RESPONSE_AMOUNT_VALUE] / 100;
                    $voidResult['voided_amount'] = $voidedAmount;
                    $totalVoidedAmount += $voidedAmount;
                } elseif (isset($transactionAmounts[$paymentId][AuthTransactionIdTwoCcRequest::AMOUNT_VALUE])) {
                    // Fallback to the requested amount if response doesn't include it
                    $voidedAmount = $transactionAmounts[$paymentId][AuthTransactionIdTwoCcRequest::AMOUNT_VALUE] / 100;
                    $voidResult['voided_amount'] = $voidedAmount;
                    $totalVoidedAmount += $voidedAmount;
                }
            } else {
                $allSuccess = false;
            }

            $voidResults[] = $voidResult;
        }

        // Set overall status based on all voids
        if ($allSuccess) {
            $status = 1;
        }

        // Build response
        $response = [
            self::RESULT_CODE => $status,
            self::VOID_RESULTS => $voidResults,
            'total_voided_amount' => $totalVoidedAmount
        ];

        // If both voids were successful, merge the last void data
        if ($allSuccess && !empty($voidResults)) {
            $lastVoid = end($voidResults);
            if (is_array($lastVoid['data'])) {
                $response = array_merge($response, $lastVoid['data']);
            }
        }

        return $response;
    }
}