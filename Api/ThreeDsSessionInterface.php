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

namespace PagBank\PaymentMagento\Api;

use PagBank\PaymentMagento\Api\Data\ThreeDsSessionDataInterface;

/**
 * Interface for obtaining session for 3ds.
 *
 * @api
 */
interface ThreeDsSessionInterface
{
    /**
     * Get 3ds Session.
     *
     * @return \PagBank\PaymentMagento\Api\Data\ThreeDsSessionDataInterface
     */
    public function getSession(): ThreeDsSessionDataInterface;
}
