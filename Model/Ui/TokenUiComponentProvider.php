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

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;

/**
 * Class Token Ui Component Provider - Defines properties of the payment form.
 */
class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    protected $componentFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * TokenUiComponentProvider constructor.
     *
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param Json                             $json
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        Json $json
    ) {
        $this->componentFactory = $componentFactory;
        $this->json = $json;
    }

    /**
     * Get UI component for token.
     *
     * @param PaymentTokenInterface $paymentToken
     *
     * @return TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $jsonDetails = $this->json->unserialize($paymentToken->getTokenDetails());
        $component = $this->componentFactory->create(
            [
                'config' => [
                    // phpcs:ignore Generic.Files.LineLength
                    'code'                                                   => ConfigProviderBase::METHOD_CODE_CC_VAULT,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS     => $jsonDetails,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                ],
                'name' => 'PagBank_PaymentMagento/js/view/payment/method-renderer/vault',
            ]
        );

        return $component;
    }
}
