<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Block\Customer\Vault\Cards;

use Magento\Framework\View\Element\Template;
use Magento\Payment\Model\CcConfig;
use PagBank\PaymentMagento\Gateway\Config\Config;
use PagBank\PaymentMagento\Gateway\Config\ConfigCc;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Asset\Source;

/**
 * Class CreateForm - Block to handle credit card form.
 */
class CreateForm extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigCc
     */
    private $configCc;

    /**
     * @var CcConfig
     */
    private $ccConfig;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var Source
     */
    private $assetSource;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param ConfigCc $configCc
     * @param CcConfig $ccConfig
     * @param Json $serializer
     * @param Source $assetSource
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        ConfigCc $configCc,
        CcConfig $ccConfig,
        Json $serializer,
        Source $assetSource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->configCc = $configCc;
        $this->ccConfig = $ccConfig;
        $this->serializer = $serializer;
        $this->assetSource = $assetSource;
    }

    /**
     * Get credit card expiration months
     *
     * @return array
     */
    public function getCcMonths()
    {
        return $this->ccConfig->getCcMonths();
    }

    /**
     * Get credit card expiration years
     *
     * @return array
     */
    public function getCcYears()
    {
        return $this->ccConfig->getCcYears();
    }

    /**
     * Get CC Available Types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $types = $this->configCc->getCcAvailableTypes($storeId);
        $ccTypesMapper = $this->configCc->getCcTypesMapper($storeId);
        $availableTypes = array_filter(explode(',', $types));

        $result = [];
        foreach ($availableTypes as $type) {
            $code = trim($type);
            if (isset($ccTypesMapper[$code])) {
                $result[$code] = $ccTypesMapper[$code];
            }
        }

        return $result;
    }

    /**
     * Get Public Key
     *
     * @return string
     */
    public function getPublicKey()
    {
        return $this->config->getMerchantGatewayPublicKey($this->_storeManager->getStore()->getId());
    }

    /**
     * Get JS Form Config
     *
     * @return string
     */
    public function getJsFormConfig()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $config = [
            'publicKey' => $this->getPublicKey(),
            'ccTypesMapper' => $this->configCc->getCcTypesMapper($storeId)
        ];
        return $this->serializer->serialize($config);
    }
}