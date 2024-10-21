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
use Magento\Framework\Phrase;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use PagBank\PaymentMagento\Gateway\Config\ConfigDeepLink;

/**
 * Class Config Provider DeepLink - Defines properties of the payment form..
 */
class ConfigProviderDeepLink implements ConfigProviderInterface
{
    /*
     * @const string
     */
    public const CODE = 'pagbank_paymentmagento_deep_link';

    /**
     * @var ConfigDeepLink
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
     * @param ConfigDeepLink    $config
     * @param CartInterface     $cart
     * @param CcConfig          $ccConfig
     * @param Escaper           $escaper
     * @param Source            $assetSource
     */
    public function __construct(
        ConfigDeepLink $config,
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
                self::CODE => [
                    'isActive'             => $this->config->isActive($storeId),
                    'title'                => $this->config->getTitle($storeId),
                    'name_capture'         => $this->config->hasNameCapture($storeId),
                    'tax_id_capture'       => $this->config->hasTaxIdCapture($storeId),
                    'phone_capture'        => $this->config->hasPhoneCapture($storeId),
                    'instruction_checkout' => nl2br($this->getInstruction($storeId)),
                    'logo'                 => $this->getLogo(),
                ],
            ],
        ];
    }

    /**
     * Get Instruction.
     *
     * @param int|null $storeId
     *
     * @return Phrase
     */
    public function getInstruction($storeId)
    {
        $text = $this->config->getInstructionCheckout($storeId);

        return $this->escaper->escapeHtml(
            $text,
            ['b', 'a', 'h3', 'target']
        );
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getLogo()
    {
        $logo = [];
        $asset = $this->ccConfig->createAsset('PagBank_PaymentMagento::images/deep-link/logo.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => '48px',
                'height' => '32px',
                'title'  => __('Pay in PagBank'),
            ];
        }

        return $logo;
    }
}
