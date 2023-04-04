<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Response;

use InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;
use PagBank\PaymentMagento\Gateway\Config\ConfigCc;

/**
 * Vault Details Handler - Reply Flow for Vault data.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VaultDetailsHandler implements HandlerInterface
{
    /**
     * Response Pay Charges - Block name.
     */
    public const RESPONSE_CHARGES = 'charges';

    /**
     * Response Pay Payment Method - Block name.
     */
    public const RESPONSE_PAYMENT_METHOD = 'payment_method';

    /**
     * Response Pay Payment Method Card - Block name.
     */
    public const RESPONSE_PAYMENT_METHOD_CARD = 'card';

    /**
     * Response Pay Payment Method Card Id - Block name.
     */
    public const RESPONSE_PM_CARD_ID = 'id';

    /**
     * Response Pay Payment Method Card Brand - Block name.
     */
    public const RESPONSE_PM_CARD_BRAND = 'brand';

    /**
     * Response Pay Payment Method Card First Digits - Block name.
     */
    public const RESPONSE_PM_CARD_FIRST_DIGITS = 'first_digits';

    /**
     * Response Pay Payment Method Card Last Digits - Block name.
     */
    public const RESPONSE_PM_CARD_LAST_DIGITS = 'last_digits';

    /**
     * Response Pay Payment Method Card Exp Month - Block name.
     */
    public const RESPONSE_PM_CARD_EXP_MONTH = 'exp_month';

    /**
     * Response Pay Payment Method Card Exp Year - Block name.
     */
    public const RESPONSE_PM_CARD_EXP_YEAR = 'exp_year';

    /**
     * Response Pay Payment Method Card Holder - Block name.
     */
    public const RESPONSE_PM_CARD_HOLDER = 'holder';

    /**
     * @var PaymentTokenInterfaceFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $payExtensionFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param Json                                  $json
     * @param ObjectManagerInterface                $objectManager
     * @param OrderPaymentExtensionInterfaceFactory $payExtensionFactory
     * @param ConfigCc                              $configCc
     * @param PaymentTokenFactoryInterface          $paymentTokenFactory
     */
    public function __construct(
        Json $json,
        ObjectManagerInterface $objectManager,
        OrderPaymentExtensionInterfaceFactory $payExtensionFactory,
        ConfigCc $configCc,
        PaymentTokenFactoryInterface $paymentTokenFactory = null
    ) {
        if ($paymentTokenFactory === null) {
            $paymentTokenFactory = $objectManager->get(PaymentTokenFactoryInterface::class);
        }

        $this->objectManager = $objectManager;
        $this->payExtensionFactory = $payExtensionFactory;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->json = $json;
        $this->configCc = $configCc;
    }

    /**
     * Handle.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $paymentToken = $this->getVaultPaymentToken($response);

        if (null !== $paymentToken) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * Get vault payment token entity.
     *
     * @param array $response
     *
     * @return PaymentTokenInterface|null
     */
    protected function getVaultPaymentToken($response)
    {
        $charges = $response[self::RESPONSE_CHARGES][0];

        $paymentMethod = $charges[self::RESPONSE_PAYMENT_METHOD];

        $cardData = $paymentMethod[self::RESPONSE_PAYMENT_METHOD_CARD];

        if (!isset($cardData[self::RESPONSE_PM_CARD_ID])) {
            return null;
        }

        $cardId = $cardData[self::RESPONSE_PM_CARD_ID];
        $ccType = $cardData[self::RESPONSE_PM_CARD_BRAND];
        $ccBin = $cardData[self::RESPONSE_PM_CARD_FIRST_DIGITS];
        $ccLast4 = $cardData[self::RESPONSE_PM_CARD_LAST_DIGITS];
        $ccExpMonth = $cardData[self::RESPONSE_PM_CARD_EXP_MONTH];
        $ccExpYear = $cardData[self::RESPONSE_PM_CARD_EXP_YEAR];

        $ccType = $this->getCreditCardType($ccType);
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($cardId);
        $paymentToken->setExpiresAt(strtotime('+1 year'));
        $paymentToken->setType(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);

        $details = [
            'cc_bin'       => $ccBin,
            'cc_last4'     => $ccLast4,
            'cc_exp_year'  => $ccExpYear,
            'cc_exp_month' => $ccExpMonth,
            'cc_type'      => $ccType,
        ];

        $paymentToken->setTokenDetails($this->json->serialize($details));

        return $paymentToken;
    }

    /**
     * Get payment extension attributes.
     *
     * @param InfoInterface $payment
     *
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->payExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }

    /**
     * Get type of credit card mapped from PagBank.
     *
     * @param string $type
     *
     * @return string
     */
    public function getCreditCardType(string $type): ?string
    {
        $type = strtoupper($type);
        $mapper = $this->configCc->getCcTypesMapper();

        return $mapper[$type] ?: $type;
    }
}
