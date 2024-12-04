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

namespace PagBank\PaymentMagento\Model\Api\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use PagBank\PaymentMagento\Api\Data\PagBankVaultTokenInterface;

/**
 * Class PagBank Vault Token - Model data.
 */
class PagBankVaultToken extends AbstractSimpleObject implements PagBankVaultTokenInterface
{
    /**
     * @inheritdoc
     */
    public function getPagBankToken()
    {
        return $this->_get(self::PAGBANK_TOKEN);
    }

    /**
     * @inheritdoc
     */
    public function setPagBankToken($token)
    {
        return $this->setData(self::PAGBANK_TOKEN, $token);
    }

    /**
     * @inheritdoc
     */
    public function getWebsiteId()
    {
        return $this->_get(self::WEBSITE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setWebsiteId($websiteId)
    {
        return $this->setData(self::WEBSITE_ID, $websiteId);
    }

    /**
     * @inheritdoc
     */
    public function getPublicHash()
    {
        return $this->_get(self::PUBLIC_HASH);
    }

    /**
     * @inheritdoc
     */
    public function setPublicHash($hash)
    {
        return $this->setData(self::PUBLIC_HASH, $hash);
    }

    /**
     * @inheritdoc
     */
    public function getCardBrand()
    {
        return $this->_get(self::CARD_BRAND);
    }

    /**
     * @inheritdoc
     */
    public function setCardBrand($brand)
    {
        return $this->setData(self::CARD_BRAND, $brand);
    }

    /**
     * @inheritdoc
     */
    public function getLastDigits()
    {
        return $this->_get(self::LAST_DIGITS);
    }

    /**
     * @inheritdoc
     */
    public function setLastDigits($lastDigits)
    {
        return $this->setData(self::LAST_DIGITS, $lastDigits);
    }

    /**
     * @inheritdoc
     */
    public function getExpirationDate()
    {
        return $this->_get(self::EXPIRATION_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setExpirationDate($expirationDate)
    {
        return $this->setData(self::EXPIRATION_DATE, $expirationDate);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getIsActive()
    {
        return $this->_get(self::IS_ACTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }
}
