<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

declare(strict_types=1);

namespace PagBank\PaymentMagento\Model\Api;

use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\LaminasClient;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use PagBank\PaymentMagento\Api\PagBankVaultManagementInterface;
use PagBank\PaymentMagento\Api\Data\PagBankVaultTokenInterface;
use PagBank\PaymentMagento\Api\Data\PagBankVaultTokenInterfaceFactory;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;
use PagBank\PaymentMagento\Gateway\Config\ConfigCc;

/**
 * Class PagBank Vault Management - Create token vault from zero dollar transaction.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PagBankVaultManagement implements PagBankVaultManagementInterface
{
    /**
     * @var PaymentTokenManagementInterface
     */
    protected $payTokenManagement;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $payTokenRepository;

    /**
     * @var PaymentTokenFactoryInterface
     */
    protected $paymentTokenFactory;

    /**
     * @var PagBankVaultTokenInterfaceFactory
     */
    protected $vaultTokenFactory;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var Json
     */
    protected $serializer;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Constructor
     *
     * @param PaymentTokenManagementInterface $payTokenManagement
     * @param PaymentTokenRepositoryInterface $payTokenRepository
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param PagBankVaultTokenInterfaceFactory $vaultTokenFactory
     * @param ConfigBase $configBase
     * @param ConfigCc $configCc
     * @param Json $serializer
     * @param StoreManagerInterface $storeManager
     * @param ZendClientFactory $httpClientFactory
     * @param EncryptorInterface $encryptor
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        PaymentTokenManagementInterface $payTokenManagement,
        PaymentTokenRepositoryInterface $payTokenRepository,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        PagBankVaultTokenInterfaceFactory $vaultTokenFactory,
        ConfigBase $configBase,
        ConfigCc $configCc,
        Json $serializer,
        StoreManagerInterface $storeManager,
        ZendClientFactory $httpClientFactory,
        EncryptorInterface $encryptor
    ) {
        $this->payTokenManagement = $payTokenManagement;
        $this->payTokenRepository = $payTokenRepository;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->vaultTokenFactory = $vaultTokenFactory;
        $this->configBase = $configBase;
        $this->configCc = $configCc;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
        $this->httpClientFactory = $httpClientFactory;
        $this->encryptor = $encryptor;
    }

    /**
     * Create vault token for customer's credit card.
     *
     * @param int $customerId
     * @param string $encryptedCard
     * @return PagBankVaultTokenInterface
     * @throws LocalizedException
     */
    public function createVaultToken(int $customerId, string $encryptedCard)
    {
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
            $websiteId = (int) $this->storeManager->getStore()->getWebsiteId();

            $cardData = $this->createPagBankToken($storeId, $encryptedCard);
            if (!$cardData) {
                throw new LocalizedException(__('Invalid card data'));
            }

            $ccType = $this->getCreditCardType($cardData['cc_type']);
            $publicHash = $this->generatePublicHash($cardData);

            $existingToken = $this->payTokenManagement->getByPublicHash($publicHash, $customerId);
            if ($existingToken !== null) {
                return $this->handleExistingToken($existingToken);
            }

            $paymentToken = $this->createPaymentToken($customerId, $websiteId, $publicHash, $cardData, $ccType);
            $this->payTokenRepository->save($paymentToken);

            return $this->createVaultTokenResponse($paymentToken, $cardData, $ccType, $websiteId);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Handle existing token - reactivate if inactive.
     *
     * @param PaymentTokenInterface $existingToken
     * @return PagBankVaultTokenInterface
     * @throws LocalizedException
     */
    private function handleExistingToken(PaymentTokenInterface $existingToken)
    {
        if (!$existingToken->getIsActive() || !$existingToken->getIsVisible()) {
            return $this->reactivateToken($existingToken);
        }
        throw new LocalizedException(__('Card already exists and is active.'));
    }

    /**
     * Reactivate existing token.
     *
     * @param PaymentTokenInterface $existingToken
     * @return PagBankVaultTokenInterface
     */
    private function reactivateToken(PaymentTokenInterface $existingToken)
    {
        $existingToken->setIsActive(true);
        $existingToken->setIsVisible(true);
        $this->payTokenRepository->save($existingToken);

        $details = $this->serializer->unserialize($existingToken->getTokenDetails());
        $ccType = $this->getCreditCardType($details['cc_type']);

        $vaultToken = $this->vaultTokenFactory->create();
        $vaultToken->setPagBankToken($existingToken->getGatewayToken())
            ->setWebsiteId($existingToken->getWebsiteId())
            ->setPublicHash($existingToken->getPublicHash())
            ->setCardBrand($ccType)
            ->setLastDigits($details['cc_last4'])
            ->setCreatedAt($existingToken->getCreatedAt())
            ->setIsActive(true);

        return $vaultToken;
    }

    /**
     * Create new payment token.
     *
     * @param int $customerId
     * @param int $websiteId
     * @param string $publicHash
     * @param array $cardData
     * @param string $ccType
     * @return PaymentTokenInterface
     */
    private function createPaymentToken($customerId, $websiteId, $publicHash, array $cardData, string $ccType)
    {
        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setCustomerId($customerId)
            ->setWebsiteId($websiteId)
            ->setPaymentMethodCode('pagbank_paymentmagento_cc')
            ->setPublicHash($publicHash)
            ->setExpiresAt(
                $this->getExpirationDate($cardData['cc_exp_month'], $cardData['cc_exp_year'])
            )
            ->setGatewayToken($cardData['token'])
            ->setIsVisible(true)
            ->setIsActive(true)
            ->setTokenDetails($this->convertDetailsToString([
                'cc_bin' => $cardData['cc_bin'],
                'cc_last4' => $cardData['cc_last4'],
                'cc_exp_year' => $cardData['cc_exp_year'],
                'cc_exp_month' => $cardData['cc_exp_month'],
                'cc_type' => $ccType
            ]));

        return $paymentToken;
    }

    /**
     * Create vault token response.
     *
     * @param PaymentTokenInterface $paymentToken
     * @param array $cardData
     * @param string $ccType
     * @param int $websiteId
     * @return PagBankVaultTokenInterface
     */
    private function createVaultTokenResponse(PaymentTokenInterface $paymentToken, array $cardData, string $ccType, int $websiteId)
    {
        /** @var PagBankVaultTokenInterfaceFactory $vault */
        $vaultToken = $this->vaultTokenFactory->create();
        $vaultToken->setPagBankToken($cardData['token'])
            ->setWebsiteId($websiteId)
            ->setPublicHash($paymentToken->getPublicHash())
            ->setCardBrand($ccType)
            ->setLastDigits($cardData['cc_last4'])
            ->setCreatedAt($paymentToken->getCreatedAt())
            ->setIsActive(true);

        return $vaultToken;
    }

    /**
     * Create PagBank Token.
     *
     * @param int $storeId
     * @param string $encryptedCard
     * @return array
     * @throws LocalizedException
     */
    private function createPagBankToken($storeId, $encryptedCard)
    {
        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->configBase->getApiUrl($storeId);
        $apiConfigs = $this->configBase->getApiConfigs();
        $headers = $this->configBase->getApiHeaders($storeId);
        $uri = $url.'tokens/cards';

        $data = [
            'encrypted' => $encryptedCard
        ];

        try {
            $client->setUri($uri);
            $client->setHeaders($headers);
            $client->setMethod(ZendClient::POST);
            $client->setConfig($apiConfigs);
            $client->setRawData($this->serializer->serialize($data), 'application/json');

            $responseBody = $client->request()->getBody();

            $response = $this->serializer->unserialize($responseBody);

            if (isset($response['error_messages'])) {
                throw new LocalizedException(
                    __('PagBank API error: %1', $response['error_messages'][0]['description'])
                );
            }

            return [
                'token' => $response['id'],
                'cc_bin' => $response['first_digits'],
                'cc_last4' => $response['last_digits'],
                'cc_exp_month' => $response['exp_month'],
                'cc_exp_year' => $response['exp_year'],
                'cc_type' => $response['brand']
            ];

        } catch (\Exception $e) {
            throw new LocalizedException(__('Error creating card token: %1', $e->getMessage()));
        }
    }

    /**
     * Generate public hash for payment token.
     *
     * @param array $cardData
     * @return string
     */
    private function generatePublicHash($cardData): string
    {
        $hashData = $cardData['cc_bin'] . $cardData['cc_last4'] . $cardData['cc_type'];
        return $this->encryptor->getHash($hashData);
    }

    /**
     * Get expiration date.
     *
     * @param string $month
     * @param string $year
     * @return string
     */
    private function getExpirationDate(string $month, string $year): string
    {
        return sprintf('%s-%s-01 00:00:00', $year, $month);
    }

    /**
     * Convert payment token details to string.
     *
     * @param array $details
     * @return string
     */
    private function convertDetailsToString(array $details): string
    {
        return $this->serializer->serialize($details);
    }

    /**
     * Get Credit Card Type.
     *
     * @param string $type
     * @return string
     */
    private function getCreditCardType(string $type): ?string
    {
        $type = strtoupper($type);
        $mapper = $this->configCc->getCcTypesMapper();
        return $mapper[$type] ?? $type;
    }
}
