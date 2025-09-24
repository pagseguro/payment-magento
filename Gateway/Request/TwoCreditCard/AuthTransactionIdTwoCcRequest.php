<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Request\TwoCreditCard;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use PagBank\PaymentMagento\Gateway\Config\Config;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Auth Transaction Id Two Cc Request - Auth Transaction Id structure for two cards.
 */
class AuthTransactionIdTwoCcRequest implements BuilderInterface
{
    /**
     * @var string
     */
    public const PAGBANK_PAYMENT_IDS = 'payment_ids';

    /**
     * @var string
     */
    public const AMOUNT = 'amount';

    /**
     * @var string
     */
    public const AMOUNT_VALUE = 'value';

    /**
     * @var string
     */
    public const AMOUNT_CURRENCY = 'currency';

    /**
     * @var string
     */
    public const TRANSACTION_AMOUNTS = 'transaction_amounts';

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transacRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchBuilder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param TransactionRepositoryInterface $transacRepository
     * @param SearchCriteriaBuilder          $searchBuilder
     * @param Config                         $config
     */
    public function __construct(
        TransactionRepositoryInterface $transacRepository,
        SearchCriteriaBuilder $searchBuilder,
        Config $config
    ) {
        $this->transacRepository = $transacRepository;
        $this->searchBuilder = $searchBuilder;
        $this->config = $config;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObject $paymentDO */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var InfoInterface $payment */
        $payment = $paymentDO->getPayment();

        $transactionIds = [];
        $transactionAmounts = [];
        
        // Build search criteria
        $searchCriteria = $this->searchBuilder
            ->addFilter('payment_id', $payment->getId())
            ->addFilter('txn_type', Transaction::TYPE_AUTH)
            ->create();
        
        // Get all AUTH transactions for this payment
        $transactions = $this->transacRepository->getList($searchCriteria);

        foreach ($transactions->getItems() as $transaction) {
            // Skip if it's a parent transaction (order transaction)
            if (!$transaction->getParentTxnId()) {
                continue;
            }
            
            $transactionId = $transaction->getTxnId();
            $transactionIds[] = $transactionId;
            
            // Get transaction additional information to retrieve amount
            $additionalInfo = $transaction->getAdditionalInformation();
            $amountAuthorized = 0;
            
            // Try to get from RAW_DETAILS first
            if (isset($additionalInfo[Transaction::RAW_DETAILS])) {
                $rawDetails = $additionalInfo[Transaction::RAW_DETAILS];
                if (isset($rawDetails['amount_authorized'])) {
                    $amountAuthorized = $rawDetails['amount_authorized'];
                } elseif (isset($rawDetails['base_amount_authorized'])) {
                    $amountAuthorized = $rawDetails['base_amount_authorized'];
                }
            }
            
            // If not found in RAW_DETAILS, try other sources
            if ($amountAuthorized === 0) {
                // Try to get from raw_details_info
                if (isset($additionalInfo['raw_details_info']['amount']['value'])) {
                    // This value is already in cents from PagBank
                    $amountAuthorized = $additionalInfo['raw_details_info']['amount']['value'] / 100;
                } elseif (isset($additionalInfo['amount'])) {
                    $amountAuthorized = $additionalInfo['amount'];
                } else {
                    // Fallback: determine from payment additional information
                    $cardIndex = count($transactionIds) - 1;
                    if ($cardIndex === 0) {
                        $amountAuthorized = $payment->getAdditionalInformation('first_card_amount') ?: 0;
                    } else {
                        $amountAuthorized = $payment->getAdditionalInformation('second_card_amount') ?: 0;
                    }
                }
            }
            
            // Format the amount for the specific transaction
            $transactionAmounts[$transactionId] = [
                self::AMOUNT_VALUE => $this->config->formatPrice($amountAuthorized),
                self::AMOUNT_CURRENCY => 'BRL'
            ];
        }

        // Ensure we have exactly 2 transactions
        if (count($transactionIds) !== 2) {
            throw new LocalizedException(
                __('Expected 2 authorization transactions for two card payment, found %1', count($transactionIds))
            );
        }

        return [
            self::PAGBANK_PAYMENT_IDS => $transactionIds,
            self::TRANSACTION_AMOUNTS => $transactionAmounts
        ];
    }
}
