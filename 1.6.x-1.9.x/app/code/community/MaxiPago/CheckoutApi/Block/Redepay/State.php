<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Checkout_State
 */
class Rede_Pay_Block_Checkout_State extends Rede_Pay_Block_Checkout_Abstract
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/pay/checkout/state.phtml');
    }

}
