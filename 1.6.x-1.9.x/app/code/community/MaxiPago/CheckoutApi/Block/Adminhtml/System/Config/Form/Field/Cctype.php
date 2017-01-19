<?php

class MaxiPago_CheckoutApi_Block_Adminhtml_System_Config_Form_Field_Cctype
	extends Mage_Core_Block_Html_Select
{
	public function _toHtml()
	{
		$options = Mage::getSingleton('checkoutapi/source_cctype')->toOptionArray();
		foreach ($options as $option) {
			$this->addOption($option['value'], $option['label']);
		}

		return parent::_toHtml();
	}

	public function setInputName($value)
	{
		return $this->setName($value);
	}
}