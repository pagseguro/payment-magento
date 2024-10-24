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

namespace PagBank\PaymentMagento\Model\Api;

use Laminas\Http\ClientFactory;
use Laminas\Http\Request;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\LaminasClient;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use PagBank\PaymentMagento\Api\ThreeDsSessionInterface;
use PagBank\PaymentMagento\Api\Data\ThreeDsSessionDataInterface;
use PagBank\PaymentMagento\Api\Data\ThreeDsSessionDataInterfaceFactory;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;

/**
 * Class 3ds Session - Get Session for Checkout 3ds on PagBank.
 */
class ThreeDsSession implements ThreeDsSessionInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var ClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var ThreeDsSessionDataInterfaceFactory
     */
    protected $sessionData;

    /**
     * InterestManagement constructor.
     *
     * @param StoreManagerInterface                 $storeManager
     * @param ConfigBase                            $configBase
     * @param ClientFactory                         $httpClientFactory
     * @param Json                                  $json
     * @param ThreeDsSessionDataInterfaceFactory    $sessionData
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigBase $configBase,
        ClientFactory $httpClientFactory,
        Json $json,
        ThreeDsSessionDataInterfaceFactory $sessionData
    ) {
        $this->storeManager = $storeManager;
        $this->configBase = $configBase;
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
        $this->sessionData = $sessionData;
    }

    /**
     * @inheritdoc
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getSession(): ThreeDsSessionDataInterface
    {
        /** @var AuthDataInterface $data */
        $data = $this->sessionData->create();
        
        $session = $this->getSessionInPagBank();

        if (isset($session['session'])) {
            $data->setSessionId($session['session']);
            $data->setExpiresAt($session['expires_at']);
        }

        return $data;
    }

    /**
     * Get Session in PagBank
     *
     * @return string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getSessionInPagBank()
    {
        $storeId = $this->storeManager->getStore()->getId();

        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->configBase->getApiSDKUrl($storeId);
        $apiConfigs = $this->configBase->getApiConfigs();
        $headers = $this->configBase->getApiHeaders($storeId);
        $uri = $url.'checkout-sdk/sessions';
        try {
            $client->setUri($uri);
            $client->setHeaders($headers);
            $client->setMethod(Request::METHOD_POST);
            $client->setOptions($apiConfigs);
            $responseBody = $client->send()->getBody();

            $dataResponse = $this->json->unserialize($responseBody);

            return $dataResponse;
        } catch (InvalidArgumentException $exc) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new NoSuchEntityException('Invalid JSON was returned by the gateway');
        }
    }
}
