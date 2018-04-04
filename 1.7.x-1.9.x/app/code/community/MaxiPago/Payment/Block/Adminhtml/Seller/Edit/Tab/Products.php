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
class MaxiPago_Payment_Block_Adminhtml_Seller_Edit_Tab_Products
    extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('maxipago_form', array('legend' => Mage::helper('maxipago')->__('Conteúdo Página Filtro')));

        $fieldset->addField('url', 'text', array(
                'label' => Mage::helper('maxipago')->__('URL'),
                'required' => true,
                'name' => 'url',
            )
        );

        $fieldset->addField('meta_title', 'text', array(
            'label' => Mage::helper('maxipago')->__('Meta Title'),
            //'class' => 'required-entry',
            //'required' => true,
            'name' => 'meta_title',
        ));

        $fieldset->addField('meta_description', 'text', array(
            'label' => Mage::helper('maxipago')->__('Meta Description'),
            //'class' => 'required-entry',
            //'required' => true,
            'name' => 'meta_description',
        ));

        $fieldset->addField('meta_robots', 'select', array(
            'label' => Mage::helper('maxipago')->__('Meta Robots'),
            //'class' => 'required-entry',
            //'required' => true,
            'name' => 'meta_robots',
            'values' => array(
                array(
                    'value' => 'index,follow',
                    'label' => Mage::helper('cms')->__('index,follow'),
                ),
                array(
                    'value' => 'noindex,follow',
                    'label' => Mage::helper('cms')->__('noindex,follow'),
                ),
                array(
                    'value' => 'noindex,nofollow',
                    'label' => Mage::helper('cms')->__('noindex,nofollow'),
                ),
                array(
                    'value' => 'index,nofollow',
                    'label' => Mage::helper('cms')->__('index,nofollow'),
                ),
            ),

        ));

        $fieldset->addField('canonical_tag', 'text', array(
            'label' => Mage::helper('maxipago')->__('Canonical Tag'),
            //'class' => 'required-entry',
            //'required' => true,
            'name' => 'canonical_tag',
        ));

        $fieldset->addField('h1', 'text', array(
            'label' => Mage::helper('maxipago')->__('H1'),
            //'class' => 'required-entry',
            //'required' => true,
            'name' => 'h1',
        ));

        if (Mage::getSingleton('adminhtml/session')->getCustomcontentData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getCustomcontentData());
            Mage::getSingleton('adminhtml/session')->setCustomcontentData(null);
        } elseif (Mage::registry('maxipago_data')) {
            $form->setValues(Mage::registry('maxipago_data')->getData());
            $this->setForm($form);
        }
        return parent::_prepareForm();
    }

}