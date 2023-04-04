<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;
use PagBank\PaymentMagento\Gateway\Request\MetadataRequest;

/**
 * Class TransferFactory - Factors data transfer.
 */
class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    protected $transferBuilder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param TransferBuilder $transferBuilder
     * @param Config          $config
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        Config $config
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
    }

    /**
     * Builds gateway transfer object.
     *
     * @param array $request
     *
     * @return TransferInterface
     */
    public function create(array $request)
    {
        $storeId = $request[MetadataRequest::METADATA][0][MetadataRequest::STORE_ID];

        $apiConfigs = $this->config->getApiConfigs();
        $headers = $this->config->getApiHeaders($storeId);
        $uri = $this->config->getApiUrl($storeId);

        return $this->transferBuilder
            ->setUri($uri)
            ->setBody($request)
            ->setClientConfig($apiConfigs)
            ->setHeaders($headers)
            ->build();
    }
}
