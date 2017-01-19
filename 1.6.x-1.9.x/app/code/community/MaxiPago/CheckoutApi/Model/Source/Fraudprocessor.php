<?php

class MaxiPago_CheckoutApi_Model_Source_Fraudprocessor
{
	public function toOptionArray()
	{
		return array(
			array('value' => 99, 'label' => Mage::helper('adminhtml')->__('Kount')),
			array('value' => 97, 'label' => Mage::helper('adminhtml')->__('ClearSale Total')),
		);
	}
}