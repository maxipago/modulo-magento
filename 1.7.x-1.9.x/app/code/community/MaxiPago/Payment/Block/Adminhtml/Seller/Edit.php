<?php

/**
 * Bizcommerce Desenvolvimento de Plataformas Digitais Ltda - Epp
 *
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
 *
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class MaxiPago_Payment_Block_Adminhtml_Seller_Edit
    extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'maxipago';
        $this->_controller = 'adminhtml_seller';

        $this->_updateButton('save', 'label', Mage::helper('maxipago')->__('Save'));
        $this->_updateButton('delete', 'label', Mage::helper('maxipago')->__('Delete'));

        $this->_removeButton('reset');

        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('catalog')->__('Save and Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
        ), -100);

    }

    public function getHeaderText()
    {
        if (Mage::registry('maxipago_data') && Mage::registry('maxipago_data')->getId()) {
            return Mage::helper('maxipago')->__("Edit Seller '%s'", $this->escapeHtml(Mage::registry('maxipago_data')->getName()));
        } else {
            return Mage::helper('maxipago')->__('Add Seller');
        }
    }

}
