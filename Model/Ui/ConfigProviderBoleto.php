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
use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use PagBank\PaymentMagento\Gateway\Config\ConfigBoleto;

/**
 * Class Config Provider Boleto - Defines properties of the payment form.
 */
class ConfigProviderBoleto implements ConfigProviderInterface
{
    /*
     * @const string
     */
    public const CODE = 'pagbank_paymentmagento_boleto';

    /**
     * @var ConfigBoleto
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
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * @param ConfigBoleto  $config
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     * @param Escaper       $escaper
     * @param Source        $assetSource
     */
    public function __construct(
        ConfigBoleto $config,
        CartInterface $cart,
        CcConfig $ccConfig,
        Escaper $escaper,
        Source $assetSource
    ) {
        $this->config = $config;
        $this->cart = $cart;
        $this->escaper = $escaper;
        $this->ccConfig = $ccConfig;
        $this->assetSource = $assetSource;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->cart->getStoreId();

        return [
            'payment' => [
                ConfigBoleto::METHOD => [
                    'isActive'             => $this->config->isActive($storeId),
                    'title'                => $this->config->getTitle($storeId),
                    'name_capture'         => $this->config->hasNameCapture($storeId),
                    'tax_id_capture'       => $this->config->hasTaxIdCapture($storeId),
                    'expiration'           => $this->escaper->escapeHtml(
                        $this->config->getExpirationFormat($storeId)
                    ),
                    'instruction_checkout' => nl2br(
                        $this->escaper->escapeHtml(
                            $this->config->getInstructionCheckout($storeId),
                            ['b']
                        )
                    ),
                    'logo'                 => $this->getLogo(),
                ],
            ],
        ];
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getLogo()
    {
        $logo = [];
        $asset = $this->ccConfig->createAsset('PagBank_PaymentMagento::images/boleto/logo.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => '48px',
                'height' => '32px',
                'title'  => __('Boleto Bancário - PagBank'),
            ];
        }

        return $logo;
    }
}
