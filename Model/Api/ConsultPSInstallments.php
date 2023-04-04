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

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;
use PagBank\PaymentMagento\Gateway\Config\ConfigCc;

/**
 * Class Consult PagBank Installments - Get Installments information on PagBank.
 */
class ConsultPSInstallments
{
    /**
     * Credit Card - Block Name.
     */
    public const CREDIT_CARD = 'CREDIT_CARD';

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * InterestManagement constructor.
     *
     * @param ConfigBase        $configBase
     * @param ConfigCc          $configCc
     * @param ZendClientFactory $httpClientFactory
     * @param Json              $json
     */
    public function __construct(
        ConfigBase $configBase,
        Configcc $configCc,
        ZendClientFactory $httpClientFactory,
        Json $json
    ) {
        $this->configBase = $configBase;
        $this->configCc = $configCc;
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
    }

    /**
     * Get PagBank Installments.
     *
     * @param int    $storeId
     * @param string $creditCardBin
     * @param string $amount
     *
     * @return array
     */
    public function getPagBankInstallments($storeId, $creditCardBin, $amount)
    {
        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->configBase->getApiUrl($storeId);
        $apiConfigs = $this->configBase->getApiConfigs();
        $headers = $this->configBase->getApiHeaders($storeId);
        $uri = $url.'charges/fees/calculate';
        $response = [];
        $list = [];

        $data = [
            'payment_methods'               => self::CREDIT_CARD,
            'value'                         => $amount,
            'max_installments'              => $this->configCc->getMaxInstallments($storeId),
            'max_installments_no_interest'  => $this->configCc->getInterestFree($storeId),
            'credit_card_bin'               => $creditCardBin,
        ];

        try {
            $client->setUri($uri);
            $client->setConfig($apiConfigs);
            $client->setHeaders($headers);
            $client->setParameterGet($data);
            $client->setMethod(ZendClient::GET);

            $responseBody = $client->request()->getBody();
            $dataResponse = $this->json->unserialize($responseBody);

            if (!empty($dataResponse['payment_methods'])) {
                $targets = $dataResponse['payment_methods']['credit_card'];

                foreach ($targets as $brand) {
                    $list = $brand;
                }

                $response = $list['installment_plans'];
            }

            if (!$client->request()->isSuccessful()) {
                $response[0] = [
                    'installments'      => 1,
                    'installment_value' => $amount,
                    'interest_free'     => true,
                    'amount'            => [
                        'value' => $amount,
                    ],

                ];
            }
        } catch (InvalidArgumentException $exc) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
