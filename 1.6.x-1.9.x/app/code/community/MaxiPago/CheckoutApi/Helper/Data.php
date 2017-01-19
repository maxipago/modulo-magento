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

class MaxiPago_CheckoutApi_Helper_Data extends Mage_Core_Helper_Data
{
    public function prepareLineItems(Mage_Core_Model_Abstract $salesEntity, $discountTotalAsItem = true, $shippingTotalAsItem = false)
    {
        $items = array();
        
        foreach ($salesEntity->getAllItems() as $item) {
            
            if (!$item->getParentItem()) {
                $items[] = new Varien_Object($this->_prepareLineItemFields($salesEntity, $item));
            }
        }
        
        $discountAmount = 0;
        
        $shippingDescription = '';
        
        if ($salesEntity instanceof Mage_Sales_Model_Order) {
            
            $discountAmount = abs(1 * $salesEntity->getBaseDiscountAmount());
            
            $shippingDescription = $salesEntity->getShippingDescription();
            
            $totals = array(
                'subtotal' => $salesEntity->getBaseSubtotal() - $discountAmount,
                'tax'      => $salesEntity->getBaseTaxAmount(),
                'shipping' => $salesEntity->getBaseShippingAmount(),
                'discount' => $discountAmount
            );
        } else {
            
            $address = $salesEntity->getIsVirtual() ? $salesEntity->getBillingAddress() : $salesEntity->getShippingAddress();
            
            $discountAmount = abs(1 * $address->getBaseDiscountAmount());
            
            $shippingDescription = $address->getShippingDescription();
            
            $totals = array (
                'subtotal' => $salesEntity->getBaseSubtotal() - $discountAmount,
                'tax'      => $address->getBaseTaxAmount(),
                'shipping' => $address->getBaseShippingAmount(),
                'discount' => $discountAmount
            );
        }

        // discount total as line item (negative)
        if ($discountTotalAsItem && $discountAmount) {
            $items[] = new Varien_Object(array(
                'name'   => Mage::helper('checkoutapi')->__('Discount'),
                'qty'    => 1,
                'amount' => -1.00 * $discountAmount,
            ));
        }
        
        // shipping total as line item
        if ($shippingTotalAsItem && (!$salesEntity->getIsVirtual()) && (float)$totals['shipping']) {
            $items[] = new Varien_Object(array(
                'id'     => Mage::helper('checkoutapi')->__('Shipping'),
                'name'   => $shippingDescription,
                'qty'    => 1,
                'amount' => (float) $totals['shipping'],
            ));
        }

        $hiddenTax = (float) $salesEntity->getBaseHiddenTaxAmount();
        
        if ($hiddenTax) {
            $items[] = new Varien_Object(array(
                'name'   => Mage::helper('checkoutapi')->__('Discount Tax'),
                'qty'    => 1,
                'amount' => (float)$hiddenTax,
            ));
        }

        return array($items, $totals, $discountAmount, $totals['shipping']);
    }

    /**
     * Get one line item key-value array
     *
     * @param Mage_Core_Model_Abstract $salesEntity
     * @param Varien_Object $item
     * @return array
     */
    protected function _prepareLineItemFields(Mage_Core_Model_Abstract $salesEntity, Varien_Object $item)
    {
        if ($salesEntity instanceof Mage_Sales_Model_Order) {
            $qty = $item->getQtyOrdered();
            $amount = $item->getBasePrice();
            // TODO: nominal item for order
        } else {
            $qty = $item->getTotalQty();
            $amount = $item->isNominal() ? 0 : $item->getBaseCalculationPrice();
        }
        
        // workaround in case if item subtotal precision is not compatible with PayPal (.2)
        $subAggregatedLabel = '';
        
        if ((float) $amount - round((float) $amount, 2)) {
            $amount = $amount * $qty;
            $subAggregatedLabel = ' x' . $qty;
            $qty = 1;
        }
        
        return array(
            'id'     => $item->getSku(),
            'name'   => $item->getName() . $subAggregatedLabel,
            'qty'    => $qty,
            'amount' => (float)$amount,
        );
    }
    
