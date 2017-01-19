<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to atendimento@saffira.com.br so we can send you a copy immediately.
 *
 * @category   Saffira / maxiPago
 * @package    MaxiPago_CheckoutApi
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MaxiPago_CheckoutApi_Block_Form_Bankslip extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('maxipago/checkoutapi/form/bankslip.phtml');
        parent::_construct();
    }
    
    public function getBankSlip() {
    	
    	return Mage::getStoreConfig('payment/'.$this->getMethodCode().'/bankSlip',Mage::app()->getStore());
    }
}
