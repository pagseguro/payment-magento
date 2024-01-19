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

use PagBank\PaymentMagento\Api\Data\ThreeDsSessionDataInterface;

/**
 * Class 3ds Session - Model data.
 */
class Session implements ThreeDsSessionDataInterface
{
    /**
     * @var string $sessionId
     */
    protected $sessionId;

    /**
     * @var string $expiresAt
     */
    protected $expiresAt;

    /**
     * @inheritdoc
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @inheritdoc
     */
    public function setSessionId($sessionId)
    {
        return $this->sessionId = $sessionId;
    }

    /**
     * @inheritdoc
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @inheritdoc
     */
    public function setExpiresAt($expiresAt)
    {
        return $this->expiresAt = $expiresAt;
    }
}
