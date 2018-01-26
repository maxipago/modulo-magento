<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contato@maxipago.com.br so we can send you a copy immediately.
 *
 * @category   maxiPago!
 * @package    MaxiPago_Payment
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MaxiPago_Payment_Block_Adminhtml_System_Config_Form_Field_Interest
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_addRowButtonHtml = array();
    protected $_removeRowButtonHtml = array();


    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $html = '<div id="installment_rate_template" style="display:none">';
        $html .=    $this->_getRowTemplateHtml();
        $html .= '</div>';
        $html .= '<ul id="installment_rate_container">';
        $html .= '<li>';
        $html .= '  <div style="width:100px;float:left">';
        $html .=    $this->__('Installments');
        $html .= '</div>';
        $html .= '<div style="width:200px;float:left;margin-left:5px">';
        $html .=    $this->__('Interest Rate');
        $html .= '</div>';
        $html .= '</li>';
        if ($this->_getValue('number')) {
            foreach ($this->_getValue('number') as $i => $f) {
                if ($i) {
                    $html .= $this->_getRowTemplateHtml($i);
                }
            }
        }
        $html .= '</ul>';
        $html .= $this->_getAddRowButtonHtml('installment_rate_container', 'installment_rate_template', $this->__('Add'));
        $html .= '<script>$$(\'#installment_rate_container .maxipago_interest_rate_per_installments\').each(function(el, i){$(el).value = i + 1;});</script>';
        return $html;
    }

    protected function _getRowTemplateHtml($i = 0)
    {
        $html = '<li>';
        $html .= '<div style="width:30px;float:left">';
        $html .= '  <input type="text" name="' . $this->getElement()->getName() . '[number][]" readonly class="maxipago_interest_rate_per_installments" value="">';
        $html .= '</div>';
        $html .= '<div style="width:200px;float:left;margin-left:5px">';
        $html .= '  <input class="input-text" type="text" class="required-entry" name="' . $this->getElement()->getName() . '[value][]" value="' . $this->_getValue('value/' . $i) . '" ' . $this->_getDisabled() . ' />';
        $html .= '</div>';
        $html .= '<div style="float:left;margin-left:5px">' . $this->_getRemoveRowButtonHtml() . '</div>';
        $html .= '</li>';

        return $html;
    }

    protected function _getDisabled()
    {
        return $this->getElement()->getDisabled() ? ' disabled' : '';
    }

    protected function _getValue($key)
    {
        return $this->getElement()->getData('value/' . $key);
    }

    protected function _getSelected($key, $value)
    {
        return $this->getElement()->getData('value/' . $key) == $value ? 'selected="selected"' : '';
    }

    protected function _getAddRowButtonHtml($container, $template, $title = 'Add')
    {
        if (!isset($this->_addRowButtonHtml[$container])) {
            $onclick = "Element.insert(\$('" . $container . "'), {bottom: \$('" . $template . "').innerHTML});\$\$('#installment_rate_container .maxipago_interest_rate_per_installments').each(function(el, i){\$(el).value = i + 1;});";
            $this->_addRowButtonHtml[$container] = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setType('button')
                ->setClass('add ' . $this->_getDisabled())
                ->setLabel($this->__($title))
                ->setOnClick($onclick)
                ->setDisabled($this->_getDisabled())
                ->toHtml();
        }
        return $this->_addRowButtonHtml[$container];
    }

    protected function _getRemoveRowButtonHtml($selector = 'li', $title = 'Remove')
    {
        if (!$this->_removeRowButtonHtml) {
            $onclick = "Element.remove($(this).up('" . $selector . "')); \$\$('#installment_rate_container .maxipago_interest_rate_per_installments').each(function(el, i){\$(el).value = i + 1;});";
            $this->_removeRowButtonHtml = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setType('button')
                ->setClass('delete v-middle ' . $this->_getDisabled())
                ->setLabel($this->__($title))
                ->setOnClick($onclick)
                ->setDisabled($this->_getDisabled())
                ->toHtml();
        }
        return $this->_removeRowButtonHtml;
    }

}