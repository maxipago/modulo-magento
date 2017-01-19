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

class MaxiPago_CheckoutApi_Block_Info_Bankslip extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('maxipago/checkoutapi/info/standard.phtml');
    }
        
    public function getLinkPayment($order) 
    {
        if ($this->getRequest()->getRouteName() != 'checkout') {
            $_order = $order;
            $incrementid = $_order->getData('increment_id');
            $quoteid = $_order->getData('quote_id');
            $linkBoleto = $_order->getPayment()->getData('maxipago_url_payment');
		
            $hash = Mage::getModel('core/encryption')->encrypt($incrementid . ":" . $quoteid);
			$method = $_order->getPayment()->getMethod();
            switch ($method) {
            	case 'maxipagocheckoutapi_bankslip':
                        return '<span><a class="button btn-cart" href="' . $linkBoleto . '" target="_blank">Imprimir Boleto</a></span>';
                    break;
            }
        }
    }
    
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $info = $this->getInfo();
        $transport = new Varien_Object();
        $transport = parent::_prepareSpecificInformation($transport);
        $transport->addData(array(
            Mage::helper('payment')->__('Payment Method ID') => $info->getMaxiPagocheckoutTransactionId(),
            Mage::helper('payment')->__('Split Number') => $info->getMaxiPagocheckoutSplitNumber(),
            Mage::helper('payment')->__('Split Value') => $info->getMaxiPagocheckoutSplitValue()
        ));
        return $transport;
    }

}