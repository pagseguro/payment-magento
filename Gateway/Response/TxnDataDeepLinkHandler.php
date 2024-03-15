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
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;

/**
 * Txn Data DeepLink Handler - Reply Flow for DeepLink data.
 */
class TxnDataDeepLinkHandler implements HandlerInterface
{

    /**
     * Creation Date Qrcode - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CREATION_DATE_QRCODE = 'creation_date_qrcode';

    /**
     * Expiration Date - Payment Addtional Information.
     */
    public const PAYMENT_INFO_EXPIRATION_DATE = 'expiration_date';

    /**
     * Qr Code Image - Payment Addtional Information.
     */
    public const PAYMENT_INFO_QR_CODE_URL_IMAGE = 'qr_code_url_image';

    /**
     * Deep Link Url - Payment Addtional Information.
     */
    public const PAYMENT_INFO_DEEP_LINK_URL = 'deep_link_url';

    /**
     * Response Pay PagBank Id - Block name.
     */
    public const REPONSE_PAGBANK_ID = 'id';

    /**
     * Response Pay Qr Codes - Block name.
     */
    public const RESPONSE_QR_CODES = 'qr_codes';

    /**
     * Response Pay Deep Link - Block name.
     */
    public const RESPONSE_DEEP_LINK = 'deep_links';

    /**
     * Response Pay Deep Link Url - Block name.
     */
    public const RESPONSE_DEEP_LINK_URL = 'url';

    /**
     * Response Pay Qr Codes - Block name.
     */
    public const RESPONSE_QR_CODES_ID = 'id';

    /**
     * Response Pay Qr Codes Expiration Date - Block name.
     */
    public const RESPONSE_QR_CODES_EXPIRATION_DATE = 'expiration_date';

    /**
     * Response Pay Qr Codes Text - Block name.
     */
    public const RESPONSE_QR_CODES_TEXT = 'text';

    /**
     * Response Pay Charges Links - Block name.
     */
    public const RESPONSE_QR_CODES_LINKS = 'links';

    /**
     * Response Pay Charges Links Href - Block name.
     */
    public const RESPONSE_QR_CODES_LINKS_HREF = 'href';

    /**
     * Response Pay Charges Links Media - Block name.
     */
    public const RESPONSE_QR_CODES_LINKS_MEDIA = 'media';

    /**
     * Response Pay Charges Links Media for Image PNG - Block name.
     */
    public const RESPONSE_QR_CODES_LINKS_MEDIA_FOR_IMAGE = 'image/png';

    /**
     * @var Config
     */
    protected $configBase;

    /**
     * @param Config $configBase
     */
    public function __construct(
        Config $configBase
    ) {
        $this->configBase = $configBase;
    }

    /**
     * Handles.
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

        $qrCodes = $response[self::RESPONSE_QR_CODES][0];

        $pagbankPayId = $qrCodes[self::RESPONSE_QR_CODES_ID];

        /** Popule Additional Info */
        $this->setAdditionalInfo($payment, $response, $pagbankPayId);
    }

    /**
     * Set Additional Info.
     *
     * @param InfoInterface $payment
     * @param array         $response
     * @param string        $pagbankPayId
     *
     * @return void
     */
    public function setAdditionalInfo(
        $payment,
        $response,
        $pagbankPayId
    ) {
        $qrCodes = $response[self::RESPONSE_QR_CODES][0];
        $deepLink = $response[self::RESPONSE_DEEP_LINK][0];

        if (isset($deepLink[self::RESPONSE_DEEP_LINK_URL])) {
            $linkToImage = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl='.
                urlencode($deepLink[self::RESPONSE_DEEP_LINK_URL]);

            $qrCodeImage = $this->configBase->copyFile('deep_link', $linkToImage, $pagbankPayId);
            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_QR_CODE_URL_IMAGE,
                $qrCodeImage
            );
        }

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_EXPIRATION_DATE,
            $qrCodes[self::RESPONSE_QR_CODES_EXPIRATION_DATE]
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_DEEP_LINK_URL,
            $deepLink[self::RESPONSE_DEEP_LINK_URL]
        );
    }
}
