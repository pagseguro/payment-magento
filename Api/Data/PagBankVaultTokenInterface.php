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

namespace PagBank\PaymentMagento\Api\Data;

/**
 * Interface PagBank Vault Token.
 *
 * @api
 */
interface PagBankVaultTokenInterface
{
    /**
     * Constants for keys of data array.
     */
    public const PAGBANK_TOKEN = 'pagbank_token';
    public const PUBLIC_HASH = 'public_hash';
    public const CARD_BRAND = 'card_brand';
    public const LAST_DIGITS = 'last_digits';
    public const EXPIRATION_DATE = 'expiration_date';
    public const CREATED_AT = 'created_at';
    public const IS_ACTIVE = 'is_active';
    public const WEBSITE_ID = 'website_id';

    /**
     * Get PagBank Token.
     *
     * @return string|null
     */
    public function getPagBankToken();

    /**
     * Set PagBank Token.
     *
     * @param string $token
     * @return $this
     */
    public function setPagBankToken($token);

    /**
     * Get Website Id.
     *
     * @return int|null
     */
    public function getWebsiteId();

    /**
     * Set Website Id.
     *
     * @param int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId);

    /**
     * Get public hash.
     *
     * @return string|null
     */
    public function getPublicHash();

    /**
     * Set public hash.
     *
     * @param string $hash
     * @return $this
     */
    public function setPublicHash($hash);

    /**
     * Get card brand.
     *
     * @return string|null
     */
    public function getCardBrand();

    /**
     * Set card brand.
     *
     * @param string $brand
     * @return $this
     */
    public function setCardBrand($brand);

    /**
     * Get last digits.
     *
     * @return string|null
     */
    public function getLastDigits();

    /**
     * Set last digits.
     *
     * @param string $lastDigits
     * @return $this
     */
    public function setLastDigits($lastDigits);

    /**
     * Get expiration date.
     *
     * @return string|null
     */
    public function getExpirationDate();

    /**
     * Set expiration date.
     *
     * @param string $expirationDate
     * @return $this
     */
    public function setExpirationDate($expirationDate);

    /**
     * Get created at.
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created at.
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get is active.
     *
     * @return bool|null
     */
    public function getIsActive();

    /**
     * Set is active.
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive($isActive);
}
