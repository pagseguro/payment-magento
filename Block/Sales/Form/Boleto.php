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
use PagBank\PaymentMagento\Gateway\Config\ConfigBoleto;

/**
 * Class Boleto - Form for payment by boleto.
 */
class Boleto extends \Magento\Payment\Block\Form
{
    /**
     * Boleto Form template.
     */
    public const TEMPLATE = 'PagBank_PaymentMagento::form/boleto.phtml';

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
     * @var ConfigBoleto
     */
    protected $configBoleto;

    /**
     * @param Context      $context
     * @param ConfigBoleto $configBoleto
     */
    public function __construct(
        Context $context,
        ConfigBoleto $configBoleto
    ) {
        parent::__construct($context);
        $this->configBoleto = $configBoleto;
    }

    /**
     * Title - Boleto.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->configBoleto->getTitle();
    }

    /**
     * Instruction - Boleto.
     *
     * @return string
     */
    public function getInstruction()
    {
        return $this->configBoleto->getInstructionCheckout();
    }

    /**
     * Expiration - Boleto.
     *
     * @return string
     */
    public function getExpiration()
    {
        return $this->configBoleto->getExpirationFormat();
    }
}
