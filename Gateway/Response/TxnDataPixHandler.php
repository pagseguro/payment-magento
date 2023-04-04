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
 * Txn Data Pix Handler - Reply Flow for Pix data.
 */
class TxnDataPixHandler implements HandlerInterface
{
    /**
     * Boleto Qr Code - Payment Addtional Information.
     */
    public const PAYMENT_INFO_QR_CODE = 'qr_code';

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
    public const PAYMENT_INFO_QR_CODE_IMAGE = 'qr_code_image';

    /**
     * Response Pay PagBank Id - Block name.
     */
    public const REPONSE_PAGBANK_ID = 'id';

    /**
     * Response Pay Qr Codes - Block name.
     */
    public const RESPONSE_QR_CODES = 'qr_codes';

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
        $this->setAdditionalInfo($payment, $qrCodes, $pagbankPayId);
    }

    /**
     * Set Additional Info.
     *
     * @param InfoInterface $payment
     * @param array         $qrCodes
     * @param string        $pagbankPayId
     *
     * @return void
     */
    public function setAdditionalInfo(
        $payment,
        $qrCodes,
        $pagbankPayId
    ) {
        $linkToImage = null;
        $links = $qrCodes[self::RESPONSE_QR_CODES_LINKS];
        foreach ($links as $link) {
            if ($link[self::RESPONSE_QR_CODES_LINKS_MEDIA] === self::RESPONSE_QR_CODES_LINKS_MEDIA_FOR_IMAGE) {
                $linkToImage = $link[self::RESPONSE_QR_CODES_LINKS_HREF];
            }
        }

        if ($linkToImage) {
            $qrCodeImage = $this->configBase->copyFile('pix', $linkToImage, $pagbankPayId);
            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_QR_CODE_IMAGE,
                $qrCodeImage
            );
        }

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_QR_CODE,
            $qrCodes[self::RESPONSE_QR_CODES_TEXT]
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_EXPIRATION_DATE,
            $qrCodes[self::RESPONSE_QR_CODES_EXPIRATION_DATE]
        );
    }
}
