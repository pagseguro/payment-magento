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
use PagBank\PaymentMagento\Api\Data\CreditCardBinInterface;
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
     * @param int                                                           $cartId
     * @param \PagBank\PaymentMagento\Api\Data\CreditCardBinInterface       $creditCardBin
     * @param \PagBank\PaymentMagento\Api\Data\InstallmentSelectedInterface $installmentSelected
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function generatePagBankInterest(
        $cartId,
        CreditCardBinInterface $creditCardBin,
        InstallmentSelectedInterface $installmentSelected
    ) {
        $interest = 0;
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $quoteTotal = $this->quoteTotalRepository->get($cartId);

        $creditCardBin = $creditCardBin->getCreditCardBin();

        $installmentSelected = $installmentSelected->getInstallmentSelected();

        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);

        $amount = $quoteTotal->getBaseGrandTotal();
        $currentInterest = $quote->getData(InstallmentSelectedInterface::PAGBANK_INTEREST_AMOUNT);
        $amount -= $currentInterest;
        $amount = $this->configBase->formatPrice($amount);

        if ($creditCardBin) {
            $pagBankInterests = $this->consultInstallments->getPagBankInstallments(
                $storeId,
                $creditCardBin,
                $amount
            );

            if (isset($pagBankInterests[$installmentSelected - 1])) {
                $installment = $pagBankInterests[$installmentSelected - 1];
                if (isset($installment['amount']['fees'])) {
                    $interest = $installment['amount']['fees']['buyer']['interest']['total'];
                }
            }
        }

        $interest = $interest / 100;

        try {
            $quote->setData(InstallmentSelectedInterface::PAGBANK_INTEREST_AMOUNT, $interest);
            $quote->setData(InstallmentSelectedInterface::BASE_PAGBANK_INTEREST_AMOUNT, $interest);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save interest.'));
        }

        return $this->quoteTotalRepository->get($cartId);
    }
}
