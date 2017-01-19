<?php

class MaxiPago_CheckoutApi_Block_Form_Fraud extends Mage_Core_Block_Template
{
	protected function _construct()
	{
		$this->setTemplate('maxipago/checkoutapi/form/fraud.phtml');
		parent::_construct();
	}
	
	public function getConfig($config, $storeId = null)
	{
		if ($storeId === null) {
			$storeId = Mage::app()->getStore()->getStoreId();
		}
		return Mage::getStoreConfig('payment/maxipagocheckoutapi_creditcard/'.$config, $storeId);
	}
}