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
 *
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class MaxiPago_Payment_Block_Adminhtml_Catalog_Product_Tab_Seller
    extends Mage_Adminhtml_Block_Catalog_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected $_fieldName = null;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('maxipago/catalog/product/tab/seller.phtml');
    }

    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('product');
    }

    /**
     * Check block is readonly
     *
     * @return boolean
     */
    public function isReadonly()
    {
        return $this->getProduct()->getOptionsReadonly();
    }

    public function getTabLabel()
    {
        return $this->__('MaxiPago Seller');
    }

    public function getTabTitle()
    {
        return $this->__('MaxiPago Seller');
    }

    /**
     * @return MaxiPago_Payment_Model_Resource_Seller_Collection
     */
    public function getSellers()
    {
        /** @var MaxiPago_Payment_Model_Resource_Seller_Collection $collection */
        $collection = Mage::getModel('maxipago/seller')->getCollection();
        return $collection;
    }

    public function canShowTab()
    {
        if (Mage::getStoreConfig('payment/maxipago_settings/enabled_split')) {
            return true;
        } else {
            return false;
        }
    }

    public function isHidden()
    {
        return false;
    }
}