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

class MaxiPago_CheckoutApi_Block_Form_Creditcard extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('maxipago/checkoutapi/form/creditcard.phtml');
        parent::_construct();
    }
    
    protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }
    
    public function getQuote()
    {
    	return Mage::getSingleton('checkout/session')->getQuote();
    }
    
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if (is_null($months)) {
            $months = $this->_getConfig()->getMonths();
            $months = array(0 => $this->__('Month')) + $months;
            $this->setData('cc_months', $months);
        }
        return $months;
    }
    
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if (is_null($years)) {
            $years = $this->_getConfig()->getYears();
            $years = array(0 => $this->__('Year')) + $years;
            $this->setData('cc_years', $years);
        }
        return $years;
    }
    
    public function getSplitSimulate($totalValue = '0')
    {
        $mpCcardPayment = $this->getMethod();
        $mPQtdSplit = intval($mpCcardPayment->getConfigData('mPQtdSplit'));
        $mPValueSplit = floatval($mpCcardPayment->getConfigData('mPValueSplit'));
        $splitmP = array ();

        for ($t = 1; $t <= $mPQtdSplit; $t++) {

        	$parcela = ceil(100 * $totalValue / $t) / 100;
        	
                //Deixar pagar em uma parcela
        	if ($parcela < $mPValueSplit) {
                    
                    $k = str_pad($t, 2, '0', STR_PAD_LEFT);
                    $v = $t . 'x de ' . Mage::helper('checkoutapi')->toCurrencyString($parcela);
                    $splitmP[$k] = $v;
                    break;
                    
                } else {
        	
                    $k = str_pad($t, 2, '0', STR_PAD_LEFT);
                    $v = $t . 'x de ' . Mage::helper('checkoutapi')->toCurrencyString($parcela);
                    $splitmP[$k] = $v;
                }
        }

        $this->setData('splitSimulate', $splitmP);
        
        return $splitmP;
    }

    public function getSavedCreditcards()
    {
        $session = Mage::getSingleton('customer/session');
        $creditcards = null;
        
        if ($session->isLoggedIn()){
        	$userLogged = $session->getCustomer();
            $creditcards = Mage::getModel('checkoutapi/collection')
            	->addSavedFilter($userLogged->getData('entity_id'));
        }

        return $creditcards;
    }

    public function hasRecurringProducts()
    {
        $products = $this->getQuote()->getAllItems();
        
        foreach ($products as $product) {
            if ($product->getData('is_recurring')) {
                return true;
            }
        }
        return false;
    }
}
