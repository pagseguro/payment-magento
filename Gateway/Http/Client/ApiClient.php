<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Http\Client;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

/**
 * Class Api Client - Send and Get transfer to gateway.
 */
class ApiClient
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param Logger            $logger
     * @param ZendClientFactory $httpClientFactory
     * @param Json              $json
     */
    public function __construct(
        Logger $logger,
        ZendClientFactory $httpClientFactory,
        Json $json
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->logger = $logger;
        $this->json = $json;
    }

    /**
     * Send Post Request.
     *
     * @param TransferInterface $transferObject
     * @param string            $path
     * @param array             $request
     *
     * @return array
     */
    public function sendPostRequest($transferObject, $path, $request)
    {
        $data = [];
        $uri = $transferObject->getUri();
        $clientConfig = $transferObject->getClientConfig();
        $headers = $transferObject->getHeaders();
        $uri .= $path;
        $payload = $this->json->serialize($request);
        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();

        try {
            $client->setUri($uri);
            $client->setConfig($clientConfig);
            $client->setHeaders($headers);
            $client->setRawData($payload, 'application/json');
            $client->setMethod(ZendClient::POST);
            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
            $this->collectLogger(
                $uri,
                $headers,
                $responseBody,
                $request,
            );
        } catch (InvalidArgumentException $exc) {
            $this->collectLogger(
                $uri,
                $headers,
                $client->request()->getBody(),
                $request,
                $exc->getMessage()
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException('Invalid JSON was returned by the gateway');
        }

        return $data;
    }

    /**
     * Send Get Request.
     *
     * @param TransferInterface $transferObject
     * @param string            $path
     *
     * @return array
     */
    public function sendGetRequest($transferObject, $path)
    {
        $data = [];
        $uri = $transferObject->getUri();
        $clientConfig = $transferObject->getClientConfig();
        $headers = $transferObject->getHeaders();
        $uri .= $path;
        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();

        try {
            $client->setUri($uri);
            $client->setConfig($clientConfig);
            $client->setHeaders($headers);
            $client->setMethod(ZendClient::GET);
            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
            $this->collectLogger(
                $uri,
                $headers,
                $responseBody,
                [],
            );
        } catch (InvalidArgumentException $exc) {
            $this->collectLogger(
                $uri,
                $headers,
                $client->request()->getBody(),
                [],
                $exc->getMessage()
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException('Invalid JSON was returned by the gateway');
        }

        return $data;
    }

    /**
     * Collect Logger.
     *
     * @param string      $uri
     * @param string      $headers
     * @param array       $response
     * @param array       $request
     * @param string|null $message
     *
     * @return void
     */
    public function collectLogger(
        $uri,
        $headers,
        $response,
        $request = [],
        $message = null
    ) {
        $protectedRequest = ['email', 'tax_id', 'number'];

        $payload = $this->filterDebugData($request, $protectedRequest);

        $response = $this->json->unserialize($response);
        $responseFilter = $this->filterDebugData($response, $protectedRequest);

        $this->logger->debug(
            [
                'url'       => $uri,
                'header'    => $this->json->serialize($headers),
                'payload'   => $this->json->serialize($payload),
                'response'  => $this->json->serialize($responseFilter),
                'error_msg' => $message,
            ]
        );
    }

    /**
     * Recursive filter data by private conventions.
     *
     * @param array $debugData
     * @param array $debugDataKeys
     *
     * @return array
     */
    protected function filterDebugData(array $debugData, array $debugDataKeys)
    {
        $debugDataKeys = array_map('strtolower', $debugDataKeys);

        foreach (array_keys($debugData) as $key) {
            if (in_array(strtolower($key), $debugDataKeys)) {
                $debugData[$key] = '*** protected ***';
            } elseif (is_array($debugData[$key])) {
                $debugData[$key] = $this->filterDebugData($debugData[$key], $debugDataKeys);
            }
        }

        return $debugData;
    }
}
