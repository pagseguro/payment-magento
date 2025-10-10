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

namespace PagBank\PaymentMagento\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;

/**
 * Class Logout - Clear PagBank Config.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Logout extends \Magento\Backend\App\Action
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
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ConfigInterface
     */
    protected $configInterface;

    /**
     * @var Config
     */
    protected $resourceConfig;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context               $context
     * @param TypeListInterface     $cacheTypeList
     * @param Pool                  $cacheFrontendPool
     * @param JsonFactory           $resultJsonFactory
     * @param ConfigInterface       $configInterface
     * @param Config                $resourceConfig
     * @param ConfigBase            $configBase
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        JsonFactory $resultJsonFactory,
        ConfigInterface $configInterface,
        Config $resourceConfig,
        ConfigBase $configBase,
        StoreManagerInterface $storeManager
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configInterface = $configInterface;
        $this->resourceConfig = $resourceConfig;
        $this->configBase = $configBase;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * ACL - Check is Allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('PagBank_PaymentMagento::logout');
    }

    /**
     * Excecute.
     *
     * @return json
     */
    public function execute()
    {
        $configDefault = false;

        $params = $this->getRequest()->getParams();

        $webSiteId = (int) $params['website'];

        if (!$webSiteId) {
            $configDefault = true;
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $this->setClearOauth($configDefault, $webSiteId);

        $this->cacheTypeList->cleanType('config');
        $this->messageManager->addSuccess(__('You are diconnected to PagBank.'));

        $resultRedirect->setUrl($this->getUrlConfig());

        return $resultRedirect;
    }

    /**
     * Get store from request.
     *
     * @return Store
     */
    public function getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('website');

        return $this->storeManager->getStore($storeId);
    }

    /**
     * Get Url.
     *
     * @return string
     */
    private function getUrlConfig()
    {
        return $this->getUrl(
            'adminhtml/system_config/edit/section/payment/',
            [
                'website' => $this->getStore()->getId(),
            ]
        );
    }

    /**
     * Set Clear oAuth.
     *
     * @param bool $configDefault
     * @param int  $webSiteId
     *
     * @return void
     */
    public function setClearOauth(
        $configDefault,
        $webSiteId
    ) {
        $environment = $this->configBase->getEnvironmentMode($webSiteId);

        $scope = ScopeInterface::SCOPE_WEBSITES;

        if ($configDefault) {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }

        $this->resourceConfig->deleteConfig(
            'payment/pagbank_paymentmagento/access_token_'.$environment,
            $scope,
            $webSiteId
        );

        $this->resourceConfig->deleteConfig(
            'payment/pagbank_paymentmagento/refresh_token_'.$environment,
            $scope,
            $webSiteId
        );

        $this->resourceConfig->deleteConfig(
            'payment/pagbank_paymentmagento/public_key_'.$environment,
            $scope,
            $webSiteId
        );
    }
}
