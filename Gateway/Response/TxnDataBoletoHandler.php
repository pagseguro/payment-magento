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
 * Txn Data Boleto Handler - Reply Flow for Boleto data.
 */
class TxnDataBoletoHandler implements HandlerInterface
{
    /**
     * Boleto Line Code - Payment Addtional Information.
     */
    public const PAYMENT_INFO_BOLETO_LINE_CODE = 'boleto_line_code';

    /**
     * Boleto PDF Href - Payment Addtional Information.
     */
    public const PAYMENT_INFO_BOLETO_PDF_HREF = 'boleto_pdf_href';

    /**
     * Expiration Date - Payment Addtional Information.
     */
    public const PAYMENT_INFO_EXPIRATION_DATE = 'expiration_date';

    /**
     * Response Pay PagBank Id - Block name.
     */
    public const RESPONSE_PAGBANK_ID = 'id';

    /**
     * Response Pay Charges - Block name.
     */
    public const RESPONSE_CHARGES = 'charges';

    /**
     * Response Pay Charges Payment Method - Block name.
     */
    public const RESPONSE_PAYMENT_METHOD = 'payment_method';

    /**
     * Response Pay Boleto - Block name.
     */
    public const RESPONSE_PAYMENT_METHOD_BOLETO = 'boleto';

    /**
     * Response Pay Boleto Typeful Line - Block name.
     */
    public const RESPONSE_PAYMENT_METHOD_BOLETO_TYPEFUL_LINE = 'formatted_barcode';

    /**
     * Response Pay Boleto Expiration Date - Block name.
     */
    public const RESPONSE_PAYMENT_METHOD_BOLETO_EXPIRATION_DATE = 'due_date';

    /**
     * Response Pay Charges Links - Block name.
     */
    public const RESPONSE_LINKS = 'links';

    /**
     * Response Pay Charges Links Href - Block name.
     */
    public const RESPONSE_LINKS_HREF = 'href';

    /**
     * Response Pay Charges Links Media - Block name.
     */
    public const RESPONSE_LINKS_MEDIA = 'media';

    /**
     * Response Pay Charges Links Media for PDF - Block name.
     */
    public const RESPONSE_LINKS_MEDIA_FOR_PDF = 'application/pdf';

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

        $charges = $response[self::RESPONSE_CHARGES][0];

        $pagbankPayId = $charges[self::RESPONSE_PAGBANK_ID];

        /** Popule Additional Info */
        $this->setAdditionalInfo($payment, $pagbankPayId, $charges);
    }

    /**
     * Set Additional Info.
     *
     * @param InfoInterface $payment
     * @param string        $pagbankPayId
     * @param array         $charges
     *
     * @return void
     */
    public function setAdditionalInfo($payment, $pagbankPayId, $charges)
    {
        $linkToPDF = null;
        $paymetMethod = $charges[self::RESPONSE_PAYMENT_METHOD];
        $payBoleto = $paymetMethod[self::RESPONSE_PAYMENT_METHOD_BOLETO];

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_BOLETO_LINE_CODE,
            $payBoleto[self::RESPONSE_PAYMENT_METHOD_BOLETO_TYPEFUL_LINE]
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_EXPIRATION_DATE,
            $payBoleto[self::RESPONSE_PAYMENT_METHOD_BOLETO_EXPIRATION_DATE].' 23:59:59'
        );

        $links = $charges[self::RESPONSE_LINKS];
        foreach ($links as $link) {
            if ($link[self::RESPONSE_LINKS_MEDIA] === self::RESPONSE_LINKS_MEDIA_FOR_PDF) {
                $linkToPDF = $link[self::RESPONSE_LINKS_HREF];
            }
        }

        if ($linkToPDF) {
            $linkToPDF = $this->configBase->copyFile('boleto', $linkToPDF, $pagbankPayId);
            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_BOLETO_PDF_HREF,
                $linkToPDF
            );
        }
    }
}
