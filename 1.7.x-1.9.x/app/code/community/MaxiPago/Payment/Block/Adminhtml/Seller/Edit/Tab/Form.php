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
class MaxiPago_Payment_Block_Adminhtml_Seller_Edit_Tab_Form
    extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('maxipago_form', array('legend' => Mage::helper('maxipago')->__('Seller Data')));

        $fieldset->addField('seller_id', 'text', array(
            'label' => Mage::helper('maxipago')->__('Seller ID'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'seller_id',
        ));

        $fieldset->addField('name', 'text', array(
                'label' => Mage::helper('maxipago')->__('Name'),
                'class' => 'required-entry',
                'required' => true,
                'name' => 'name',
            )
        );

        $fieldset->addField('mdr', 'text', array(
            'label' => Mage::helper('maxipago')->__('MDR'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'meta_title',
        ));

        $fieldset->addField('days_to_pay', 'text', array(
            'label' => Mage::helper('maxipago')->__('Days To Pay'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'meta_description',
        ));

        if (Mage::getSingleton('adminhtml/session')->getSellersData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getSellersData());
            Mage::getSingleton('adminhtml/session')->setSellersData(null);
        } elseif (Mage::registry('sellers_data')) {
            $form->setValues(Mage::registry('sellers_data')->getData());
            $this->setForm($form);
        }
        return parent::_prepareForm();
    }

}