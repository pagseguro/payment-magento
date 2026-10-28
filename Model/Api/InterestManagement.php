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

namespace PagBank\PaymentMagento\Model\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface as QuoteCartInterface;
use PagBank\PaymentMagento\Api\Data\CardIndexInterface;
use PagBank\PaymentMagento\Api\Data\CreditCardBinInterface;
use PagBank\PaymentMagento\Api\Data\CustomAmountInterface;
use PagBank\PaymentMagento\Api\Data\InstallmentSelectedInterface;
use PagBank\PaymentMagento\Api\InterestManagementInterface;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;
use PagBank\PaymentMagento\Model\Interest\InterestContext;

/**
 * Class List Interest Management - Generate Interest in order.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InterestManagement implements InterestManagementInterface
{
    /**
     * Credit Card - Block Name.
     */
    public const CREDIT_CARD = 'CREDIT_CARD';

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var CartTotalRepositoryInterface
     */
    protected $quoteTotalRepository;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var ConsultPSInstallments
     */
    protected $consultInstallments;

    /**
     * @var InterestContext
     */
    protected $interestContext;

    /**
     * Constructor.
     *
     * @param CartRepositoryInterface      $quoteRepository
     * @param CartTotalRepositoryInterface $quoteTotalRepository
     * @param ConfigBase                   $configBase
     * @param ConsultPSInstallments        $consultInstallments
     * @param InterestContext              $interestContext
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        CartTotalRepositoryInterface $quoteTotalRepository,
        ConfigBase $configBase,
        ConsultPSInstallments $consultInstallments,
        InterestContext $interestContext
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteTotalRepository = $quoteTotalRepository;
        $this->configBase = $configBase;
        $this->consultInstallments = $consultInstallments;
        $this->interestContext = $interestContext;
    }

    /**
     * Fetch interest from PagBank for a given bin, installment and amount.
     *
     * @param int    $storeId
     * @param string $bin
     * @param float  $amount
     * @param int    $installment
     *
     * @return float
     */
    private function fetchInterest(int $storeId, string $bin, float $amount, int $installment): float
    {
        if (!$bin || $installment < 2) {
            return 0.0;
        }

        $pagBankInterests = $this->consultInstallments->getPagBankInstallments(
            $storeId,
            $bin,
            $this->configBase->formatPrice($amount)
        );

        if (isset($pagBankInterests[$installment - 1]['amount']['fees'])) {
            return (float) $pagBankInterests[$installment - 1]['amount']['fees']['buyer']['interest']['total'] / 100;
        }

        return 0.0;
    }

    /**
     * Generate List Installments.
     *
     * @param int                                                                    $cartId
     * @param \PagBank\PaymentMagento\Api\Data\CreditCardBinInterface                $creditCardBin
     * @param \PagBank\PaymentMagento\Api\Data\InstallmentSelectedInterface          $installmentSelected
     * @param \PagBank\PaymentMagento\Api\Data\CustomAmountInterface|null            $customAmount
     * @param \PagBank\PaymentMagento\Api\Data\CardIndexInterface|null               $cardIndex
     * @param \PagBank\PaymentMagento\Api\Data\CreditCardBinInterface|null           $creditCardBinCard1
     * @param \PagBank\PaymentMagento\Api\Data\InstallmentSelectedInterface|null     $installmentSelectedCard1
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function generatePagBankInterest(
        $cartId,
        CreditCardBinInterface $creditCardBin,
        InstallmentSelectedInterface $installmentSelected,
        ?CustomAmountInterface $customAmount = null,
        ?CardIndexInterface $cardIndex = null,
        ?CreditCardBinInterface $creditCardBinCard1 = null,
        ?InstallmentSelectedInterface $installmentSelectedCard1 = null
    ) {
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $quoteTotal = $this->quoteTotalRepository->get($cartId);
        $storeId = (int) $quote->getData(QuoteCartInterface::KEY_STORE_ID);
        $installment = (int) $installmentSelected->getInstallmentSelected();
        $cardIndexValue = $cardIndex ? $cardIndex->getCardIndex() : null;
        $customAmountValue = $customAmount ? (float) $customAmount->getCustomAmount() : 0.0;

        // The grand total already carries the interest stored by a previous call; price on the amount without it.
        $totalWithoutInterest = (float) $quoteTotal->getBaseGrandTotal()
            - (float) $quote->getData(InstallmentSelectedInterface::BASE_PAGBANK_INTEREST_AMOUNT);

        if ($installment === 0 && ($cardIndexValue === null || $cardIndexValue === 1)) {
            return $this->saveInterest($quote, (int) $cartId, 0.0, null);
        }

        $bin = (string) $creditCardBin->getCreditCardBin();

        if ($cardIndexValue === 1) {
            $interest = $this->fetchInterest($storeId, $bin, $customAmountValue, $installment);
            $context = [
                InterestContext::MODE     => InterestContext::MODE_TWO,
                InterestContext::CARD_ONE => $this->interestContext->card($installment, $customAmountValue, $interest),
            ];

            return $this->saveInterest($quote, (int) $cartId, $interest, $context);
        }

        if ($cardIndexValue === 2) {
            $cardOne = $this->resolveCardOne(
                $storeId,
                $quote,
                $customAmountValue,
                $creditCardBinCard1,
                $installmentSelectedCard1
            );
            $amount = $totalWithoutInterest - $customAmountValue;
            $interest = $this->fetchInterest($storeId, $bin, $amount, $installment);
            $context = [
                InterestContext::MODE     => InterestContext::MODE_TWO,
                InterestContext::CARD_ONE => $cardOne,
                InterestContext::CARD_TWO => $this->interestContext->card($installment, $amount, $interest),
            ];

            return $this->saveInterest(
                $quote,
                (int) $cartId,
                $interest + $cardOne[InterestContext::INTEREST] / 100,
                $context
            );
        }

        $interest = $this->fetchInterest($storeId, $bin, $totalWithoutInterest, $installment);
        $context = [
            InterestContext::MODE => InterestContext::MODE_SINGLE,
            InterestContext::CARD => $this->interestContext->card($installment, $totalWithoutInterest, $interest),
        ];

        return $this->saveInterest($quote, (int) $cartId, $interest, $context);
    }

    /**
     * Price the first card of a two-card payment.
     *
     * Falls back to what the first-card call already priced when the request does not resend the first card
     * (the guest endpoint does not forward it).
     *
     * @param int                                   $storeId
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param float                                 $cardOneAmount
     * @param CreditCardBinInterface|null           $creditCardBinCard1
     * @param InstallmentSelectedInterface|null     $installmentSelectedCard1
     *
     * @return array
     */
    private function resolveCardOne(
        int $storeId,
        $quote,
        float $cardOneAmount,
        ?CreditCardBinInterface $creditCardBinCard1,
        ?InstallmentSelectedInterface $installmentSelectedCard1
    ): array {
        if ($creditCardBinCard1 && $installmentSelectedCard1) {
            $installment = (int) $installmentSelectedCard1->getInstallmentSelected();
            $interest = $this->fetchInterest(
                $storeId,
                (string) $creditCardBinCard1->getCreditCardBin(),
                $cardOneAmount,
                $installment
            );

            return $this->interestContext->card($installment, $cardOneAmount, $interest);
        }

        $previous = $this->interestContext->read($quote)[InterestContext::CARD_ONE] ?? null;
        if (is_array($previous)
            && abs((int) $previous[InterestContext::BASE] - $this->interestContext->toCents($cardOneAmount)) <= 1
        ) {
            return $previous;
        }

        // Unpriced: the checkout guard only accepts it for interest-free installments.
        return $this->interestContext->card(0, $cardOneAmount, 0.0);
    }

    /**
     * Persist the interest and the conditions it was priced under.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param int                                   $cartId
     * @param float                                 $interest
     * @param array|null                            $context
     *
     * @throws CouldNotSaveException
     *
     * @return \Magento\Quote\Api\Data\TotalsInterface
     */
    private function saveInterest($quote, int $cartId, float $interest, ?array $context)
    {
        try {
            $quote->setData(InstallmentSelectedInterface::PAGBANK_INTEREST_AMOUNT, $interest);
            $quote->setData(InstallmentSelectedInterface::BASE_PAGBANK_INTEREST_AMOUNT, $interest);
            $this->interestContext->write($quote, $context);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save interest.'));
        }

        return $this->quoteTotalRepository->get($cartId);
    }
}
