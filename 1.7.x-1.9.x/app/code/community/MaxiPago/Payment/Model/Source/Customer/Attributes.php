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
class MaxiPago_Payment_Model_Source_Customer_Attributes
{
    protected $_fontendTypes = array('text', 'textarea', 'select');
    /**
     * Retrieves attributes
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter(Mage::getModel('customer/customer')->getEntityTypeId());

        $result = array();
        foreach ($attributes as $attribute) {
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            if ($attribute->getId() && in_array($attribute->getFrontendInput(), $this->_fontendTypes)) {
                $result[] = array(
                    'value' => $attribute->getAttributeCode(),
                    'label' => Mage::helper('adminhtml')->__($attribute->getFrontend()->getLabel()) . ' (' . $attribute->getAttributeCode() . ')',
                );
            }
        }
        return $result;
    }

}