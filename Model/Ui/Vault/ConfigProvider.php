<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Model\Ui\Vault;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Quote\Api\Data\CartInterface;
use PagBank\PaymentMagento\Gateway\Config\ConfigCc;
use PagBank\PaymentMagento\Gateway\Config\ConfigCcVault;
use PagBank\PaymentMagento\Model\Ui\ConfigProviderCc;

/**
 * Class Config Provider - Defines properties of the payment form.
 */
class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'pagbank_paymentmagento_cc_vault';

    /**
     * @var array
     */
    protected $icons = [];

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @var ConfigProviderCc
     */
    protected $configProviderCc;

    /**
     * @var ConfigCcVault
     */
    protected $configCcVault;

    /**
     * ConfigProvider constructor.
     *
     * @param CartInterface    $cart
     * @param ConfigCc         $configCc
     * @param ConfigProviderCc $configProviderCc
     * @param ConfigCcVault    $configCcVault
     */
    public function __construct(
        CartInterface $cart,
        ConfigCc $configCc,
        ConfigProviderCc $configProviderCc,
        ConfigCcVault $configCcVault
    ) {
        $this->cart = $cart;
        $this->configCc = $configCc;
        $this->configProviderCc = $configProviderCc;
        $this->configCcVault = $configCcVault;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @throws InputException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->cart->getStoreId();

        return [
            'payment' => [
                self::CODE => [
                    'icons'             => $this->configProviderCc->getIcons(),
                    'tax_id_capture'    => $this->configCc->hasTaxIdCapture($storeId),
                    'phone_capture'     => $this->configCc->hasPhoneCapture($storeId),
                ],
            ],
        ];
    }
}
