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
use PagBank\PaymentMagento\Api\GuestListInstallmentsManagementInterface;
use PagBank\PaymentMagento\Api\ListInstallmentsManagementInterface;

/**
 * Class Guest List Installments Management - Generate list installments.
 */
class GuestListInstallmentsManagement implements GuestListInstallmentsManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var ListInstallmentsManagementInterface
     */
    protected $cardNumberInterface;

    /**
     * @param \Magento\Quote\Model\QuoteIdMaskFactory                         $quoteIdMaskFactory
     * @param \PagBank\PaymentMagento\Api\ListInstallmentsManagementInterface $cardNumberInterface
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ListInstallmentsManagementInterface $cardNumberInterface
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cardNumberInterface = $cardNumberInterface;
    }

    /**
     * Generate List Installments.
     *
     * @param string                                                  $cartId
     * @param \PagBank\PaymentMagento\Api\Data\CreditCardBinInterface $creditCardBin
     *
     * @return array
     */
    public function generateListInstallments(
        $cartId,
        CreditCardBinInterface $creditCardBin
    ) {
        /** @var \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->cardNumberInterface->generateListInstallments(
            $quoteIdMask->getQuoteId(),
            $creditCardBin
        );
    }
}
