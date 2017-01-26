<?php
/**
 * Created by PhpStorm.
 * User: claudio
 * Date: 29/03/15
 * Time: 20:58
 */

class MaxiPago_CheckoutApi_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View {
	
    public function  __construct() {

        parent::__construct();

        $order = $this->getOrder();

        if($order->getPayment()->getMethodInstance()->getCode() == 'maxipagocheckoutapi_creditcard') {
        	
            if($order->getStatus() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            		
                $message = 'Tem certeza que deseja uma captura do valor total do pedido?';
                $this->_addButton('order_mpCaptura', array(
                    'label'     => 'Capturar',
                    'onclick'   => "confirmSetLocation('{$message}', '{$this->getCaptureUrl()}')",
                    'class'     => ''
                ), 0, 1);
                
                $message = 'Você será redirecionado para a tela de criação de fatura. Deseja continuar?';
                $this->_addButton('order_mpCapturaParcial', array(
                	'label'     => 'Capturar Parc.',
                	'onclick'   => "confirmSetLocation('{$message}', '{$this->getCaptureParcialUrl()}')",
                	'class'     => ''
                ), 0, 2);
            }
            
            $invoice = Mage::helper('checkoutapi')->getCapturedInvoice($this->getOrder());
            if($order->getStatus() == Mage_Sales_Model_Order::STATE_PROCESSING) {
            
            	if (!$invoice) {
            		
            		$message = 'Tem certeza que deseja estornar esta transação?';
            		$this->_addButton('order_mpEstorno', array(
            				'label'     => 'Estornar',
            				'onclick'   => "confirmSetLocation('{$message}', '{$this->getReversalUrl()}')",
            				'class'     => ''
            		), 0, 3);
            	}
            	else {
            	
            		$message = 'Para realizar o estorno é necessário criar um reembolso para a fatura. Deseja continuar?';
            		$this->_addButton('order_mpEstorno', array(
            			'label'     => 'Estornar',
            			'onclick'   => "confirmSetLocation('{$message}', '{$this->getReembolsoUrl($invoice->getId())}')",
            			'class'     => ''
            		), 0, 3);
            	}
            }
            elseif ($order->getStatus() == Mage_Sales_Model_Order::STATE_COMPLETE && $this->getIsRecurring()) {
            	if ($invoice && $this->getIsRecurring())
            	{
            		$message = 'Para realizar o estorno é necessário criar um reembolso para a fatura. Deseja continuar?';
            		$this->_addButton('order_mpEstorno', array(
            				'label'     => 'Estornar',
            				'onclick'   => "confirmSetLocation('{$message}', '{$this->getReembolsoUrl($invoice->getId())}')",
            				'class'     => ''
            		), 0, 3);
            	}
            	else
            	{
	            	$message = 'Tem certeza que deseja estornar esta transação?';
	            	$this->_addButton('order_mpEstorno', array(
	            			'label'     => 'Estornar',
	            			'onclick'   => "confirmSetLocation('{$message}', '{$this->getReversalUrl()}')",
	            			'class'     => ''
	            	), 0, 3);
            	}
            }
        }
        elseif ($order->getPayment()->getMethodInstance()->getCode() == 'maxipagocheckoutapi_redepay') {
        	$this->_addButton('order_mpConsultar', array(
        			'label'     => 'Consultar',
        			'onclick'   => "setLocation('{$this->getRedepayUpdateUrl($order->getId())}')",
        			'class'     => ''
        	), 0, 1);
        }
    }

    protected function getCaptureUrl()
    {
        return $this->getUrl('checkoutapi/standard/capture/');
    }

    protected function getReversalUrl()
    {
        return $this->getUrl('checkoutapi/standard/reversal/');
    }
    
    protected function getCaptureParcialUrl()
    {
    	return $this->getUrl('*/sales_order_invoice/start');
    }
    
    protected function getReembolsoUrl($invoiceId)
    {
    	return $this->getUrl('*/sales_order_creditmemo/new', array('invoice_id' => $invoiceId));
    }
    
    protected function getRedepayUpdateUrl($orderId)
    {
    	return $this->getUrl('checkoutapi/redepay/update', array('order_id' => $orderId));
    }
    
    protected function getIsRecurring()
    {
    	foreach ($this->getOrder()->getAllItems() as $item) {
    		if (!$item->getIsVirtual())
    			return false;
    	}
    	return true;
    }
}