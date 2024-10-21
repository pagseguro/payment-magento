<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Apps - Type apps.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Apps extends Field
{
    /**
     * Template oAuth.
     */
    public const TEMPLATE = 'PagBank_PaymentMagento::system/config/apps.phtml';

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->setTemplate(self::TEMPLATE);
    }
    
    /**
     * Override render method to remove label and td elements
     *
     * @param AbstractElement $element
     */
    public function render(AbstractElement $element)
    {
        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '<td colspan="2">';
        $html .= $this->_getElementHtml($element);
        $html .= '</td>';
        $html .= '</tr>';
        return $html;
    }

    /**
     * Render the field HTML
     *
     * @param AbstractElement $element
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->toHtml();
    }
}