    /**
     * Get the config value
     * @param Varien_Object $payment
     * @param string $config
     * @param string $storeId
     * @return string
     */
    public function getPaymentConfig(Varien_Object $payment, $config, $storeId = null) {
    	
    	return $payment->getMethodInstance()->getConfigData($config, $storeId);
    }
    
    /**
     * Get the global config value
     * @param string $config
     * @param string $storeId
     * @return string
     */
    public function getGlobalConfig($config, $storeId = null) {
    	if ($storeId === null) {
	    	$storeId = Mage::app()->getStore()->getStoreId();
    	}
    	return Mage::getStoreConfig('payment/maxipagocheckoutapi_global_config/'.$config, $storeId);
    }
    
    public function enviarNotificacaoMudancaStatus($order, $mensagem) {
    	 
    	$emailStatus = explode(',', $this->getPaymentConfig($order->getPayment(), 'emailStatus'));
    	$sendEmail = in_array($order->getStatus(), $emailStatus) ? true :  false;
    	$order->addStatusToHistory(
    			$order->getStatus(),
    			$this->__($mensagem),
    			$sendEmail
    		);
//     	if($sendEmail) { $order->sendOrderUpdateEmail($sendEmail, ''); }
    }
    
    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return null|string
     */
    public function getDocument(Mage_Sales_Model_Order $order)
    {
    	$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
    	$address = $order->getIsVirtual() ? $order->getBillingAddress() : $order->getShippingAddress();
    	$taxvat = $customer->getData('taxvat');
    
    	if (empty($taxvat)) {
    		$taxvat = $address->getData('taxvat');
    	}
    
    	if (empty($taxvat)) {
    		$taxvat = $order->getCustomerTaxvat();
    	}
    
    	$taxvat = preg_replace('/[^0-9]/', '', $taxvat);
    
    	return $taxvat;
    }
    
    /**
     * @param float $value
     * @return string
     */
    public function toCurrencyString($value) {
    	$simbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
    	return $simbol . ' ' . number_format($value, 2, ',', '');
    }
    
    /**
     * @return Mage_Sales_Model_Order_Invoice|false
     */
    public function getCapturedInvoice($order)
    {
    	if ($order->hasInvoices()) {
    
    		$invoiceCollection = $order->getInvoiceCollection();
    
    		$transactions = Mage::getModel('sales/order_payment_transaction')->getCollection()
    		->addAttributeToFilter('order_id', array('eq' => $order->getEntityId()))
    		->addAttributeToFilter('txn_type', array('eq' => 'capture'))
    		;
    
    		$txnCaptureId = null;
    		foreach ($transactions as $txn) {
    			$txnCaptureId = $txn->getTxnId();
    			break;
    		}
    
    		foreach ($invoiceCollection as $invoice) {
    			if ($order->getGrandTotal() == $invoice->getGrandTotal()
    				|| $invoice->getTransactionId() == $txnCaptureId) {
    					return $invoice;
    				}
    		}
    	}
    	 
    	return false;
    }
    
    public function getFraudCheck($order)
    {
    	$processorType = $this->getPaymentConfig($order->getPayment(), 'processorType');
    	$fraudCheck = $this->getPaymentConfig($order->getPayment(), 'fraudCheck') ? 'Y' : 'N';
    	return $processorType != 'sale' ? $fraudCheck : 'N';
    }
    
    /**
     * Log the message
     * @param string $message
     * @param string $file
     */
    public function log($message, $file = '')
    {
    	if ($this->getGlobalConfig('log_active') == '1') {
    		MaxiPago_CheckoutApi_Model_Logger::log($message, Zend_Log::INFO, $file);
    	}
    }
}