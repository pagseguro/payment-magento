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
 * Class Boleto - Boleto payment information.
 */
class Boleto extends ConfigurableInfo
{
    /**
     * Boleto Info template.
     */
    public const TEMPLATE = 'PagBank_PaymentMagento::info/boleto/instructions.phtml';

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
     * @return Phrase
     */
    protected function getValueView($field, $value)
    {
        if ($field === 'boleto_pdf_href') {
            return $this->getBoletoPDFLink($value);
        }

        return parent::getValueView($field, $value);
    }

    /**
     * Get Url to Image Qr Code.
     *
     * @param string $path
     *
     * @return Phrase
     */
    public function getBoletoPDFLink($path)
    {
        return $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]).$path;
    }
}
