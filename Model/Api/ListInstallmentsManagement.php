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
use PagBank\PaymentMagento\Api\Data\CardTypeTransactionInterface;
use PagBank\PaymentMagento\Api\Data\CreditCardBinInterface;
use PagBank\PaymentMagento\Api\Data\CustomAmountInterface;
use PagBank\PaymentMagento\Api\Data\InstallmentSelectedInterface;
use PagBank\PaymentMagento\Api\ListInstallmentsManagementInterface;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;

/**
 * Class List Installments Management - Generate list installments.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ListInstallmentsManagement implements ListInstallmentsManagementInterface
{
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
     * ListInstallmentsManagement constructor.
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
     * Generate List Installments.
     *
     * @param int                                                                   $cartId
     * @param \PagBank\PaymentMagento\Api\Data\CreditCardBinInterface               $creditCardBin
     * @param \PagBank\PaymentMagento\Api\Data\CardTypeTransactionInterface|null    $cardTypeTransaction
     * @param \PagBank\PaymentMagento\Api\Data\CustomAmountInterface|null           $customAmount
     * @param \PagBank\PaymentMagento\Api\Data\CardIndexInterface|null              $cardIndex
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function generateListInstallments(
        $cartId,
        CreditCardBinInterface $creditCardBin,
        ?CardTypeTransactionInterface $cardTypeTransaction = null,
        ?CustomAmountInterface $customAmount = null,
        ?CardIndexInterface $cardIndex = null
    ) {
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $quoteTotal = $this->quoteTotalRepository->get($cartId);
        $creditCardBin = $creditCardBin->getCreditCardBin();

        $ccTransactionValue = null;
        if ($cardTypeTransaction) {
            $ccTransactionValue = $cardTypeTransaction->getCardTypeTransaction();
        }

        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);
        
        $customAmountValue = null;
        $cardIndexValue = null;
        
        if ($customAmount) {
            $customAmountValue = $customAmount->getCustomAmount();
        }
        
        if ($cardIndex) {
            $cardIndexValue = $cardIndex->getCardIndex();
        }

        $currentInterest = $quote->getData(InstallmentSelectedInterface::PAGBANK_INTEREST_AMOUNT);
        $amount = $quoteTotal->getBaseGrandTotal();

        if ($cardIndexValue === 1) {
            $amount = $customAmountValue;
        }

        if ($cardIndexValue === 2) {
            $amount -= $customAmountValue;
            $amount -= $currentInterest;
        }
        
        if ($cardIndexValue === null) {
            $amount -= $currentInterest;
        }

        $amount = $this->configBase->formatPrice($amount);

        $listInstallments = $this->consultInstallments->getPagBankInstallments(
            $storeId,
            $creditCardBin,
            $amount,
            $ccTransactionValue
        );

        return $listInstallments;
    }
}