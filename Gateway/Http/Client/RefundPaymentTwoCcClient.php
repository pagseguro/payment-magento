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
 * Class Refund Payment Two Cc Client - Returns refund for two card payments.
 */
class RefundPaymentTwoCcClient implements ClientInterface
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
     * Response Pay Status Refunded - Value.
     */
    public const RESPONSE_STATUS_REFUNDED = 'CANCELED';

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
     * Refund Results - Block Name.
     */
    public const REFUND_RESULTS = 'refund_results';

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
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = [
            self::REFUND_RESULTS => []
        ];
        $status = 0;
        $refundResults = [];
        $totalRefundedAmount = 0;

        $request = $transferObject->getBody();

        // Get both payment IDs from the request
        $paymentIds = $request[AuthTransactionIdTwoCcRequest::PAGBANK_PAYMENT_IDS] ?? [];
        $transactionAmounts = $request[AuthTransactionIdTwoCcRequest::TRANSACTION_AMOUNTS] ?? [];

        if (count($paymentIds) !== 2) {
            throw new LocalizedException(
                __('Two payment IDs are required for two card refund')
            );
        }

        $allSuccess = true;

        // Refund each payment
        foreach ($paymentIds as $index => $paymentId) {
            $path = 'charges/' . $paymentId . '/cancel';

            // Build refund request with specific amount for this transaction if partial refund
            $refundRequest = [];
            if (isset($transactionAmounts[$paymentId])) {
                $refundRequest[AuthTransactionIdTwoCcRequest::AMOUNT] = $transactionAmounts[$paymentId];
            }

            $data = $this->api->sendPostRequest($transferObject, $path, $refundRequest);

            $refundResult = [
                'payment_id' => $paymentId,
                'index' => $index,
                'success' => false,
                'data' => $data,
                'refunded_amount' => 0
            ];

            if (isset($data[self::RESPONSE_STATUS]) &&
                $data[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_REFUNDED
            ) {
                $refundResult['success'] = true;

                // Get the actual refunded amount from response
                if (isset($data[self::RESPONSE_AMOUNT][self::RESPONSE_AMOUNT_VALUE])) {
                    // Value from PagBank comes in cents
                    $refundedAmount = $data[self::RESPONSE_AMOUNT][self::RESPONSE_AMOUNT_VALUE] / 100;
                    $refundResult['refunded_amount'] = $refundedAmount;
                    $totalRefundedAmount += $refundedAmount;
                } elseif (isset($transactionAmounts[$paymentId][AuthTransactionIdTwoCcRequest::AMOUNT_VALUE])) {
                    // Fallback to the requested amount if response doesn't include it
                    $refundedAmount = $transactionAmounts[$paymentId][AuthTransactionIdTwoCcRequest::AMOUNT_VALUE] / 100;
                    $refundResult['refunded_amount'] = $refundedAmount;
                    $totalRefundedAmount += $refundedAmount;
                }
            } else {
                $allSuccess = false;
            }

            $refundResults[] = $refundResult;
        }

        // Set overall status based on all refunds
        if ($allSuccess) {
            $status = 1;
        }

        // Build response
        $response = [
            self::RESULT_CODE => $status,
            self::REFUND_RESULTS => $refundResults,
            'total_refunded_amount' => $totalRefundedAmount
        ];

        // If both refunds were successful, merge the last refund data
        if ($allSuccess && !empty($refundResults)) {
            $lastRefund = end($refundResults);
            if (is_array($lastRefund['data'])) {
                $response = array_merge($response, $lastRefund['data']);
            }
        }

        return $response;
    }
}
