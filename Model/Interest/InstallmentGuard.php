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

namespace PagBank\PaymentMagento\Model\Interest;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use PagBank\PaymentMagento\Api\Data\InstallmentSelectedInterface;
use PagBank\PaymentMagento\Gateway\Config\ConfigCc;
use Psr\Log\LoggerInterface;

/**
 * Ensures the order is charged with the installments the stored interest was priced for.
 *
 * The interest is priced by /pagbank-interest from the installment the shopper selected, while the installment
 * sent to PagBank comes from the payment data at place order. Without this check a shopper can price 1x
 * (no interest) and place the order in 12x, and PagBank accepts it as a merchant-paid installment plan.
 */
class InstallmentGuard
{
    public const METHOD_CC = 'pagbank_paymentmagento_cc';

    public const METHOD_CC_VAULT = 'pagbank_paymentmagento_cc_vault';

    public const METHOD_TWO_CC = 'pagbank_paymentmagento_two_cc';

    /**
     * Rounding tolerance between amounts, in cents.
     */
    private const TOLERANCE_IN_CENTS = 1;

    private const DEBIT_CARD = 'DEBIT_CARD';

    /**
     * @var InterestContext
     */
    private $interestContext;

    /**
     * @var ConfigCc
     */
    private $configCc;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param InterestContext $interestContext
     * @param ConfigCc        $configCc
     * @param LoggerInterface $logger
     */
    public function __construct(
        InterestContext $interestContext,
        ConfigCc $configCc,
        LoggerInterface $logger
    ) {
        $this->interestContext = $interestContext;
        $this->configCc = $configCc;
        $this->logger = $logger;
    }

    /**
     * Validate the quote about to be submitted.
     *
     * @param Quote $quote
     *
     * @throws LocalizedException when installments or amounts differ from what the interest was priced for
     *
     * @return float|null first card interest (two-card payments only), already validated
     */
    public function validate(Quote $quote): ?float
    {
        $payment = $quote->getPayment();
        $method = $payment ? (string) $payment->getMethod() : '';

        if ($method === self::METHOD_CC || $method === self::METHOD_CC_VAULT) {
            $this->validateSingleCard($quote);

            return null;
        }

        if ($method === self::METHOD_TWO_CC) {
            return $this->validateTwoCards($quote);
        }

        return null;
    }

    /**
     * Single card (also used by the saved-card method).
     *
     * @param Quote $quote
     *
     * @throws LocalizedException
     *
     * @return void
     */
    private function validateSingleCard(Quote $quote): void
    {
        $payment = $quote->getPayment();
        $installments = $this->installments($payment->getAdditionalInformation('cc_installments'));
        $context = $this->interestContext->read($quote);
        $card = ($context[InterestContext::MODE] ?? null) === InterestContext::MODE_SINGLE
            ? ($context[InterestContext::CARD] ?? null)
            : null;

        if ($payment->getAdditionalInformation('card_type_transaction') === self::DEBIT_CARD && $installments !== 1) {
            $this->reject($quote, 'debit card with installments', ['installments' => $installments]);
        }

        $interestInCents = $this->storedInterestInCents($quote);
        $this->assertCard(
            $quote,
            'single',
            $card,
            $installments,
            $this->totalWithoutInterestInCents($quote, $interestInCents)
        );
        $this->assertInterestTotal($quote, [$card], $interestInCents);
    }

    /**
     * Two cards: each card must match what was priced, and the split must cover the order.
     *
     * @param Quote $quote
     *
     * @throws LocalizedException
     *
     * @return float
     */
    private function validateTwoCards(Quote $quote): float
    {
        $payment = $quote->getPayment();
        $context = $this->interestContext->read($quote);
        $isTwo = ($context[InterestContext::MODE] ?? null) === InterestContext::MODE_TWO;
        $cardOne = $isTwo ? ($context[InterestContext::CARD_ONE] ?? null) : null;
        $cardTwo = $isTwo ? ($context[InterestContext::CARD_TWO] ?? null) : null;

        $interestInCents = $this->storedInterestInCents($quote);
        $totalInCents = $this->totalWithoutInterestInCents($quote, $interestInCents);
        $cardOneAmount = $this->interestContext->toCents($payment->getAdditionalInformation('first_card_amount'));
        $cardTwoAmount = $totalInCents - $cardOneAmount;

        if ($cardOneAmount <= 0 || $cardTwoAmount <= 0) {
            $this->reject($quote, 'invalid two-card split', [
                'first_card_amount' => $cardOneAmount,
                'total'             => $totalInCents,
            ]);
        }

        $this->assertCard(
            $quote,
            'card1',
            $cardOne,
            $this->installments($payment->getAdditionalInformation('first_card_cc_installments')),
            $cardOneAmount
        );
        $this->assertCard(
            $quote,
            'card2',
            $cardTwo,
            $this->installments($payment->getAdditionalInformation('second_card_cc_installments')),
            $cardTwoAmount
        );
        $this->assertInterestTotal($quote, [$cardOne, $cardTwo], $interestInCents);

        return $this->isPriced($cardOne) ? $cardOne[InterestContext::INTEREST] / 100 : 0.0;
    }

