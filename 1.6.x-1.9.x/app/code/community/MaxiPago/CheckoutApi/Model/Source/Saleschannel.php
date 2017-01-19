<?php

class MaxiPago_CheckoutApi_Model_Source_Saleschannel
{
	public function toOptionArray()
	{
		return array(
				array('value' => 'Web', 'label' => Mage::helper('adminhtml')->__('E-commerce')),
				array('value' => 'Phone', 'label' => Mage::helper('adminhtml')->__('Call-center')),
				array('value' => 'Outros', 'label' => Mage::helper('adminhtml')->__('Outros')),
		);
	}
}