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

namespace PagBank\PaymentMagento\Api;

/**
 * Interface for obtaining a list of installments in PagBank payments in the order quote.
 *
 * @api
 */
interface GuestListInstallmentsManagementInterface
{
    /**
     * Generate the list installments by credit card.
     *
     * @param string                                                            $cartId
     * @param \PagBank\PaymentMagento\Api\Data\CreditCardBinInterface           $creditCardBin
     * @param \PagBank\PaymentMagento\Api\Data\CardTypeTransactionInterface     $cardTypeTransaction
     * @param \PagBank\PaymentMagento\Api\Data\CustomAmountInterface|null       $customAmount
     * @param \PagBank\PaymentMagento\Api\Data\CardIndexInterface|null          $cardIndex
     *
     * @return mixed
     */
    public function generateListInstallments(
        $cartId,
        \PagBank\PaymentMagento\Api\Data\CreditCardBinInterface $creditCardBin,
        \PagBank\PaymentMagento\Api\Data\CardTypeTransactionInterface $cardTypeTransaction = null,
        ?\PagBank\PaymentMagento\Api\Data\CustomAmountInterface $customAmount = null,
        ?\PagBank\PaymentMagento\Api\Data\CardIndexInterface $cardIndex = null
    );
}
