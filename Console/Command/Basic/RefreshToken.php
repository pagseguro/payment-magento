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

namespace PagBank\PaymentMagento\Console\Command\Basic;

use Magento\Framework\App\State;
use PagBank\PaymentMagento\Model\Console\Command\Basic\Refresh;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshToken extends Command
{
    /**
     * Store Id.
     */
    public const STORE_ID = 'store_id';

    /**
     * @var Refresh
     */
    protected $refresh;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State   $state
     * @param Refresh $refresh
     */
    public function __construct(
        State $state,
        refresh $refresh
    ) {
        $this->state = $state;
        $this->refresh = $refresh;
        parent::__construct();
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->refresh->setOutput($output);

        $storeId = $input->getArgument(self::STORE_ID);

        return $this->refresh->newToken($storeId);
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('pagbank:basic:refresh_token');
        $this->setDescription('Refresh Token');
        $this->setDefinition(
            [new InputArgument(self::STORE_ID, InputArgument::OPTIONAL, 'Store Id')]
        );
        parent::configure();
    }
}
