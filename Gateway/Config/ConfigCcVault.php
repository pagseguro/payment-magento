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
use Magento\Payment\Gateway\Config\Config as PaymentConfig;

/**
 * Class Config Cc Vault - Returns form of payment configuration properties for Credit Card Vault.
 */
class ConfigCcVault extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'pagbank_paymentmagento_cc_vault';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
    }
}
