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

namespace PagBank\PaymentMagento\Gateway\Command;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Class PaymentCommand - Executes the payment creation command with the order creation call.
 */
class PaymentCommand implements CommandInterface
{
    /**
     * @var CommandPoolInterface
     */
    protected $commandPool;

    /**
     * @var BuilderInterface
     */
    protected $requestBuilder;

    /**
     * @var TransferFactoryInterface
     */
    protected $transferFactory;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var HandlerInterface
     */
    protected $handler;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var ErrorMessageMapperInterface
     */
    protected $errorMessageMapper;

    /**
     * @param CommandPoolInterface             $commandPool
     * @param BuilderInterface                 $requestBuilder
     * @param TransferFactoryInterface         $transferFactory
     * @param ClientInterface                  $client
     * @param LoggerInterface                  $logger
     * @param HandlerInterface                 $handler
     * @param ValidatorInterface               $validator
     * @param ErrorMessageMapperInterface|null $errorMessageMapper
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        BuilderInterface $requestBuilder,
        TransferFactoryInterface $transferFactory,
        ClientInterface $client,
        LoggerInterface $logger,
        HandlerInterface $handler = null,
        ValidatorInterface $validator = null,
        ErrorMessageMapperInterface $errorMessageMapper = null
    ) {
        $this->commandPool = $commandPool;
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->handler = $handler;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->errorMessageMapper = $errorMessageMapper;
    }

    /**
     * Execute.
     *
     * @param array $commandSubject
     *
     * @return void
     */
    public function execute(array $commandSubject)
    {
        $transfer = $this->transferFactory->create(
            $this->requestBuilder->build($commandSubject)
        );

        $response = $this->client->placeRequest($transfer);

        if ($this->validator !== null) {
            $result = $this->validator->validate(
                array_merge($commandSubject, ['response' => $response])
            );
            if (!$result->isValid()) {
                $this->processErrors($result);
            }
        }

        if ($this->handler) {
            $this->handler->handle(
                $commandSubject,
                $response
            );
        }
    }

    /**
     * Process Errors.
     *
     * @param ResultInterface $result
     */
    protected function processErrors(ResultInterface $result)
    {
        $messages = [];
        $errorsSource = array_merge($result->getErrorCodes(), $result->getFailsDescription());
        foreach ($errorsSource as $errorCodeOrMessage) {
            $errorCodeOrMessage = (string) $errorCodeOrMessage;
            if ($this->errorMessageMapper !== null) {
                $mapped = $this->errorMessageMapper->getMessage($errorCodeOrMessage);

                if ($mapped) {
                    $messages[] = (string) $mapped;
                    $errorCodeOrMessage = (string) $mapped;
                }
            }
            $this->logger->critical('Payment Error: '.$errorCodeOrMessage);
        }

        throw new CommandException(
            !empty($messages)
                ? __(implode(PHP_EOL, $messages))
                : __('Transaction has been declined. Please try again later.')
        );
    }
}
