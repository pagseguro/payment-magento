<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Block\Sales\Order;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Success - Success page additional payment method information.
 */
class Success extends Template
{
    /**
     * Template Boleto.
     */
    public const TEMPLATE_BOLETO = 'PagBank_PaymentMagento::sales/order/success/boleto.phtml';

    /**
     * Template Pix.
     */
    public const TEMPLATE_PIX = 'PagBank_PaymentMagento::sales/order/success/pix.phtml';

    /**
     * Template Pix.
     */
    public const TEMPLATE_DEEP_LINK = 'PagBank_PaymentMagento::sales/order/success/deep-link.phtml';

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @param Context     $context
     * @param Session     $checkoutSession
     * @param HttpContext $httpContext
     * @param array       $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        HttpContext $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->httpContext = $httpContext;
    }

    /**
     * Get Template.
     *
     * @return string
     */
    public function getTemplate()
    {
        $paymentType = $this->getMethodCode();

        $templates = [
            'pagbank_paymentmagento_boleto'     => self::TEMPLATE_BOLETO,
            'pagbank_paymentmagento_pix'        => self::TEMPLATE_PIX,
            'pagbank_paymentmagento_deep_link'  => self::TEMPLATE_DEEP_LINK,
        ];

        if (isset($templates[$paymentType])) {
            return $templates[$paymentType];
        }

        return parent::getTemplate();
    }

    /**
     * Get Payment.
     *
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getPayment()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        return $order->getPayment()->getMethodInstance();
    }

    /**
     * Method Code.
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->getPayment()->getCode();
    }

    /**
     * Info payment.
     *
     * @param string $info
     *
     * @return string
     */
    public function getInfo(string $info)
    {
        return  $this->getPayment()->getInfoInstance()->getAdditionalInformation($info);
    }

    /**
     * Get Media Url.
     *
     * @param string $path
     *
     * @return string
     */
    public function getMediaUrl($path)
    {
        return $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]).$path;
    }
}
