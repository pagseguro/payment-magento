<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Block\Sales\Form;

use Magento\Framework\View\Element\Template\Context;
use PagBank\PaymentMagento\Gateway\Config\ConfigPix;

/**
 * Class Pix - Form for payment by Pix.
 */
class Pix extends \Magento\Payment\Block\Form
{
    /**
     * Pix Form template.
     */
    public const TEMPLATE = 'PagBank_PaymentMagento::form/pix.phtml';

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
     * @var ConfigPix
     */
    protected $configPix;

    /**
     * @param Context   $context
     * @param ConfigPix $configPix
     */
    public function __construct(
        Context $context,
        ConfigPix $configPix
    ) {
        parent::__construct($context);
        $this->configPix = $configPix;
    }

    /**
     * Title - Pix.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->configPix->getTitle();
    }

    /**
     * Get Instruction.
     *
     * @param int|null $storeId
     *
     * @return Phrase
     */
    public function getInstruction()
    {
        $text = $this->configPix->getInstructionCheckout();

        $time = $this->configPix->getTextTime();

        $replaceText = __($text, $time);

        return $replaceText;
    }
}
