<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace PagBank\PaymentMagento\Plugin;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\ConfigFactoryInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Block\Form;
use Magento\Vault\Model\VaultPaymentInterface;

/**
 * Class Vault.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD)
 */
class VaultAddtionalCommand implements VaultPaymentInterface
{
    /**
     * @var string
     */
    protected static $activeKey = 'active';

    /**
     * @var string
     */
    protected static $titleKey = 'title';

    /**
     * @var ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var MethodInterface
     */
    protected $vaultProvider;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var ValueHandlerPoolInterface
     */
    protected $valueHandlerPool;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var Command\CommandManagerPoolInterface
     */
    protected $commandManagerPool;

    /**
     * @var PaymentTokenManagementInterface
     */
    protected $tokenManagement;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $payExtensionFactory;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * Constructor.
     *
     * @param ConfigInterface                       $config
     * @param ConfigFactoryInterface                $configFactory
     * @param ObjectManagerInterface                $objectManager
     * @param MethodInterface                       $vaultProvider
     * @param ManagerInterface                      $eventManager
     * @param ValueHandlerPoolInterface             $valueHandlerPool
     * @param Command\CommandManagerPoolInterface   $commandManagerPool
     * @param PaymentTokenManagementInterface       $tokenManagement
     * @param OrderPaymentExtensionInterfaceFactory $payExtensionFactory
     * @param Json                                  $jsonSerializer
     * @param string                                $code
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ConfigInterface $config,
        ConfigFactoryInterface $configFactory,
        ObjectManagerInterface $objectManager,
        MethodInterface $vaultProvider,
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        Command\CommandManagerPoolInterface $commandManagerPool,
        PaymentTokenManagementInterface $tokenManagement,
        OrderPaymentExtensionInterfaceFactory $payExtensionFactory,
        Json $jsonSerializer,
        $code
    ) {
        $this->config = $config;
        $this->configFactory = $configFactory;
        $this->objectManager = $objectManager;
        $this->valueHandlerPool = $valueHandlerPool;
        $this->vaultProvider = $vaultProvider;
        $this->eventManager = $eventManager;
        $this->commandManagerPool = $commandManagerPool;
        $this->tokenManagement = $tokenManagement;
        $this->payExtensionFactory = $payExtensionFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->code = $code;
    }

    /**
     * Unifies configured value handling logic.
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return mixed
     */
    protected function getConfiguredValue($field, $storeId = null)
    {
        $handler = $this->valueHandlerPool->get($field);
        $subject = ['field' => $field];

        return $handler->handle($subject, $storeId ?: $this->getStore());
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function getFormBlockType()
    {
        return Form::class;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function getTitle()
    {
        return $this->getConfiguredValue(self::$titleKey);
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function setStore($storeId)
    {
        $this->storeId = (int) $storeId;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function getStore()
    {
        return $this->storeId;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canOrder()
    {
        return false;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canAuthorize()
    {
        return $this->vaultProvider->canAuthorize()
        && $this->vaultProvider->getConfigData(static::CAN_AUTHORIZE);
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canCapture()
    {
        return $this->vaultProvider->canCapture()
        && $this->vaultProvider->getConfigData(static::CAN_CAPTURE);
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canCapturePartial()
    {
        return false;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canCaptureOnce()
    {
        return $this->vaultProvider->canCaptureOnce();
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canRefund()
    {
        return false;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canRefundPartialPerInvoice()
    {
        return false;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canVoid()
    {
        return false;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canUseInternal()
    {
        $isInternalAllowed = $this->getConfiguredValue('can_use_internal');
        // if config has't been specified for Vault, need to check payment provider option
        if ($isInternalAllowed === null) {
            return $this->vaultProvider->canUseInternal();
        }

        return (bool) $isInternalAllowed;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canUseCheckout()
    {
        return $this->vaultProvider->canUseCheckout();
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canEdit()
    {
        return $this->vaultProvider->canEdit();
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canFetchTransactionInfo()
    {
        return $this->vaultProvider->canFetchTransactionInfo();
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     *
     * @return void
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        $commandExecutor = $this->commandManagerPool->get(
            $this->vaultProvider->getCode()
        );

        $commandExecutor->executeByCode(
            'vault_fetch_transaction_information',
            $payment,
            ['payment' => $payment, 'transactionId' => $transactionId]
        );

        $payment->setMethod($this->vaultProvider->getCode());
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function isGateway()
    {
        return $this->vaultProvider->isGateway();
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function isOffline()
    {
        return $this->vaultProvider->isOffline();
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function isInitializeNeeded()
    {
        return $this->vaultProvider->isInitializeNeeded();
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canUseForCountry($country)
    {
        return $this->vaultProvider->canUseForCountry($country);
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canUseForCurrency($currencyCode)
    {
        return $this->vaultProvider->canUseForCurrency($currencyCode);
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function getInfoBlockType()
    {
        return $this->vaultProvider->getInfoBlockType();
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function getInfoInstance()
    {
        return $this->vaultProvider->getInfoInstance();
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function setInfoInstance(InfoInterface $info)
    {
        $this->vaultProvider->setInfoInstance($info);
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function validate()
    {
        return $this->vaultProvider->validate();
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        throw new LocalizedException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$payment instanceof OrderPaymentInterface) {
            throw new LocalizedException('Not implemented');
        }
        /** @var $payment OrderPaymentInterface */
        $this->attachTokenExtensionAttribute($payment);
        $this->attachCreditCardInfo($payment);

        $commandExecutor = $this->commandManagerPool->get(
            $this->vaultProvider->getCode()
        );

        $commandExecutor->executeByCode(
            VaultPaymentInterface::VAULT_AUTHORIZE_COMMAND,
            $payment,
            [
                'amount' => $amount,
            ]
        );

        $payment->setMethod($this->vaultProvider->getCode());

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     *
     * @return void
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$payment instanceof OrderPaymentInterface) {
            throw new LocalizedException('Not implemented');
        }

        if ($payment->getAuthorizationTransaction()) {
            throw new LocalizedException('Capture can not be performed through vault');
        }

        $this->attachTokenExtensionAttribute($payment);

        $commandExecutor = $this->commandManagerPool->get(
            $this->vaultProvider->getCode()
        );

        $commandExecutor->executeByCode(
            VaultPaymentInterface::VAULT_SALE_COMMAND,
            $payment,
            [
                'amount' => $amount,
            ]
        );

        $payment->setMethod($this->vaultProvider->getCode());
    }

    /**
     * Attaches token extension attribute.
     *
     * @param OrderPaymentInterface $orderPayment
     *
     * @throws LocalizedException
     *
     * @return void
     */
    protected function attachTokenExtensionAttribute(OrderPaymentInterface $orderPayment)
    {
        $paymentToken = null;
        $addInformation = $orderPayment->getAdditionalInformation();
        if (empty($addInformation[PaymentTokenInterface::PUBLIC_HASH])) {
            throw new LocalizedException('Public hash should be defined');
        }

        $customerId = isset($addInformation[PaymentTokenInterface::CUSTOMER_ID]) ?
            $addInformation[PaymentTokenInterface::CUSTOMER_ID] : null;

        $publicHash = $addInformation[PaymentTokenInterface::PUBLIC_HASH];

        $paymentToken = $this->tokenManagement->getByPublicHash($publicHash, $customerId);

        if (!$paymentToken) {
            throw new LocalizedException('Public hash should be defined');
        }

        $extensionAttributes = $this->getPaymentExtensionAttributes($orderPayment);

        $extensionAttributes->setVaultPaymentToken($paymentToken);
    }

    /**
     * Returns Payment's extension attributes.
     *
     * @param OrderPaymentInterface $payment
     *
     * @return \Magento\Sales\Api\Data\OrderPaymentExtensionInterface
     */
    protected function getPaymentExtensionAttributes(OrderPaymentInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->payExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }

    /**
     * Attaches credit card info.
     *
     * @param OrderPaymentInterface $payment
     *
     * @return void
     */
    protected function attachCreditCardInfo(OrderPaymentInterface $payment): void
    {
        $paymentToken = $payment->getExtensionAttributes()
            ->getVaultPaymentToken();
        if ($paymentToken === null) {
            return;
        }

        $tokenDetails = $paymentToken->getTokenDetails();
        if ($tokenDetails === null) {
            return;
        }

        if (is_string($tokenDetails)) {
            $tokenDetails = $this->jsonSerializer->unserialize($paymentToken->getTokenDetails());
        }
        if (is_array($tokenDetails)) {
            $payment->addData($tokenDetails);
        }
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        throw new LocalizedException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        throw new LocalizedException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        throw new LocalizedException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function canReviewPayment()
    {
        throw new LocalizedException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function acceptPayment(InfoInterface $payment)
    {
        throw new LocalizedException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function denyPayment(InfoInterface $payment)
    {
        throw new LocalizedException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function getConfigData($field, $storeId = null)
    {
        return $this->getConfiguredValue($field, $storeId);
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->eventManager->dispatch(
            'payment_method_assign_data_vault',
            [
                AbstractDataAssignObserver::METHOD_CODE => $this,
                AbstractDataAssignObserver::MODEL_CODE  => $this->getInfoInstance(),
                AbstractDataAssignObserver::DATA_CODE   => $data,
            ]
        );

        $this->eventManager->dispatch(
            'payment_method_assign_data_vault_'.$this->getProviderCode(),
            [
                AbstractDataAssignObserver::METHOD_CODE => $this,
                AbstractDataAssignObserver::MODEL_CODE  => $this->getInfoInstance(),
                AbstractDataAssignObserver::DATA_CODE   => $data,
            ]
        );

        return $this->vaultProvider->assignData($data);
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return $this->vaultProvider->isAvailable($quote)
            && $this->config->getValue(self::$activeKey, $this->getStore() ?: ($quote ? $quote->getStoreId() : null));
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function isActive($storeId = null)
    {
        return $this->vaultProvider->isActive($storeId)
            && $this->config->getValue(self::$activeKey, $this->getStore() ?: $storeId);
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function initialize($paymentAction, $stateObject)
    {
        $commandExecutor = $this->commandManagerPool->get(
            $this->vaultProvider->getCode()
        );

        $this->attachTokenExtensionAttribute($this->getInfoInstance());
        $this->attachCreditCardInfo($this->getInfoInstance());

        $commandExecutor->executeByCode(
            VaultPaymentInterface::VAULT_AUTHORIZE_COMMAND,
            $this->getInfoInstance(),
            [
                'payment'       => $this->getProviderCode(),
                'paymentAction' => $paymentAction,
                'stateObject'   => $stateObject,
            ]
        );

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function getConfigPaymentAction()
    {
        return $this->vaultProvider->getConfigPaymentAction();
    }

    /**
     * @inheritdoc
     *
     * @since 100.1.0
     */
    public function getProviderCode()
    {
        return $this->vaultProvider->getCode();
    }
}
