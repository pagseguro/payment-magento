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

use Magento\Quote\Model\QuoteIdMaskFactory;
use PagBank\PaymentMagento\Api\Data\CreditCardBinInterface;
use PagBank\PaymentMagento\Api\Data\InstallmentSelectedInterface;
use PagBank\PaymentMagento\Api\GuestInterestManagementInterface;
use PagBank\PaymentMagento\Api\InterestManagementInterface;

/**
 * Class Guest Interest Management - Generate interest.
 */
class GuestInterestManagement implements GuestInterestManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var InterestManagementInterface
     */
    protected $cardNumberInterface;

    /**
     * @param \Magento\Quote\Model\QuoteIdMaskFactory                 $quoteIdMaskFactory
     * @param \PagBank\PaymentMagento\Api\InterestManagementInterface $cardNumberInterface
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        InterestManagementInterface $cardNumberInterface
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cardNumberInterface = $cardNumberInterface;
    }

    /**
     * Generate List Installments.
     *
     * @param string                                                        $cartId
     * @param \PagBank\PaymentMagento\Api\Data\CreditCardBinInterface       $creditCardBin
     * @param \PagBank\PaymentMagento\Api\Data\InstallmentSelectedInterface $installmentSelected
     *
     * @return array
     */
    public function generatePagBankInterest(
        $cartId,
        CreditCardBinInterface $creditCardBin,
        InstallmentSelectedInterface $installmentSelected
    ) {
        /** @var \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->cardNumberInterface->generatePagBankInterest(
            $quoteIdMask->getQuoteId(),
            $creditCardBin,
            $installmentSelected
        );
    }
}
