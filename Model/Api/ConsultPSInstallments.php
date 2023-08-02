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

use Laminas\Http\ClientFactory;
use Laminas\Http\Request;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\LaminasClient;
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
     * @var ClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * InterestManagement constructor.
     *
     * @param ConfigBase    $configBase
     * @param ConfigCc      $configCc
     * @param ClientFactory $httpClientFactory
     * @param Json          $json
     */
    public function __construct(
        ConfigBase $configBase,
        Configcc $configCc,
        ClientFactory $httpClientFactory,
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
     * @param int|null  $storeId
     * @param string    $creditCardBin
     * @param string    $amount
     *
     * @return array
     */
    public function getPagBankInstallments($storeId, $creditCardBin, $amount)
    {
        /** @var LaminasClient $client */
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
            $client->setHeaders($headers);
            $client->setMethod(Request::METHOD_GET);
            $client->setOptions($apiConfigs);
            $client->setParameterGet($data);
            $responseBody = $client->send()->getBody();

            $dataResponse = $this->json->unserialize($responseBody);

            if (!empty($dataResponse['payment_methods'])) {
                $targets = $dataResponse['payment_methods']['credit_card'];

                foreach ($targets as $brand) {
                    $list = $brand;
                }

                $list = $this->getAvailableInstallments($list['installment_plans'], $storeId);
               
                $response = $list;
            }

            if (!$client->send()->getStatusCode()) {
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

    /**
     * Get Available Installments.
     *
     * @param array     $list
     * @param int|null  $storeId
     * @return array
     */
    public function getAvailableInstallments($list, $storeId)
    {
        $minInstallment = $this->configCc->getMinValuelInstallment($storeId) * 100;

        foreach ($list as $key => $allInstallments) {
            if ($key >= 1) {
                if ($allInstallments['installment_value'] <= $minInstallment) {
                    unset($list[$key]);
                }
            }
        }

        return $list;
    }
}
