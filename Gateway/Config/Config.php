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

namespace PagBank\PaymentMagento\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;
use PagBank\PaymentMagento\Gateway\Request\AddressDataRequest;

/**
 * Class Config - Returns form of payment configuration properties.
 */
class Config extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'pagbank_paymentmagento';

    /**
     * @const string
     */
    public const ENVIRONMENT_PRODUCTION = 'production';

    /**
     * @const string
     */
    public const ENDPOINT_PRODUCTION = 'https://api.pagseguro.com/';

    /**
     * @const string
     */
    public const ENDPOINT_SDK_PRODUCTION = 'https://sdk.pagseguro.com/';

    /**
     * @const string
     */
    public const ENDPOINT_CONNECT_PRODUCTION = 'https://connect.pagseguro.uol.com.br/oauth2/authorize';

    /**
     * @const string
     */
    public const APP_ID_PRODUCTION = '1782a592-5eea-442c-8e67-c940d020dc53';

    /**
     * @const string
     */
    public const APP_ID_THIRTY_PRODUCTION = '4875151e-9caa-4019-b6b7-d29852efe7ee';

    /**
     * @const string
     */
    public const APP_ID_FOURTEEN_PRODUCTION = 'fd0305da-da00-42b6-a9f4-dac498bc05e4';
    /**
     * @const string
     */
    public const ENVIRONMENT_SANDBOX = 'sandbox';

    /**
     * @const string
     */
    public const ENDPOINT_SANDBOX = 'https://sandbox.api.pagseguro.com/';

    /**
     * @const string
     */
    public const ENDPOINT_SDK_SANDBOX = 'https://sandbox.sdk.pagseguro.com/';

    /**
     * @const string
     */
    public const APP_ID_SANDBOX = '16670d56-c0cb-4a45-a7c7-616868c2c94d';

    /**
     * @const string
     */
    public const APP_ID_THIRTY_SANDBOX = '38a3acd5-b628-4bab-8364-079343cce978';

    /**
     * @const string
     */
    public const APP_ID_FOURTEEN_SANDBOX = 'ebde0a80-a80c-4e81-b375-9d1e8b8671d3';

    /**
     * @const string
     */
    public const ENDPOINT_CONNECT_SANDBOX = 'https://connect.sandbox.pagseguro.uol.com.br/oauth2/authorize';

    /**
     * @const string
     */
    public const CLIENT = 'Magento';

    /**
     * @const string
     */
    public const CLIENT_VERSION = '2.0.0';

    /**
     * @const string
     */
    public const OAUTH_CODE = 'code';

    /**
     * @const string
     */
    public const OAUTH_SCOPE = 'payments.read+payments.create+payments.refund';

    /**
     * @const string
     */
    public const OAUTH_STATE = 'active';

    /**
     * @const string
     */
    public const OAUTH_CODE_CHALLENGER_METHOD = 'S256';

    /**
     * @const int
     */
    public const ROUND_UP = 100;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DriverFile
     */
    protected $driver;

    /**
     * @var File
     */
    protected $fileIo;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param DriverFile           $driver
     * @param File                 $fileIo
     * @param Filesystem           $filesystem
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DriverFile $driver,
        File $fileIo,
        Filesystem $filesystem
    ) {
        parent::__construct($scopeConfig, self::METHOD);
        $this->scopeConfig = $scopeConfig;
        $this->driver = $driver;
        $this->fileIo = $fileIo;
        $this->filesystem = $filesystem;
    }

    /**
     * Formant Price.
     *
     * @param string|int|float $amount
     *
     * @return float
     */
    public function formatPrice($amount): float
    {
        return round((float) $amount, 2) * self::ROUND_UP;
    }

    /**
     * Gets the API endpoint URL.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getApiUrl($storeId = null): ?string
    {
        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            return self::ENDPOINT_SANDBOX;
        }

        return self::ENDPOINT_PRODUCTION;
    }

    /**
     * Gets the API endpoint URL.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getApiSDKUrl($storeId = null): ?string
    {
        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            return self::ENDPOINT_SDK_SANDBOX;
        }

        return self::ENDPOINT_SDK_PRODUCTION;
    }

    /**
     * Gets the Environment Mode.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getEnvironmentMode($storeId = null): ?string
    {
        $environment = $this->getAddtionalValue('environment', $storeId);

        if ($environment === 'sandbox') {
            return self::ENVIRONMENT_SANDBOX;
        }

        return self::ENVIRONMENT_PRODUCTION;
    }

    /**
     * Get Type App.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getTypeApp($storeId = null): ?string
    {
        return $this->getAddtionalValue('type_app', $storeId);
    }

    /**
     * Get Api Headers.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getApiHeaders($storeId = null)
    {
        $oAuth = $this->getMerchantGatewayOauth($storeId);

        return [
            'Content-Type'      => 'application/json',
            'Authorization'     => 'Bearer '.$oAuth,
            'x-api-version'     => '4.0',
        ];
    }

    /**
     * Get Api Configs.
     *
     * @return array
     */
    public function getApiConfigs()
    {
        return [
            'maxredirects'  => 0,
            'timeout'       => 45000,
        ];
    }

    /**
     * Get Pub Header.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getPubHeader($storeId = null)
    {
        $environment = $this->getAddtionalValue('environment', $storeId);
        $pub = $this->getAddtionalValue('cipher_text_production', $storeId);
        $app = $this->getTypeApp($storeId);

        if ($environment === 'sandbox') {
            $pub = $this->getAddtionalValue('cipher_text_sandbox');
        }

        if ($app === 'd14') {
            $pub = $this->getAddtionalValue('d14_cipher_text_production', $storeId);
            if ($environment === 'sandbox') {
                $pub = $this->getAddtionalValue('d14_cipher_text_sandbox');
            }
        }

        if ($app === 'd30') {
            $pub = $this->getAddtionalValue('d30_cipher_text_production', $storeId);
            if ($environment === 'sandbox') {
                $pub = $this->getAddtionalValue('d30_cipher_text_sandbox');
            }
        }

        return [
            'Content-Type'      => 'application/json',
            'Authorization'     => 'Pub '.$pub,
            'x-api-version'     => '4.0',
        ];
    }

    /**
     * Get Rewrite notification url.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getRewriteNotificationUrl($storeId = null): ?string
    {
        $environment = $this->getEnvironmentMode($storeId);

        $custom = $this->getAddtionalValue('production_custom_notification_url', $storeId);

        if ($environment === 'sandbox') {
            $custom = $this->getAddtionalValue('sandbox_custom_notification_url', $storeId);
        }

        return $custom;
    }

    /**
     * Gets the Merchant Gateway OAuth.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getMerchantGatewayOauth($storeId = null): ?string
    {
        $oauth = $this->getAddtionalValue('access_token_production', $storeId);

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $oauth = $this->getAddtionalValue('access_token_sandbox', $storeId);
        }

        return $oauth;
    }

    /**
     * Gets the Merchant Gateway Refresh OAuth.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getMerchantGatewayRefreshOauth($storeId = null): ?string
    {
        $oauth = $this->getAddtionalValue('refresh_token_production', $storeId);

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $oauth = $this->getAddtionalValue('refresh_token_sandbox', $storeId);
        }

        return $oauth;
    }

    /**
     * Gets the Merchant Gateway Public Key.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getMerchantGatewayPublicKey($storeId = null): ?string
    {
        $publicKey = $this->getAddtionalValue('public_key_production', $storeId);

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $publicKey = $this->getAddtionalValue('public_key_sandbox', $storeId);
        }

        return $publicKey;
    }

    /**
     * Gets the AddtionalValues.
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getAddtionalValue($field, $storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Soft Descriptor.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getSoftDescriptor($storeId = null): ?string
    {
        $soft = $this->getAddtionalValue('soft_descriptor', $storeId);

        return preg_replace('/\s+/', '', $soft);
    }

    /**
     * Copy File.
     *
     * @param string $type
     * @param string $fileToCopy
     * @param string $transactionId
     *
     * @return string|null
     */
    public function copyFile($type, $fileToCopy, $transactionId): ?string
    {
        $fileName = null;

        $extension = '.png';

        if ($type === 'boleto') {
            $extension = '.pdf';
        }

        $fileName = 'pagbank/'.$type.'/'.$transactionId.$extension;

        if ($this->hasPathDir($type)) {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            $filePath = $this->getPathFile($type, $mediaDirectory, $transactionId);

            $generate = $this->createFile($mediaDirectory, $filePath, $fileToCopy);

            if (!$generate) {
                return null;
            }
        }

        return $fileName;
    }

    /**
     * Get Path File.
     *
     * @param string     $type
     * @param Filesystem $mediaDirectory
     * @param string     $transactionId
     *
     * @return string
     */
    public function getPathFile($type, $mediaDirectory, $transactionId): string
    {
        $extension = '.png';

        if ($type === 'boleto') {
            $extension = '.pdf';
        }

        $fileName = 'pagbank/'.$type.'/'.$transactionId.$extension;

        $filePath = $mediaDirectory->getAbsolutePath($fileName);

        return $filePath;
    }

    /**
     * Create File.
     *
     * @param WriteInterface $writeDirectory
     * @param string         $filePath
     * @param string         $fileToCopy
     *
     * @throws FileSystemException
     *
     * @return bool
     */
    public function createFile(WriteInterface $writeDirectory, $filePath, $fileToCopy): bool
    {
        $content = $this->driver->fileGetContents($fileToCopy);

        try {
            $stream = $writeDirectory->openFile($filePath, 'w+');
            $stream->lock();
            $stream->write($content);
            $stream->unlock();
            $stream->close();
        } catch (FileSystemException $ex) {
            return false;
        }

        return true;
    }

    /**
     * Check or Create Dir.
     *
     * @param string $type
     *
     * @return bool
     */
    public function hasPathDir($type): bool
    {
        $path = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('/pagbank/'.$type);

        return $this->fileIo->checkAndCreateFolder($path);
    }

    /**
     * Get Address Limit to Send.
     *
     * @param string $field
     *
     * @return int $limitSend
     */
    public function getAddressLimitSend($field): int
    {
        $limits = [];

        $limits = [
            AddressDataRequest::STREET      => 160,
            AddressDataRequest::NUMBER      => 20,
            AddressDataRequest::LOCALITY    => 60,
            AddressDataRequest::COMPLEMENT  => 40,
        ];

        return $limits[$field];
    }

    /**
     * Value For Field Address.
     *
     * @param AddressAdapterInterface $address
     * @param string                  $field
     *
     * @return string|null
     */
    public function getValueForAddress($address, $field): ?string
    {
        $streets = [];

        $value = (int) $this->getAddtionalValue($field);

        $limitSend = $this->getAddressLimitSend($field);

        $streets = [
            0 => $address->getStreetLine1(),
            1 => $address->getStreetLine2(),
            2 => $address->getStreetLine3(),
            3 => $address->getStreetLine4(),
        ];

        $street = $streets[$value];

        if (!$street) {
            $street = $streets[0];
        }

        return substr($street, 0, $limitSend);
    }
}
