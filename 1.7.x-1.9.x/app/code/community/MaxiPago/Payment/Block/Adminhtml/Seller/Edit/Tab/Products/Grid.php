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
 * @author        Thiago Contardi <thiago@contardi.com.br>
 *
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class MaxiPago_Payment_Block_Adminhtml_Seller_Edit_Tab_Products_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    public function _construct()
    {
        parent::_construct();
        $this->setId('product_grid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    protected function getSeller($field)
    {
        $seller = Mage::getModel('maxipago/seller')->load($this->getSellerId());
        if ($seller && $seller->getId()) {
            if ($field) {
                return $seller->getData($field);
            }
            return $seller;
        }
        return false;

    }

    protected function getSellerId()
    {
        return (int) $this->getRequest()->getParam('id', 0);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('maxipago_seller', $this->getSeller('seller_id'));

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('maxipago')->__('Product ID'),
            'align'  => 'center',
            'width'  => '50px',
            'index'  => 'entity_id',
        ));

        $this->addColumn('product_name', array(
            'header' => Mage::helper('maxipago')->__('Name'),
            'align'  => 'center',
            'width'  => '200px',
            'index'  => 'name',
        ));

        $this->addColumn('sku', array(
            'header' => Mage::helper('maxipago')->__('Product SKU'),
            'align'  => 'center',
            'width'  => '200px',
            'index'  => 'sku',
        ));

        return parent::_prepareColumns();
    }

}