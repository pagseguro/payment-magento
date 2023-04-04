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
 * Interface for obtaining interest on installments on PagBank payments in the order quote.
 *
 * @api
 */
interface ListInstallmentsManagementInterface
{
    /**
     * Generate the list installments by credit card number.
     *
     * @param int                                                     $cartId
     * @param \PagBank\PaymentMagento\Api\Data\CreditCardBinInterface $creditCardBin
     *
     * @return mixed
     */
    public function generateListInstallments(
        $cartId,
        \PagBank\PaymentMagento\Api\Data\CreditCardBinInterface $creditCardBin
    );
}
