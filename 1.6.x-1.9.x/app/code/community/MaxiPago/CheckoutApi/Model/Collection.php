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
 * to atendimento@saffira.com.br so we can send you a copy immediately.
 *
 * @category   Saffira / maxiPago
 * @package    MaxiPago_CheckoutApi
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MaxiPago_CheckoutApi_Model_Collection extends Mage_Sales_Model_Mysql4_Order_Payment_Collection
{
    protected function _construct()
    {
        $this->_init('checkoutapi/payment');
    }

    public function addSavedFilter($customerId)
    {
        $ccTypes = Mage::getSingleton('checkoutapi/source_cctype')->getValueArray();

        $this->join('sales/order', '`main_table`.`parent_id`=`sales/order`.`entity_id`', '');
        $this->addAttributeToFilter('customer_id', $customerId)
            ->addAttributeToFilter('cc_number_enc', array('neq' => ''))
            ->addAttributeToFilter('cc_type', array('in' => $ccTypes))
            ->addAttributeToFilter('maxipago_cc_token', array('neq' => ''))
            ->getSelect()
        	->group(array('cc_exp_year', 'cc_exp_month', 'cc_owner', 'cc_last4', 'cc_type', 'cc_number_enc'));

        return $this->getData();
    }
}