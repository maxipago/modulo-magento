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
 * @author        Thiago Contardi <thiago@contardi.com.br>
 *
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class MaxiPago_Payment_Block_Adminhtml_Catalog_Product_Tab_Seller
    extends Mage_Adminhtml_Block_Catalog_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected $_fieldName = null;
    protected $_sellers = null;

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
     * @return int
     */
    public function hasSeller()
    {
        if ($this->getSellers()) {
            return $this->getSellers()->count();
        }

        return 0;
    }

    /**
     * @return MaxiPago_Payment_Model_Resource_Seller_Collection
     */
    public function getSellers()
    {
        if (!$this->_sellers) {
            /** @var MaxiPago_Payment_Model_Resource_Seller_Collection $collection */
            $this->_sellers = Mage::getModel('maxipago/seller')->getCollection();
        }
        return $this->_sellers;
    }

    public function canShowTab()
    {
        if ($this->_getHelper()->getConfig('enabled_split')) {
            return true;
        } else {
            return false;
        }
    }

    public function isHidden()
    {
        return false;
    }


    /**
     * @return MaxiPago_Payment_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function _getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('maxipago');
        }

        return $this->_helper;
    }
}