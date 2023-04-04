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

namespace PagBank\PaymentMagento\Console\Command\Orders;

use Magento\Framework\App\State;
use PagBank\PaymentMagento\Model\Console\Command\Orders\Update;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clas Get Update - Command to manually obtain and apply the PagBank order update status.
 */
class GetUpdate extends Command
{
    /**
     * Order Increment Id.
     */
    public const INCREMENT_ID = 'increment_id';

    /**
     * @var Update
     */
    protected $update;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State  $state
     * @param Update $update
     */
    public function __construct(
        State $state,
        Update $update
    ) {
        $this->state = $state;
        $this->update = $update;
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
        $this->update->setOutput($output);

        $incrementId = $input->getArgument(self::INCREMENT_ID);

        return $this->update->getUpdate($incrementId);
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('pagbank:orders:update');
        $this->setDescription('Manually obtain and apply the order update status from PagBank');
        $this->setDefinition(
            [new InputArgument(self::INCREMENT_ID, InputArgument::REQUIRED, 'Order Increment Id')]
        );
        parent::configure();
    }
}
