<?php

/**
 * INFORMAÇÕES SOBRE LICENÇA
 *
 * Open Software License (OSL 3.0).
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Não edite este arquivo caso você pretenda atualizar este módulo futuramente
 * para novas versões.
 *
 * @category      maxiPago!
 * @package       MaxiPago_Payment
 * @copyright     Copyright (c) 2017
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class MaxiPago_Payment_Block_Adminhtml_System_Config_Form_Fieldset
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Return header title part of html for payment solution
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        $html = '<div class="config-heading meli" ><div class="heading"><strong>' . $element->getLegend();
        $html .= '</strong></div>';

        $html .= '<div class="button-container">' .
            '       <button type="button" ' .
            '               class="button" ' .
            '               id="' . $element->getHtmlId(). '-head" ' .
            '               onclick="Fieldset.toggleCollapse(\'' . $element->getHtmlId() . '\', \'' . $this->getUrl('*/*/state') . '\'); return false;">' .
            '          <span class="state-closed">' . $this->__('Configure') . '</span>' .
            '          <span class="state-opened">' . $this->__('Close') . '</span>' .
            '        </button>' .
            '   </div>' .
            '</div>';

        return $html;
    }

    /**
     * Collapsed or expanded fieldset when page loaded
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    protected function _getCollapseState($element)
    {
        return false;
    }

}
