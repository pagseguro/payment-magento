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
 * Interface 3ds Session.
 *
 * @api
 *
 * @since 100.0.1
 */
interface ThreeDsSessionDataInterface
{
    /**
     * Return Three Ds Session.
     *
     * @return string|null
     */
    public function getSessionId();

    /**
     * Set Three Ds Session Id.
     *
     * @param string $sessionId
     *
     * @return $this
     */
    public function setSessionId($sessionId);

    /**
     * Return Three Ds Expires At.
     *
     * @return string|null
     */
    public function getExpiresAt();

    /**
     * Set Three Ds Session Expires At.
     *
     * @param string $expiresAt
     *
     * @return $this
     */
    public function setExpiresAt($expiresAt);
}
