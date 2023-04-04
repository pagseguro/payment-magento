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
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\LaminasClient;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;

/**
 * Class Credential - Get access credential on PagBank.
 */
class Credential
{
    /**
     * @var Config
     */
    protected $resourceConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

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
     * Constructor.
     *
     * @param Config                $resourceConfig
     * @param EncryptorInterface    $encryptor
     * @param StoreManagerInterface $storeManager
     * @param ConfigBase            $configBase
     * @param ClientFactory         $httpClientFactory
     * @param Json                  $json
     */
    public function __construct(
        Config $resourceConfig,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        ConfigBase $configBase,
        ClientFactory $httpClientFactory,
        Json $json
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->configBase = $configBase;
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
    }

    /**
     * Set New Configs.
     *
     * @param array $configs
     * @param bool  $storeIdIsDefault
     * @param int   $webSiteId
     * @param int   $storeId
     *
     * @return void
     */
    public function setNewConfigs(
        $configs,
        bool $storeIdIsDefault,
        int $webSiteId = 0,
        int $storeId = 0,
    ) {
        $scope = ScopeInterface::SCOPE_WEBSITES;

        $environment = $this->configBase->getEnvironmentMode($storeId);

        $basePathConfig = 'payment/pagbank_paymentmagento/%s_%s';

        foreach ($configs as $config => $value) {
            $pathConfig = sprintf($basePathConfig, $config, $environment);

            $value = $this->encryptor->encrypt($value);

            if ($storeIdIsDefault) {
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            }

            $this->resourceConfig->saveConfig(
                $pathConfig,
                $value,
                $scope,
                $webSiteId
            );
        }
    }

    /**
     * Get Authorize.
     *
     * @param int    $storeId
     * @param string $code
     * @param string $codeVerifier
     *
     * @return json
     */
    public function getAuthorize($storeId, $code, $codeVerifier)
    {
        $url = $this->configBase->getApiUrl($storeId);
        $headers = $this->configBase->getPubHeader($storeId);
        $apiConfigs = $this->configBase->getApiConfigs();
        $uri = $url.'oauth2/token';

        $store = $this->storeManager->getStore('admin');
        $storeCode = '/'.$store->getCode().'/';
        $redirectUrl = $store->getUrl('pagbank/system_config/oauth', [
            'website'       => $storeId,
            'code_verifier' => $codeVerifier,
        ]);

        $redirectUrl = str_replace($storeCode, '/', $redirectUrl);

        $data = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUrl,
            'code_verifier' => $codeVerifier,
        ];

        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();
        $client->setUri($uri);
        $client->setHeaders($headers);
        $client->setMethod(Request::METHOD_POST);
        $client->setOptions($apiConfigs);
        $client->setRawBody($this->json->serialize($data));

        $send = $client->send();

        return $send->getBody();
    }

    /**
     * Get Public Key.
     *
     * @param string $oAuth
     * @param int    $storeId
     *
     * @return string
     */
    public function getPublicKey($oAuth, $storeId)
    {
        $url = $this->configBase->getApiUrl($storeId);
        $uri = $url.'public-keys/';
        $apiConfigs = $this->configBase->getApiConfigs();

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$oAuth,
        ];

        $data = ['type' => 'card'];

        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();
        $client->setUri($uri);
        $client->setHeaders($headers);
        $client->setMethod(Request::METHOD_POST);
        $client->setOptions($apiConfigs);
        $client->setRawBody($this->json->serialize($data));

        $send = $client->send();

        return $send->getBody();
    }

    /**
     * Generate New oAuth.
     *
     * @param int $storeId
     *
     * @return string
     */
    public function generateNewoAuth(int $storeId = 0)
    {
        $url = $this->configBase->getApiUrl($storeId);
        $uri = $url.'oauth2/refresh';
        $header = $this->configBase->getPubHeader($storeId);
        $apiConfigs = $this->configBase->getApiConfigs();
        $currentRefresh = $this->configBase->getMerchantGatewayRefreshOauth($storeId);

        $data = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $currentRefresh,
        ];

        $payload = $this->json->serialize($data);

        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();

        $client->setUri($uri);
        $client->setHeaders($header);
        $client->setMethod(Request::METHOD_POST);
        $client->setOptions($apiConfigs);
        $client->setRawBody($payload);
        $send = $client->send();

        return $send->getBody();
    }
}
