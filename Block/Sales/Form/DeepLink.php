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
use PagBank\PaymentMagento\Gateway\Config\ConfigDeepLink;

/**
 * Class DeepLink - Form for payment by DeepLink.
 */
class DeepLink extends \Magento\Payment\Block\Form
{
    /**
     * DeepLink Form template.
     */
    public const TEMPLATE = 'PagBank_PaymentMagento::form/deep-link.phtml';

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
     * @var ConfigDeepLink
     */
    protected $configDeepLink;

    /**
     * @param Context        $context
     * @param ConfigDeepLink $configDeepLink
     */
    public function __construct(
        Context $context,
        ConfigDeepLink $configDeepLink
    ) {
        parent::__construct($context);
        $this->configDeepLink = $configDeepLink;
    }

    /**
     * Title - DeepLink.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->configDeepLink->getTitle();
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
        $text = $this->configDeepLink->getInstructionCheckout();

        $replaceText = __($text);

        return $replaceText;
    }
}
