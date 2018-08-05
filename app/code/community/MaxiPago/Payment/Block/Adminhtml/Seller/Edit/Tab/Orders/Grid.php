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
class MaxiPago_Payment_Block_Adminhtml_Seller_Edit_Tab_Orders_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    public function _construct()
    {
        parent::_construct();
        $this->setId('orders_grid');
        $this->setDefaultSort('increment_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setCountTotals(true);
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
        /** @var Mage_Sales_Model_Resource_Order_Item_Collection $collection */
        $collection = Mage::getModel('sales/order_item')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('maxipago_seller_id', $this->getSeller('seller_id'))
        ;

        $collection->getSelect()->join(
            array('order' => $collection->getTable('sales/order')),
            'order.entity_id = main_table.order_id',
            'increment_id'
        )->where('parent_item_id IS NULL');

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    public function getTotals()
    {
        $totals = new Varien_Object();

        $price = 0;
        foreach ($this->getCollection() as $item) {
            $price += $item->getData('price');
        }

        //First column in the grid
        $fields['increment_id'] =  Mage::helper('maxipago')->__('Total');
        $fields['price'] = Mage::helper('core')->currency($price, true, false);
        $totals->setData($fields);

        return $totals;
    }

    protected function _prepareColumns()
    {

        $this->addColumn('increment_id', array(
            'header' => Mage::helper('maxipago')->__('Order'),
            'align'  => 'left',
            'index'  => 'increment_id',
        ));

        $this->addColumn('order_item_name', array(
            'header' => Mage::helper('maxipago')->__('Name'),
            'align'  => 'center',
            'index'  => 'name',
        ));

        $this->addColumn('order_item_name', array(
            'header' => Mage::helper('maxipago')->__('Name'),
            'align'  => 'center',
            'index'  => 'name',
        ));

        $this->addColumn('order_item_price', array(
            'header' => Mage::helper('maxipago')->__('Price'),
            'index'  => 'price',
            'type'  => 'currency',
            'currency' => Mage::app()->getStore()->getBaseCurrencyCode()
        ));

        $this->addColumn('order_item_sku', array(
            'header' => Mage::helper('maxipago')->__('SKU'),
            'align'  => 'center',
            'index'  => 'sku',
        ));

        $this->addColumn('order_item_seller_mdr', array(
            'header' => Mage::helper('maxipago')->__('MDR'),
            'align'  => 'center',
            'index'  => 'maxipago_seller_mdr',
        ));

        $this->addColumn('order_item_seller_installments', array(
            'header' => Mage::helper('maxipago')->__('Installments'),
            'align'  => 'center',
            'index'  => 'maxipago_seller_installments',
        ));

        return parent::_prepareColumns();
    }

    /**
     * Return row url for js event handlers
     *
     * @param Mage_Sales_Model_Order_Item|Varien_Object
     * @return string
     */
    public function getRowUrl($item)
    {
        return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $item->getOrderId()));
    }
}