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

namespace PagBank\PaymentMagento\Plugin;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Class Payment Token Management Interface - Corrected duplicity.
 */
class PaymentToken
{
    /**
     * Around Save Token With Payment Link.
     *
     * @param PaymentTokenManagementInterface $vaultManagement
     * @param callable                        $proceed
     * @param PaymentTokenInterface           $token
     * @param OrderPaymentInterface           $payment
     *
     * @return $proceed
     */
    public function aroundSaveTokenWithPaymentLink(
        PaymentTokenManagementInterface $vaultManagement,
        callable $proceed,
        PaymentTokenInterface $token,
        OrderPaymentInterface $payment
    ): bool {
        $order = $payment->getOrder();

        if ($order->getCustomerIsGuest()) {
            return $proceed($token, $payment);
        }

        $existingToken = $vaultManagement->getByGatewayToken(
            $token->getGatewayToken(),
            $payment->getMethodInstance()->getCode(),
            $order->getCustomerId()
        );

        if ($existingToken === null) {
            return $proceed($token, $payment);
        }

        $existingToken->addData($token->getData());

        return $proceed($existingToken, $payment);
    }
}
