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
class MaxiPago_Payment_Block_Adminhtml_Seller_Edit_Tabs
    extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('filter_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('maxipago')->__('Informações Gerais'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label' => Mage::helper('maxipago')->__('Personalizar Conteúdo'),
            'title' => Mage::helper('maxipago')->__('Personalizar Conteúdo'),
            'content' => $this->getLayout()->createBlock('maxipago/adminhtml_seller_edit_tab_form')->toHtml(),
        ));
        $this->addTab('products', array(
            'label' => Mage::helper('maxipago')->__('Products'),
            'title' => Mage::helper('maxipago')->__('Products'),
            'content' => $this->getLayout()->createBlock('maxipago/adminhtml_seller_edit_tab_products')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }

}
