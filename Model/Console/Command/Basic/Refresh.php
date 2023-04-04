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

namespace PagBank\PaymentMagento\Model\Console\Command\Basic;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;
use PagBank\PaymentMagento\Model\Api\Credential;
use PagBank\PaymentMagento\Model\Console\Command\AbstractModel;

/**
 * Class Refresh Token.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Refresh extends AbstractModel
{
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Pool
     */
    protected $cacheFrontendPool;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Credential
     */
    protected $credential;

    /**
     * @param TypeListInterface     $cacheTypeList
     * @param Pool                  $cacheFrontendPool
     * @param Logger                $logger
     * @param State                 $state
     * @param ScopeConfigInterface  $scopeConfig
     * @param ConfigBase            $configBase
     * @param StoreManagerInterface $storeManager
     * @param Json                  $json
     * @param Credential            $credential
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        Logger $logger,
        State $state,
        ScopeConfigInterface $scopeConfig,
        ConfigBase $configBase,
        StoreManagerInterface $storeManager,
        Json $json,
        Credential $credential
    ) {
        parent::__construct(
            $logger
        );
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->state = $state;
        $this->scopeConfig = $scopeConfig;
        $this->configBase = $configBase;
        $this->storeManager = $storeManager;
        $this->json = $json;
        $this->credential = $credential;
    }

    /**
     * Command Preference.
     *
     * @param int|null $storeId
     *
     * @return int
     */
    public function newToken($storeId = null)
    {
        $storeIds = $storeId ?: null;
        $this->writeln('Init Referesh Token');
        $inDefault = $this->getConfigInDefault();

        if ($inDefault['has'] || $storeId === 0) {
            $this->writeln(__('Refresh Token for Default Store.'));
            $this->refreshToken(true, 0, 0);
        }

        if (!$storeIds) {
            $allStores = $this->storeManager->getStores();

            foreach ($allStores as $stores) {
                $storeId = (int) $stores->getId();
                $this->storeManager->setCurrentStore($stores);
                $websiteId = (int) $stores->getWebsiteId();
                $storeIdIsDefault = false;
                $inWebsite = $this->getConfigInWebsite($websiteId);

                if ($inWebsite['has'] && ($inWebsite['value'] !== $inDefault['value'])) {
                    $this->writeln(__('Refresh Token for Web Site Id %1.', $websiteId));
                    $this->refreshToken($storeIdIsDefault, $storeId, $websiteId);
                }
            }
        }

        $this->writeln(__('Finished'));

        return 1;
    }

    /**
     * Get Config In Default.
     *
     * @return array
     */
    public function getConfigInDefault()
    {
        $environment = $this->configBase->getEnvironmentMode(0);

        $basePathConfig = 'payment/pagbank_paymentmagento/access_token_%s';

        $pathConfig = sprintf($basePathConfig, $environment);

        $hasConfig = $this->scopeConfig->isSetFlag(
            $pathConfig
        );

        $value = $this->scopeConfig->getValue(
            $pathConfig
        );

        return [
            'has'   => (bool) $hasConfig,
            'value' => ($hasConfig) ? $value : null,
        ];
    }

    /**
     * Get Config In Website.
     *
     * @param int|null $websiteId
     *
     * @return array
     */
    public function getConfigInWebsite($websiteId)
    {
        $environment = $this->configBase->getEnvironmentMode($websiteId);

        $scope = ScopeInterface::SCOPE_WEBSITES;

        $basePathConfig = 'payment/pagbank_paymentmagento/access_token_%s';

        $pathConfig = sprintf($basePathConfig, $environment);

        $hasConfig = $this->scopeConfig->isSetFlag(
            $pathConfig,
            $scope,
            $websiteId
        );

        $value = $this->scopeConfig->getValue(
            $pathConfig,
            $scope,
            $websiteId
        );

        return [
            'has'   => (bool) $hasConfig,
            'value' => $value,
        ];
    }

    /**
     * Refresh Token.
     *
     * @param bool $storeIdIsDefault
     * @param int  $storeId
     * @param int  $webSiteId
     *
     * @return void
     */
    protected function refreshToken(bool $storeIdIsDefault, int $storeId = 0, int $webSiteId = 0)
    {
        $newToken = $this->credential->generateNewoAuth($storeId);
        $newToken = $this->json->unserialize($newToken);

        if (isset($newToken['access_token'])) {
            $configs = [
                'access_token'  => $newToken['access_token'],
                'refresh_token' => $newToken['refresh_token'],
            ];

            $this->credential->setNewConfigs(
                $configs,
                $storeIdIsDefault,
                $webSiteId,
                $storeId
            );

            $this->cacheTypeList->cleanType('config');

            $publicKey = $this->credential->getPublicKey($newToken['access_token'], $storeId);
            $publicKey = $this->json->unserialize($publicKey);

            $configPub = [
                'public_key' => $publicKey['public_key'],
            ];

            $this->credential->setNewConfigs(
                $configPub,
                $storeIdIsDefault,
                $webSiteId,
                $storeId
            );

            $this->cacheTypeList->cleanType('config');

            $message = __('Refresh token successful');
            $this->writeln('<info>'.$message.'</info>');
        }

        if (isset($newToken['error_messages'])) {
            foreach ($newToken['error_messages'] as $errors) {
                $message = __('token update returns errors code: %1', $errors['code']);
                $this->writeln('<error>'.$message.'</error>');
            }
        }
    }
}
