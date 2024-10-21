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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config Cc - Returns form of payment configuration properties for Credit Card.
 */
class ConfigCc extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'pagbank_paymentmagento_cc';

    /**
     * @const string
     */
    public const CC_TYPES = 'payment/pagbank_paymentmagento_cc/cctypes';

    /**
     * @const string
     */
    public const CVV_ENABLED = 'cvv_enabled';

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
    public const CC_MAPPER = 'cctypes_mapper';

    /**
     * @const string
     */
    public const GET_TAX_ID = 'get_tax_id';

    /**
     * @const string
     */
    public const GET_PHONE = 'get_phone';

    /**
     * @const string
     */
    public const PAYMENT_ACTION = 'payment_action';

    /**
     * @const string
     */
    public const USE_THREE_DS_AUTH = 'use_three_ds';

    /**
     * @const string
     */
    public const REJECT_NOT_AUTH = 'reject_not_auth';

    /**
     * @const string
     */
    public const INSTRUCTION_THREE_DS = 'instruction_three_ds';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Json                 $json
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $json,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
    }

    /**
     * Should the cvv field be shown.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isCvvEnabled($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CVV_ENABLED),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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
     * Get if document capture on the form.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasTaxIdCapture($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::GET_TAX_ID),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get if phone capture on the form.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasPhoneCapture($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::GET_PHONE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Has 3ds Auth.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasThreeDsAuth($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::USE_THREE_DS_AUTH),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is Active Debit.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isActiveDebit($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, 'enable_debit'),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is Active Debit.
     *
     * @param int|null $storeId
     *
     * @return int
     */
    public function getMaxTryPlaceOrder($storeId = null): int
    {
        $pathPattern = 'payment/%s/%s';

        return (int) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, 'three_ds_max_try_place_order'),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Instruction for 3ds.
     *
     * @param string|null $storeId
     *
     * @return string
     */
    public function getInstructionForThreeDs($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_THREE_DS),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Has 3ds Env.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getThreeDsEnv($storeId = null): string
    {
        $env = $this->scopeConfig->getValue(
            'payment/pagbank_paymentmagento/environment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($env === 'sandbox') {
            return 'SANDBOX';
        }

        return 'PROD';
    }

    /**
     * Has Reject Not Auth.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasRejectNotAuth($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::REJECT_NOT_AUTH),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Has Capture.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasCapture($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';
        $typePaymentAction = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::PAYMENT_ACTION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($typePaymentAction === AbstractMethod::ACTION_AUTHORIZE) {
            return false;
        }

        return true;
    }

    /**
     * Should the cc types.
     *
     * @param int|null $storeId
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getCcAvailableTypes($storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::CC_TYPES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Cc Mapper.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCcTypesMapper($storeId = null): array
    {
        $pathPattern = 'payment/%s/%s';

        $ccTypesMapper = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CC_MAPPER),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $result = $this->json->unserialize($ccTypesMapper);

        return is_array($result) ? $result : [];
    }

    /**
     * Get Max Installments.
     *
     * @param int|null $storeId
     *
     * @return int
     */
    public function getMaxInstallments($storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            'payment/pagbank_paymentmagento_cc/max_installment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Interest Free.
     *
     * @param int|null $storeId
     *
     * @return int
     */
    public function getInterestFree($storeId = null): int
    {
        $free = (int) $this->scopeConfig->getValue(
            'payment/pagbank_paymentmagento_cc/interest_free',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($free === 1) {
            return 0;
        }

        return $free;
    }

    /**
     * Get Min Value Installments.
     *
     * @param int|null $storeId
     *
     * @return int
     */
    public function getMinValuelInstallment($storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            'payment/pagbank_paymentmagento_cc/min_value_installment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the configured minimum order total for 3DS.
     *
     * @param int|null $storeId
     * @return float
     */
    public function getThreeDsMinOrderTotal($storeId = null): float
    {
        $pathPattern = 'payment/%s/%s';

        return (float) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, 'three_ds_min_order_total'),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the configured SKUs that require 3DS.
     *
     * @param int|null $storeId
     * @return array
     */
    public function getThreeDsSkus($storeId = null): array
    {
        $pathPattern = 'payment/%s/%s';

        $threeDsSkus = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, 'three_ds_has_sku'),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $threeDsSkus ? explode(',', $threeDsSkus) : [];
    }

    /**
     * Is 3ds Applicable.
     *
     * @param \Magento\Checkout\Model\Cart $cart
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isThreeDsApplicable(\Magento\Checkout\Model\Cart $cart, $storeId = null): bool
    {
        $quote = $cart->getQuote();

        if (!$this->hasThreeDsAuth($storeId)) {
            return false;
        }

        $threeDsMinOrderTotal = (float) $this->getThreeDsMinOrderTotal($storeId);

        $total = $quote->getGrandTotal();

        if ($total >= $threeDsMinOrderTotal) {
            return true;
        }

        $threeDsSkus = $this->getThreeDsSkus($storeId);

        if ($threeDsSkus) {
            $items = $quote->getAllItems();

            foreach ($items as $item) {
                if (in_array($item->getSku(), $threeDsSkus, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
