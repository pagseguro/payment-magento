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
 * Interface for creating token vault from zero dollar transaction.
 *
 * @api
 */
interface PagBankVaultManagementInterface
{
    /**
     * Criar token vault a partir de cartão criptografado.
     *
     * @param int $customerId
     * @param string $encryptedCard
     * @return \PagBank\PaymentMagento\Api\Data\PagBankVaultTokenInterface
     */
    public function createVaultToken(
        int $customerId,
        string $encryptedCard
    );
}