    /**
     * A priced card must keep its installment and base amount; an unpriced one only passes interest-free.
     *
     * @param Quote      $quote
     * @param string     $label
     * @param array|null $card
     * @param int        $installments
     * @param int        $baseInCents
     *
     * @throws LocalizedException
     *
     * @return void
     */
    private function assertCard(Quote $quote, string $label, ?array $card, int $installments, int $baseInCents): void
    {
        if (!$this->isPriced($card)) {
            $limit = max(1, $this->configCc->getInterestFree((int) $quote->getStoreId()));
            if ($installments > $limit) {
                $this->reject($quote, 'unpriced installments above interest-free limit', [
                    'card'         => $label,
                    'installments' => $installments,
                    'limit'        => $limit,
                ]);
            }

            return;
        }

        $pricedInstallment = (int) $card[InterestContext::INSTALLMENT];
        $pricedBase = (int) $card[InterestContext::BASE];

        if ($pricedInstallment !== $installments || abs($pricedBase - $baseInCents) > self::TOLERANCE_IN_CENTS) {
            $this->reject($quote, 'installments or amount differ from priced', [
                'card'               => $label,
                'installments'       => $installments,
                'priced_installment' => $pricedInstallment,
                'base'               => $baseInCents,
                'priced_base'        => $pricedBase,
            ]);
        }
    }

    /**
     * The interest on the quote must be the sum of what was priced (unpriced cards contribute none).
     *
     * @param Quote $quote
     * @param array $cards
     * @param int   $interestInCents
     *
     * @throws LocalizedException
     *
     * @return void
     */
    private function assertInterestTotal(Quote $quote, array $cards, int $interestInCents): void
    {
        $priced = 0;
        foreach ($cards as $card) {
            $priced += $this->isPriced($card) ? (int) $card[InterestContext::INTEREST] : 0;
        }

        if (abs($priced - $interestInCents) > self::TOLERANCE_IN_CENTS) {
            $this->reject($quote, 'stored interest differs from priced', [
                'interest'        => $interestInCents,
                'priced_interest' => $priced,
            ]);
        }
    }

    /**
     * Whether a card entry was priced with a real installment.
     *
     * @param mixed $card
     *
     * @return bool
     */
    private function isPriced($card): bool
    {
        return is_array($card) && (int) ($card[InterestContext::INSTALLMENT] ?? 0) >= 1;
    }

    /**
     * Installments as sent by the checkout (missing means 1x, as the request builders assume).
     *
     * @param mixed $value
     *
     * @return int
     */
    private function installments($value): int
    {
        return max(1, (int) $value);
    }

    /**
     * Interest currently stored on the quote, in cents.
     *
     * @param Quote $quote
     *
     * @return int
     */
    private function storedInterestInCents(Quote $quote): int
    {
        return $this->interestContext->toCents(
            $quote->getData(InstallmentSelectedInterface::BASE_PAGBANK_INTEREST_AMOUNT)
        );
    }

    /**
     * Quote grand total without the PagBank interest, in cents.
     *
     * @param Quote $quote
     * @param int   $interestInCents
     *
     * @return int
     */
    private function totalWithoutInterestInCents(Quote $quote, int $interestInCents): int
    {
        return $this->interestContext->toCents($quote->getBaseGrandTotal()) - $interestInCents;
    }

    /**
     * Log the mismatch and stop the order.
     *
     * @param Quote  $quote
     * @param string $reason
     * @param array  $details
     *
     * @throws LocalizedException
     *
     * @return void
     */
    private function reject(Quote $quote, string $reason, array $details): void
    {
        $this->logger->warning('PagBank installment guard rejected order', [
            'reason'   => $reason,
            'quote_id' => $quote->getId(),
            'method'   => $quote->getPayment()->getMethod(),
            'details'  => $details,
        ]);

        throw new LocalizedException(
            __('The installment conditions have changed. Please select the installments again.')
        );
    }
}
