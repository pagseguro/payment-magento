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

namespace PagBank\PaymentMagento\Model\Interest;

use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Records the conditions (installment and base amount) under which the interest stored on the quote was priced.
 *
 * Interest is a function of the installment count, so the order must later be charged with the same installment
 * and base amount; otherwise the stored interest no longer applies.
 */
class InterestContext
{
    /**
     * Quote field holding the serialized context.
     */
    public const FIELD = 'pagbank_interest_context';

    public const MODE = 'mode';

    public const MODE_SINGLE = 'single';

    public const MODE_TWO = 'two';

    public const CARD = 'card';

    public const CARD_ONE = 'card1';

    public const CARD_TWO = 'card2';

    public const INSTALLMENT = 'installment';

    public const BASE = 'base';

    public const INTEREST = 'interest';

    /**
     * @var Json
     */
    private $json;

    /**
     * @param Json $json
     */
    public function __construct(Json $json)
    {
        $this->json = $json;
    }

    /**
     * Read the context stored on the quote.
     *
     * @param DataObject $quote
     *
     * @return array|null
     */
    public function read(DataObject $quote): ?array
    {
        $raw = $quote->getData(self::FIELD);

        if (!is_string($raw) || $raw === '') {
            return null;
        }

        try {
            $context = $this->json->unserialize($raw);
        } catch (\InvalidArgumentException $exc) {
            return null;
        }

        return is_array($context) ? $context : null;
    }

    /**
     * Store the context on the quote (null clears it).
     *
     * @param DataObject $quote
     * @param array|null $context
     *
     * @return void
     */
    public function write(DataObject $quote, ?array $context): void
    {
        $quote->setData(self::FIELD, $context === null ? null : $this->json->serialize($context));
    }

    /**
     * Describe one priced card.
     *
     * @param int   $installment
     * @param float $baseAmount
     * @param float $interest
     *
     * @return array
     */
    public function card(int $installment, float $baseAmount, float $interest): array
    {
        return [
            self::INSTALLMENT => $installment,
            self::BASE        => $this->toCents($baseAmount),
            self::INTEREST    => $this->toCents($interest),
        ];
    }

    /**
     * Convert an amount in reais to cents.
     *
     * @param mixed $amount
     *
     * @return int
     */
    public function toCents($amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
