<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;

/**
 * Class Config Provider Base - Defines properties of the payment form.
 */
class ConfigProviderBase implements ConfigProviderInterface
{
    /*
     * @const string
     */
    public const CODE = 'pagbank_paymentmagento';

    /*
     * @var METHOD CODE CC
     */
    public const METHOD_CODE_CC = 'pagbank_paymentmagento_cc';

    /*
     * @var METHOD CODE CC VAULT
     */
    public const METHOD_CODE_CC_VAULT = 'pagbank_paymentmagento_cc_vault';

    /*
     * @var METHOD CODE BOLETO
     */
    public const METHOD_CODE_BOLETO = 'pagbank_paymentmagento_boleto';

    /*
     * @var METHOD CODE Deep Link
     */
    public const METHOD_CODE_DEEP_LINK = 'pagbank_paymentmagento_deep_link';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @param Config        $config
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     */
    public function __construct(
        Config $config,
        CartInterface $cart,
        CcConfig $ccConfig
    ) {
        $this->config = $config;
        $this->cart = $cart;
        $this->ccConfig = $ccConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                Config::METHOD => [
                    'isActive'    => false,
                    'tax_id_from' => $this->config->getAddtionalValue('get_tax_id_from'),
                ],
            ],
        ];
    }
}
