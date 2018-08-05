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
class MaxiPago_Payment_Block_Adminhtml_Seller_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('maxipagoGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('maxipago/seller')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('maxipago')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'entity_id',
        ));

        $this->addColumn('seller_id', array(
            'header' => Mage::helper('maxipago')->__('Seller ID'),
            'align' => 'left',
            'index' => 'seller_id',
        ));

        $this->addColumn('name', array(
            'header' => Mage::helper('maxipago')->__('Name'),
            'align' => 'left',
            'index' => 'name',
        ));

        $this->addColumn('seller_mdr', array(
            'header' => Mage::helper('maxipago')->__('MDR'),
            'align' => 'left',
            'index' => 'seller_mdr',
        ));

        $this->addColumn('days_to_pay', array(
            'header' => Mage::helper('maxipago')->__('Days To Pay'),
            'align' => 'left',
            'index' => 'days_to_pay',
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('maxipago')->__('Status'),
            'align' => 'left',
            'index' => 'status',
            "type" => "options",
            "options" => Mage::getModel('maxipago/source_split_status')->toArray()
        ));

        $this->addColumn('action', array(
            'header' => Mage::helper('adminhtml')->__('Action'),
            'width' => '100',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('adminhtml')->__('Edit'),
                    'url' => array('base' => '*/*/edit'),
                    'field' => 'id'
                ),
            ),
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
            'is_system' => true,
        ));

        $this->addColumn('delete',
            array(
                'header' => Mage::helper('maxipago')->__('Excluir'),
                'width' => '40px',
                'index' => 'delete',
                'renderer' => 'maxipago/adminhtml_seller_grid_renderer_action',
                'filter' => false,
                'sortable' => false,
            ));


        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('sellers');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('catalog')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('maxipago')->__('Are you sure?')
        ));
        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}
