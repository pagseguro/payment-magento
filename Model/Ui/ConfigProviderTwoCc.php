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
use Magento\Checkout\Model\Cart;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;
use PagBank\PaymentMagento\Gateway\Config\ConfigTwoCc;

/**
 * Class Config Provider Cc - Defines properties of the payment form.
 */
class ConfigProviderTwoCc implements ConfigProviderInterface
{
    /*
     * @const string
     */
    public const CODE = 'pagbank_paymentmagento_two_cc';

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var ConfigTwoCc
     */
    protected $configTwoCc;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var array
     */
    protected $icons = [];

    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param ConfigBase     $configBase
     * @param configTwoCc    $configTwoCc
     * @param Cart           $cart
     * @param CcConfig       $ccConfig
     * @param Source         $assetSource
     * @param Escaper        $escaper
     */
    public function __construct(
        ConfigBase $configBase,
        ConfigTwoCc $configTwoCc,
        Cart $cart,
        CcConfig $ccConfig,
        Source $assetSource,
        Escaper $escaper
    ) {
        $this->configBase = $configBase;
        $this->configTwoCc = $configTwoCc;
        $this->cart = $cart;
        $this->ccConfig = $ccConfig;
        $this->assetSource = $assetSource;
        $this->escaper = $escaper;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $quote = $this->cart->getQuote(); // Obter a instância de Quote
        $storeId = $quote->getStoreId(); // Obter o Store ID a partir do Quote
        // $cartId = $quote->getId(); // Obter o Cart ID a partir do Quote

        return [
            'payment' => [
                configTwoCc::METHOD => [
                    'isActive'             => $this->configTwoCc->isActive($storeId),
                    'title'                => $this->configTwoCc->getTitle($storeId),
                    'useCvv'               => $this->configTwoCc->isCvvEnabled($storeId),
                    'ccTypesMapper'        => $this->configTwoCc->getCcTypesMapper($storeId),
                    'logo'                 => $this->getLogo(),
                    'icons'                => $this->getIcons(),
                    'tax_id_capture'       => $this->configTwoCc->hasTaxIdCapture($storeId),
                    'phone_capture'        => $this->configTwoCc->hasPhoneCapture($storeId),
                    'public_key'           => $this->configBase->getMerchantGatewayPublicKey($storeId),
                    'threeDs'              => [
                        'enable'        => $this->configTwoCc->hasThreeDsAuth($storeId),
                        'enable_deb'    => $this->configTwoCc->isActiveDebit($storeId),
                        'applicable'    => $this->configTwoCc->isThreeDsApplicable($this->cart, $storeId),
                        'max_try_place' => $this->configTwoCc->getMaxTryPlaceOrder($storeId),
                        'env'           => $this->configTwoCc->getThreeDsEnv($storeId),
                        'reject'        => $this->configTwoCc->hasRejectNotAuth($storeId),
                        'instruction'   => nl2br(
                            $this->escaper->escapeHtml(
                                $this->configTwoCc->getInstructionForThreeDs($storeId),
                                ['b']
                            )
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getIcons()
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }

        $storeId = $this->cart->getStoreId();

        $ccTypes = $this->configTwoCc->getCcAvailableTypes($storeId);

        $types = explode(',', $ccTypes);

        foreach ($types as $code => $label) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig->createAsset('PagBank_PaymentMagento::images/cc/'.strtolower($label).'.svg');
                $placeholder = $this->assetSource->findSource($asset);

                if ($placeholder) {
                    $this->icons[$label] = [
                        'url'    => $asset->getUrl(),
                        'width'  => '60px',
                        'height' => '40px',
                        'title'  => __($label),
                    ];
                }
            }
        }

        return $this->icons;
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getLogo()
    {
        $logo = [];
        $asset = $this->ccConfig->createAsset('PagBank_PaymentMagento::images/two-cc/logo.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => '48px',
                'height' => '32px',
                'title'  => __('Pague com 2 cartões - PagBank'),
            ];
        }

        return $logo;
    }
}
