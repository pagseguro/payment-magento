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
     * Constructor.
     *
     * @param CartRepositoryInterface      $quoteRepository
     * @param CartTotalRepositoryInterface $quoteTotalRepository
     * @param ConfigBase                   $configBase
     * @param ConsultPSInstallments        $consultInstallments
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        CartTotalRepositoryInterface $quoteTotalRepository,
        ConfigBase $configBase,
        ConsultPSInstallments $consultInstallments
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteTotalRepository = $quoteTotalRepository;
        $this->configBase = $configBase;
        $this->consultInstallments = $consultInstallments;
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
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
        $interest = 0;
        $cardIndexValue = null;

        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $quoteTotal = $this->quoteTotalRepository->get($cartId);

        $creditCardBinValue = $creditCardBin->getCreditCardBin();
        $installmentSelectedValue = $installmentSelected->getInstallmentSelected();
        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);
        $amount = $quoteTotal->getBaseGrandTotal();

        if ($installmentSelectedValue === 0) {
            if (!$cardIndex) {
                $quote->setData(InstallmentSelectedInterface::PAGBANK_INTEREST_AMOUNT, 0);
                $quote->setData(InstallmentSelectedInterface::BASE_PAGBANK_INTEREST_AMOUNT, 0);
                $this->quoteRepository->save($quote);
                return $this->quoteTotalRepository->get($cartId);
            }

            $cardIndexValue = $cardIndex->getCardIndex();
            if ($cardIndexValue === 1) {
                $quote->setData(InstallmentSelectedInterface::PAGBANK_INTEREST_AMOUNT, 0);
                $quote->setData(InstallmentSelectedInterface::BASE_PAGBANK_INTEREST_AMOUNT, 0);
                $this->quoteRepository->save($quote);
                return $this->quoteTotalRepository->get($cartId);
            }
        }

        if ($cardIndex !== null) {
            $cardIndexValue = $cardIndex->getCardIndex();
            $customAmountValue = $customAmount ? (float) $customAmount->getCustomAmount() : 0.0;

            if ($cardIndexValue === 1) {
                $amount = $customAmountValue;
            }

            if ($cardIndexValue === 2) {
                $card1Interest = 0.0;
                if ($creditCardBinCard1 && $installmentSelectedCard1) {
                    $card1Interest = $this->fetchInterest(
                        $storeId,
                        (string) $creditCardBinCard1->getCreditCardBin(),
                        $customAmountValue,
                        (int) $installmentSelectedCard1->getInstallmentSelected()
                    );
                }

                $amount -= $customAmountValue;
                $amount -= $card1Interest;
            }
        }

        $amount = $this->configBase->formatPrice($amount);

        if ($creditCardBinValue) {
            $pagBankInterests = $this->consultInstallments->getPagBankInstallments(
                $storeId,
                $creditCardBinValue,
                $amount
            );

            if (isset($pagBankInterests[$installmentSelectedValue - 1])) {
                $installment = $pagBankInterests[$installmentSelectedValue - 1];
                if (isset($installment['amount']['fees'])) {
                    $interest = $installment['amount']['fees']['buyer']['interest']['total'];
                }
            }
        }

        $interest = $interest / 100;

        try {
            if ($cardIndexValue === 2) {
                $interest += $card1Interest;
            }

            $quote->setData(InstallmentSelectedInterface::PAGBANK_INTEREST_AMOUNT, $interest);
            $quote->setData(InstallmentSelectedInterface::BASE_PAGBANK_INTEREST_AMOUNT, $interest);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save interest.'));
        }

        return $this->quoteTotalRepository->get($cartId);
    }
}