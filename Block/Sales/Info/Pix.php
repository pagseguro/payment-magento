<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Block\Sales\Info;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

/**
 * Class Pix - Pix payment information.
 */
class Pix extends ConfigurableInfo
{
    /**
     * Pix Info template.
     */
    public const TEMPLATE = 'PagBank_PaymentMagento::info/pix/instructions.phtml';

    /**
     * Get relevant path to template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return self::TEMPLATE;
    }

    /**
     * Returns value view.
     *
     * @param string $field
     * @param string $value
     *
     * @return string | Phrase
     */
    protected function getValueView($field, $value)
    {
        if ($field === 'qr_code_image') {
            return $this->getImageQrCode($value);
        }

        return parent::getValueView($field, $value);
    }

    /**
     * Get Url to Image Qr Code.
     *
     * @param string $qrCode
     *
     * @return string
     */
    public function getImageQrCode($qrCode)
    {
        return $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]).$qrCode;
    }
}
