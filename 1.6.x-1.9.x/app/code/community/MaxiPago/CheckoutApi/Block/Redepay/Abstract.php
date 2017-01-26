<?php

abstract class MaxiPago_CheckoutApi_Block_Redepay_Abstract extends Mage_Core_Block_Template
{
    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->hasData('order')) {
            $order = Mage::getModel('sales/order')->load($this->getOrderId());
            $this->setData('order', $order);
        }

        return $this->getData('order');
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->_getLastOrderId();
    }

    /**
     * @return string
     */
    public function getRealOrderId()
    {
        return $this->getOrder()->getRealOrderId();
    }

    /**
     * @return Core_Session
     */
    public function getSession()
    {
        return $this->_getSession();
    }

    /**
     * @return string
     */
    public function getPrintOrderUrl()
    {
        return $this->getUrl('sales/order/print', array('order_id'=> $this->getOrderId()));
    }

    /**
     * @return bool
     */
    public function canReorder()
    {
        if ($this->getOrder()->getCustomerIsGuest()) {
            return true;
        }

        return $this->getOrder()->canReorderIgnoreSalable();
    }

    /**
     * @return string
     */
    public function getReorderUrl()
    {
        return $this->getUrl('*/*/reorder', array('order_id' => $this->getOrder()->getId()));
    }
    
    /**
     * @return string
     */
    public function getAuthenticationUrl()
    {
    	if ($this->_getSession()->getResponseMp())
    	{
    		$responseMP = simplexml_load_string($this->_getSession()->getResponseMp());
    		if (property_exists($responseMP, 'authenticationURL'))
    			return $responseMP->authenticationURL;
    	}
    		
    	return null;
    }

    /**
     * @return Core_Session
     */
    protected function _getSession()
    {
    	return Mage::getSingleton('core/session');
    }
    
    /**
     * @return int
     */
    protected function _getLastOrderId()
    {
    	$lastOrderId = Mage::getSingleton('checkout/session')->getLastOrderId();
    	if (empty($lastOrderId)) {
    		$lastOrderId = Mage::getSingleton('adminhtml/session')->getLastOrderId();
    	}
    
    	return (int) $lastOrderId;
    }
}
