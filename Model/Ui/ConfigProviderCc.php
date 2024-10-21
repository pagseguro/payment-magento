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
use PagBank\PaymentMagento\Gateway\Config\ConfigCc;

/**
 * Class Config Provider Cc - Defines properties of the payment form.
 */
class ConfigProviderCc implements ConfigProviderInterface
{
    /*
     * @const string
     */
    public const CODE = 'pagbank_paymentmagento_cc';

    /*
     * @const string
     */
    public const VAULT_CODE = 'pagbank_paymentmagento_cc_vault';

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var configCc
     */
    protected $configCc;

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
     * @param ConfigCc       $configCc
     * @param Cart           $cart
     * @param CcConfig       $ccConfig
     * @param Source         $assetSource
     * @param Escaper        $escaper
     */
    public function __construct(
        ConfigBase $configBase,
        ConfigCc $configCc,
        Cart $cart,
        CcConfig $ccConfig,
        Source $assetSource,
        Escaper $escaper
    ) {
        $this->configBase = $configBase;
        $this->configCc = $configCc;
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
                ConfigCc::METHOD => [
                    'isActive'             => $this->configCc->isActive($storeId),
                    'title'                => $this->configCc->getTitle($storeId),
                    'useCvv'               => $this->configCc->isCvvEnabled($storeId),
                    'ccTypesMapper'        => $this->configCc->getCcTypesMapper($storeId),
                    'logo'                 => $this->getLogo(),
                    'icons'                => $this->getIcons(),
                    'tax_id_capture'       => $this->configCc->hasTaxIdCapture($storeId),
                    'phone_capture'        => $this->configCc->hasPhoneCapture($storeId),
                    'public_key'           => $this->configBase->getMerchantGatewayPublicKey($storeId),
                    'ccVaultCode'          => self::VAULT_CODE,
                    'threeDs'              => [
                        'enable'        => $this->configCc->hasThreeDsAuth($storeId),
                        'enable_deb'    => $this->configCc->isActiveDebit($storeId),
                        'applicable'    => $this->configCc->isThreeDsApplicable($this->cart, $storeId),
                        'max_try_place' => $this->configCc->getMaxTryPlaceOrder($storeId),
                        'env'           => $this->configCc->getThreeDsEnv($storeId),
                        'reject'        => $this->configCc->hasRejectNotAuth($storeId),
                        'instruction'   => nl2br(
                            $this->escaper->escapeHtml(
                                $this->configCc->getInstructionForThreeDs($storeId),
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

        $ccTypes = $this->configCc->getCcAvailableTypes($storeId);

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
        $asset = $this->ccConfig->createAsset('PagBank_PaymentMagento::images/cc/logo.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => '48px',
                'height' => '32px',
                'title'  => __('Cartão de Crédito - PagBank'),
            ];
        }

        return $logo;
    }
}
