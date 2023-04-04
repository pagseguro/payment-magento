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

namespace PagBank\PaymentMagento\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config Boleto - Returns form of payment configuration properties for Boleto.
 */
class ConfigBoleto extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'pagbank_paymentmagento_boleto';

    /**
     * @const string
     */
    public const ACTIVE = 'active';

    /**
     * @const string
     */
    public const TITLE = 'title';

    /**
     * @const string
     */
    public const INSTRUCTION_CHECKOUT = 'instruction_checkout';

    /**
     * @const string
     */
    public const EXPIRATION = 'expiration';

    /**
     * @const string
     */
    public const INSTRUCTION_LINE = 'instruction_line';

    /**
     * @const string
     */
    public const GET_TAX_ID = 'get_tax_id';

    /**
     * @const string
     */
    public const GET_NAME = 'get_name';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime             $date
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DateTime $date,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->date = $date;
    }

    /**
     * Get Payment configuration status.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isActive($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::ACTIVE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get title of payment.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getTitle($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::TITLE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Instruction - Checkoout.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getInstructionCheckout($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_CHECKOUT),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Expiration.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getExpiration($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('Y-m-d', strtotime("+{$due} days"));
    }

    /**
     * Get Expiration Formart.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getExpirationFormat($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('d/m/Y', strtotime("+{$due} days"));
    }

    /**
     * Get Instruction Line.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getInstructionLine($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_LINE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get if tax id capture on the form.
     *
     * @param int|null $storeId
     *
     * @return bool|null
     */
    public function hasTaxIdCapture($storeId = null): ?bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::GET_TAX_ID),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get if name capture on the form.
     *
     * @param int|null $storeId
     *
     * @return bool|null
     */
    public function hasNameCapture($storeId = null): ?bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::GET_NAME),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
