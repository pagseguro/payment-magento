<?php
/**
 * Copyright © PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Model\Adminhtml\Source;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Config\Model\Config;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;

/**
 * Class PaymentGroup - Fieldset renderer for PagBank.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class PaymentGroup extends Fieldset
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js      $jsHelper
     * @param Config  $config
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Config $config,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $authSession,
            $jsHelper,
            $data
        );
        $this->config = $config;
    }

    /**
     * Add custom css class.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getFrontendClass($element)
    {
        return parent::_getFrontendClass($element).' with-button';
    }

    /**
     * Return header title part of html for payment solution.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        $html = '<div class="config-heading" >';
        $htmlId = $element->getHtmlId();
        $html .= '<div class="button-container"><button type="button"'.
            ' class="button action-configure'.
            '" id="'.
            $htmlId.
            '-head" onclick="psToggleSolution.call(this, \''.
            $htmlId.
            "', '".
            $this->getUrl(
                'adminhtml/*/state'
            ).'\'); return false;"><span class="state-closed">'.__(
                'Configure'
            ).'</span><span class="state-opened">'.__(
                'Close'
            ).'</span></button>';

        $html .= '</div>';
        $html .= '<div class="heading"><strong>'.$element->getLegend().'</strong>';

        if ($element->getComment()) {
            $html .= '<span class="heading-intro">'.$element->getComment().'</span>';
        }
        $html .= '<div class="config-alt"></div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Return header comment part of html for payment solution.
     *
     * @param AbstractElement $element
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getHeaderCommentHtml($element)
    {
        return '';
    }

    /**
     * Get collapsed state on-load.
     *
     * @param AbstractElement $element
     *
     * @return false
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _isCollapseState($element)
    {
        return false;
    }

    /**
     * Return extra Js.
     *
     * @param AbstractElement $element
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getExtraJs($element)
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.psToggleSolution = function (id, url) {
                var doScroll = false;
                Fieldset.toggleCollapse(id, url);
                if ($(this).hasClassName(\"open\")) {
                    \$$(\".with-button button.button\").each(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName(\"open\")) {
                            $(anotherButton).click();
                            doScroll = true;
                        }
                    }.bind(this));
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this));
                    window.scrollTo(pos[0], pos[1] - 45);
                }
            }
        });";

        return $this->_jsHelper->getScript($script);
    }
}
