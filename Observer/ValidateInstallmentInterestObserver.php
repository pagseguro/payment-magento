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

namespace PagBank\PaymentMagento\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use PagBank\PaymentMagento\Model\Interest\InstallmentGuard;

/**
 * Blocks the order before it reaches PagBank when installments differ from what the interest was priced for.
 */
class ValidateInstallmentInterestObserver implements ObserverInterface
{
    /**
     * Payment key read by the two-card request builder.
     */
    public const FIRST_CARD_INTEREST = 'first_card_interest_amount';

    /**
     * @var InstallmentGuard
     */
    private $installmentGuard;

    /**
     * @param InstallmentGuard $installmentGuard
     */
    public function __construct(InstallmentGuard $installmentGuard)
    {
        $this->installmentGuard = $installmentGuard;
    }

    /**
     * Execute.
     *
     * @param Observer $observer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getData('quote');
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');

        $firstCardInterest = $this->installmentGuard->validate($quote);

        if ($firstCardInterest !== null && $order && $order->getPayment()) {
            $order->getPayment()->setAdditionalInformation(self::FIRST_CARD_INTEREST, $firstCardInterest);
        }
    }
}
