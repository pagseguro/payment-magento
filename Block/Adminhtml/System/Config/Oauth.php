<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Math\Random;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;

/**
 * Class Oauth - Defines oAuth session actions.
 */
class Oauth extends Field
{
    /**
     * Template oAuth.
     */
    public const TEMPLATE = 'PagBank_PaymentMagento::system/config/oauth.phtml';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Random
     */
    protected $mathRandom;

    /**
     * @var string
     */
    protected $codeVerifier;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Config                $config
     * @param Context               $context
     * @param Random                $mathRandom
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Config $config,
        Context $context,
        Random $mathRandom
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->mathRandom = $mathRandom;
        parent::__construct($context);
        $this->setTemplate(self::TEMPLATE);
    }

    /**
     * Get store from request.
     *
     * @param bool $useWebsite
     *
     * @return Store
     */
    public function getStore($useWebsite)
    {
        $storeId = (int) $this->getRequest()->getParam('store');

        if (!$storeId && $useWebsite) {
            $websiteId = (int) $this->getRequest()->getParam('website');

            return $this->storeManager->getWebsite($websiteId)->getDefaultStore();
        }

        return $this->storeManager->getStore($storeId);
    }

    /**
     * Render.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Elment Html.
     *
     * @param AbstractElement $element
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Ajax Url.
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl(
            'pagbank/system_config/logout',
            [
                'website' => $this->getStore(true)->getId(),
            ]
        );
    }

    /**
     * Url Authorize.
     *
     * @return string
     */
    public function getUrlAuthorize()
    {
        $storeUri = $this->getUrl(
            'pagbank/system_config/oauth',
            [
                'website'       => $this->getStore(true)->getId(),
                'code_verifier' => $this->codeVerifier,
            ]
        );

        return $storeUri;
    }

    /**
     * Button Html.
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $addClass = ($this->getTypeJs() === 'clear') ? 'secondary' : 'primary';
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id'    => 'pagbank-oauth',
                'label' => __($this->getInfoTextBtn()),
                'class' => $addClass,
            ]
        );

        return $button->toHtml();
    }

    /**
     * Info Text Button.
     *
     * @return string
     */
    public function getInfoTextBtn()
    {
        $storeId = $this->getStore(true)->getId();
        $websiteId = (int) $this->getRequest()->getParam('website');
        $storeName = $this->getStore(false)->getName();

        if ($websiteId) {
            $storeName = $this->getStore(true)->getWebsite()->getName();
        }

        $store = (int) $this->getRequest()->getParam('store');

        if (!$store && !$websiteId) {
            $storeName = __('Default Config');
        }

        $environment = $this->config->getEnvironmentMode($storeId);
        $oauth = $this->config->getMerchantGatewayOauth($storeId);
        $label = __('Production');

        if ($environment === Config::ENVIRONMENT_SANDBOX) {
            $label = __('Environment for tests');
        }

        $text = __('Authorize for the store %1 in %2', $storeName, $label);

        if ($oauth) {
            $text = __('Disallow for the store %1 in %2', $storeName, $label);
        }

        return $text;
    }

    /**
     * Type Js.
     *
     * @return string
     */
    public function getTypeJs()
    {
        $storeId = $this->getStore(true)->getId();

        if ($this->config->getMerchantGatewayOauth($storeId)) {
            return 'clear';
        }

        return 'getautorization';
    }

    /**
     * Url to connect.
     *
     * @return string
     */
    public function getUrlToConnect()
    {
        $storeId = $this->getStore(false)->getId();
        $urlConnect = Config::ENDPOINT_CONNECT_PRODUCTION;
        $appId = Config::APP_ID_PRODUCTION;
        $scope = Config::OAUTH_SCOPE;
        $state = Config::OAUTH_STATE;
        $responseType = Config::OAUTH_CODE;

        $codeChallenge = $this->getCodeChallenge();
        $redirectUri = $this->getUrlAuthorize();
        $codeChallengeMethod = Config::OAUTH_CODE_CHALLENGER_METHOD;

        if ($this->config->getEnvironmentMode($storeId) === Config::ENVIRONMENT_SANDBOX) {
            $urlConnect = Config::ENDPOINT_CONNECT_SANDBOX;
            $appId = Config::APP_ID_SANDBOX;
        }

        $params = [
            'response_type'         => $responseType,
            'client_id'             => $appId,
            'scope'                 => $scope,
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'redirect_uri'          => $redirectUri,
        ];

        $link = $urlConnect.'?'.http_build_query($params, '&');

        return urldecode($link);
    }

    /**
     * Get Code Challenger.
     *
     * @return string
     */
    public function getCodeChallenge()
    {
        $params = $this->getRequest()->getParams();

        $this->codeVerifier = sha1($this->mathRandom->getRandomString(100));

        if (isset($params['key'])) {
            $this->codeVerifier = $params['key'];
        }

        $codeChallenge = $this->getBase64UrlEncode(
            pack('H*', hash('sha256', $this->codeVerifier))
        );

        return $codeChallenge;
    }

    /**
     * Get Base64 Url Encode.
     *
     * @param string $code
     *
     * @return string
     */
    public function getBase64UrlEncode($code)
    {
        return rtrim(strtr(base64_encode($code), '+/', '-_'), '=');
    }
}
