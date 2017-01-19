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
 
class MaxiPago_CheckoutApi_Model_Tef extends MaxiPago_CheckoutApi_Model_Standard
{
    protected $_code  = 'maxipagocheckoutapi_tef';
	
    protected $_formBlockType = 'checkoutapi/form_tef';
    protected $_blockType = 'checkoutapi/tef';
    protected $_infoBlockType = 'checkoutapi/info_tef';
    
    protected $_standardType = 'tef';
    
    /**
     * Availability options
     */
    protected $_isGateway                   = false;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = false;
    protected $_canCapture                  = false;
    protected $_canCapturePartial           = false;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = false;
    protected $_canVoid                     = false;
    protected $_canUseInternal              = false;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = false;
    protected $_canFetchTransactionInfo     = true;
    protected $_canCreateBillingAgreement   = true;
    protected $_canReviewPayment            = true;
    
    public function getOrderPlaceRedirectUrl(){
        return Mage::getUrl('checkoutapi/standard/payment', array('_secure' => true, 'type' => 'tef'));
    }
}