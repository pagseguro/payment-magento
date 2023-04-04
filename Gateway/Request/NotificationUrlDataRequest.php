<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Request;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;

/**
 * Gateway Requests for Notification Url - Structure for notification urls in orders.
 */
class NotificationUrlDataRequest implements BuilderInterface
{
    /**
     * Back Urls block name.
     */
    public const NOTIFICATION_URLS = 'notification_urls';

    /**
     * Path to Notification - url magento.
     */
    public const PATH_TO_NOTIFICATION = 'pagbank/notification/all';

    /**
     * @var UrlInterface
     */
    protected $frontendUrlBuilder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param UrlInterface $frontendUrlBuilder
     * @param Config       $config
     */
    public function __construct(
        UrlInterface $frontendUrlBuilder,
        Config $config
    ) {
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->config = $config;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(array $buildSubject)
    {
        $result = [];

        $notificationUrl = $this->frontendUrlBuilder->getUrl(self::PATH_TO_NOTIFICATION);

        $result[self::NOTIFICATION_URLS][] = $notificationUrl;

        $rewrite = $this->config->getRewriteNotificationUrl();

        if ($rewrite) {
            unset($result[self::NOTIFICATION_URLS]);
            $result[self::NOTIFICATION_URLS][] = $rewrite;
        }

        return $result;
    }
}
