<?php

class MaxiPago_CheckoutApi_Block_Redepay_Pending extends MaxiPago_CheckoutApi_Block_Redepay_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('maxipago/checkoutapi/redepay/pending.phtml');
    }
}
