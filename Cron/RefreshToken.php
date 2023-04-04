<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Cron;

use Magento\Payment\Model\Method\Logger;
use PagBank\PaymentMagento\Model\Console\Command\Basic\Refresh;

/**
 * Class CronTab Refresh Token.
 */
class RefreshToken
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Refresh
     */
    protected $refresh;

    /**
     * Constructor.
     *
     * @param Logger  $logger
     * @param Refresh $refresh
     */
    public function __construct(
        Logger $logger,
        Refresh $refresh
    ) {
        $this->logger = $logger;
        $this->refresh = $refresh;
    }

    /**
     * Execute the cron.
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->debug([
            'cron'   => 'refresh_token',
            'status' => 'Cronjob RefreshToken is executing.',
        ]);
        $this->refresh->newToken();
        $this->logger->debug([
            'cron'   => 'refresh_token',
            'status' => 'Cronjob RefreshToken is done.',
        ]);
    }
}
