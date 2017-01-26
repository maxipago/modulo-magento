<?php

class MaxiPago_CheckoutApi_Block_Form_Redepay extends Mage_Payment_Block_Form
{
	protected function _construct()
	{
		$this->setTemplate('maxipago/checkoutapi/form/redepay.phtml');
		parent::_construct();
	}
}