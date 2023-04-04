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
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PagBank\PaymentMagento\Model\Api\Credential;

/**
 * Class oAuth - Create Authorization.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Oauth extends \Magento\Backend\App\Action
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
     * @param Context               $context
     * @param TypeListInterface     $cacheTypeList
     * @param Pool                  $cacheFrontendPool
     * @param JsonFactory           $resultJsonFactory
     * @param StoreManagerInterface $storeManager
     * @param Json                  $json
     * @param Credential            $credential
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        JsonFactory $resultJsonFactory,
        StoreManagerInterface $storeManager,
        Json $json,
        Credential $credential
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->json = $json;
        $this->credential = $credential;
        parent::__construct($context);
    }

    /**
     * ACL - Check is Allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('PagBank_PaymentMagento::oauth');
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
        $oAuth = null;

        if (isset($params['code'])) {
            $oAuthResponse = $this->credential->getAuthorize(
                $webSiteId,
                $params['code'],
                $params['code_verifier']
            );

            if ($oAuthResponse) {
                $oAuthResponse = $this->json->unserialize($oAuthResponse);
                if (isset($oAuthResponse['access_token'])) {
                    $oAuth = $oAuthResponse['access_token'];
                    $configs = [
                        'access_token'  => $oAuth,
                        'refresh_token' => $oAuthResponse['refresh_token'],
                    ];

                    $this->credential->setNewConfigs($configs, $configDefault, $webSiteId);
                }
                if ($oAuth) {
                    $publicKey = $this->credential->getPublicKey($oAuth, $webSiteId);
                    $publicKey = $this->json->unserialize($publicKey);
                    $this->credential->setNewConfigs(
                        ['public_key' => $publicKey['public_key']],
                        $configDefault,
                        $webSiteId
                    );
                    $this->cacheTypeList->cleanType('config');
                    $this->messageManager->addSuccess(__('You are connected to PagBank. =)'));
                    $resultRedirect->setUrl($this->getUrlConfig());

                    return $resultRedirect;
                }
            }
        }

        $this->messageManager->addError(__('Unable to get the code, try again. =('));
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
        $webSiteId = (int) $this->getRequest()->getParam('website');

        return $this->storeManager->getStore($webSiteId);
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
}
