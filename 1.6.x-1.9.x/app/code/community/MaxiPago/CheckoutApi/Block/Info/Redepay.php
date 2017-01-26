<?php

class MaxiPago_CheckoutApi_Block_Info_Redepay extends Mage_Payment_Block_Info
{
	protected function _construct()
	{
		$this->setTemplate('maxipago/checkoutapi/info/redepay.phtml');
		parent::_construct();
	}
}