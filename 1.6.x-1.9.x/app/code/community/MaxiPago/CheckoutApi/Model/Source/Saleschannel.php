<?php

class MaxiPago_CheckoutApi_Model_Source_Saleschannel
{
	public function toOptionArray()
	{
                //Necessario enviar Maisculo
		return array(
				array('value' => 'DEFAULT', 'label' => Mage::helper('adminhtml')->__('E-commerce')),
				array('value' => 'PHONE', 'label' => Mage::helper('adminhtml')->__('Call-center')),
				array('value' => 'OUTROS', 'label' => Mage::helper('adminhtml')->__('Outros')),
		);
	}
}