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

class MaxiPago_CheckoutApi_Model_Observer extends Varien_Event_Observer
{

    public function updateObOrdersMP($observer) {
    	
    	if (
    		!Mage::getStoreConfig('payment/maxipagocheckoutapi_creditcard/active')
    		&& !Mage::getStoreConfig('payment/maxipagocheckoutapi_bankslip/active')
    		&& !Mage::getStoreConfig('payment/maxipagocheckoutapi_tef/active')
    	) {
    		return $this;
    	}

        echo 'Executando Cron - updateObOrdersMP';
        Mage::helper('checkoutapi')->log('Executando Cron - updateObOrdersMP', 'maxipago.log');
        
        $responseMP = Mage::getSingleton('checkoutapi/api')->detailReport();
       
        if (!$responseMP || intval($responseMP->header->errorCode) != 0) {        	
            Mage::helper('checkoutapi')->log('Erro ao realizar a consulta: '.$responseMP->header->errorMsg, 'maxipago.log');
            return;
        }
        
        $setInfo = $responseMP->result->resultSetInfo;
        $pageToken = (string) $setInfo->pageToken;
        $numberOfPages = intval($setInfo->numberOfPages);
        $totalNumberOfRecords = intval($setInfo->totalNumberOfRecords);
                
        if($totalNumberOfRecords > 0) {
            Mage::helper('checkoutapi')->log('############ Página 1 ############', 'maxipago.log');
            
            for($i = 1 ; $i <= $numberOfPages; $i++) {
                
                Mage::helper('checkoutapi')->log("############ Página $i ############', 'maxipago.log");
                
                $responseMP = Mage::getSingleton('checkoutapi/api')->detailReport(null, $pageToken, $i);               
                $records = $responseMP->result->records->record;
                
                foreach($records as $record) {
                    
                    Mage::helper('checkoutapi')->log($record->referenceNumber, 'maxipago.log');
                    if (intval($record->recurringPaymentFlag) == 1) {
                        $this->updateRecurringPayment($record);
                    }
                    else {
                        $this->updateOrderMaxiPagoCheckout($record, 'creditcard');
                    }
            	}
            }
        }
    }

    protected function updateOrderMaxiPagoCheckout($record, $code) {
        
    	$orderIncrementalId = $this->removePrefix((string)$record->referenceNumber);
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementalId);
        
        if($order->getData('increment_id') != null) {

            $frase = '';
            $cod_status = (string)$record->transactionState;
            $comment = '(Status: '.(string)$record->transactionState.' - '.$record->transactionStatus.')';
            
            switch ($cod_status){
            	case '6':
            		if ($order->getStatus() == Mage_Sales_Model_Order::STATE_NEW
            			|| $order->getStatus() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
            			
            			Mage::getSingleton('checkoutapi/processador')->autorizar($order, (string)$record->transactionID);
            		}
            		break;
                case '3':
                case '10':
                case '35':
                case '36':
                	if ($order->getStatus() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                		
	                	$captura = $cod_status == '3';
	                	Mage::getSingleton('checkoutapi/processador')->aprovar($order, $captura, null);
                	}
                    break;
                case '44':
                    
                	if ($order->getStatus() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {     
                		
                		Mage::getSingleton('checkoutapi/processador')->autorizar($order, (string)$record->transactionID);
                		if ($order->getPayment()->getData('maxipago_processor_type') != 'authM') {  
                                        //Captura
                                        Mage::getSingleton('checkoutapi/api')->capture($order, $order->getGrandTotal());
                                        //Aprovo
                			Mage::getSingleton('checkoutapi/processador')->aprovar($order, true, null);                                      
                		}   
                	}
                	break;
                case '1':
                case '4':
                case '5':
                case '11':
                case '12':
                case '13':
                case '14':
                case '16':
                case '18':
                case '19':
                case '22':
                case '29':
                case '30':
                case '31':
                case '32':
                case '33':
                case '34':
                case '38':
                case '46':
                	$frase = 'maxiPago! - Atualização automática do status do pedido. ' . $comment;
                	Mage::getSingleton('checkoutapi/processador')->enviarNotificacaoMudancaStatus($order, $frase, 'Atualização de status');
                	$order->save();
                    break;
                //case '7':
                //case '9':
                case '45':
                	if ($order->getStatus() != Mage_Sales_Model_Order::STATE_COMPLETE
                		&& $order->getStatus() != Mage_Sales_Model_Order::STATE_CLOSED
                		&& $order->getStatus() != Mage_Sales_Model_Order::STATE_CANCELED
                		&& $order->getStatus() != Mage_Sales_Model_Order::STATE_HOLDED) {
                		
                		$chargeTotal = round($order->getGrandTotal(), 2);
                		$chargeRefund = round($record->transactionAmount, 2);
                		if (abs($chargeTotal - $chargeRefund) < .0001) {
                			
                			Mage::getSingleton('checkoutapi/processador')->estornar($order, null, true);
                		}
                	}
                    break;
                default:
                	$frase = 'maxiPago! - Aguardando Pagamento. ' . $comment;
                	Mage::getSingleton('checkoutapi/processador')->enviarNotificacaoMudancaStatus($order, $frase, 'Aguardando Pagamento');
                	$order->save();
                    break;
            }
        }
        else {
        	
            Mage::helper('checkoutapi')->log('Pedido ' . $orderIncrementalId . ' não encontrado', 'maxipago.log');        
        }
    }
    
    protected function updateRecurringPayment($record) {
    	$profile = Mage::getModel('sales/recurring_profile')->load((string)$record->orderId, 'reference_id');
    	if (!$profile->getId()) {
    		return;
    	}
    	
    	Mage::getSingleton('checkoutapi/processador')->atualizarPagamentoRecorrente($profile, $record);
    }
    
    protected function removePrefix($referenceNumber) {
    	$incremetalOrderId = $referenceNumber;
    	$paymentMethods = array('creditcard', 'bankslip', 'tef', 'redepay');
    	foreach ($paymentMethods as $p) {
    		$incremetalOrderId = str_replace(Mage::getStoreConfig("payment/maxipagocheckoutapi_$p/prefixo"), '', $incremetalOrderId);
    	}
    	return $incremetalOrderId;
    }
    
    public function salesOrderBeforeCancellation($observer) {
    	
    	$order = $observer->getEvent()->getPayment()->getOrder();
    	$payment = $order->getPayment();
    	$methodCode = $payment->getMethodInstance()->getCode();
    	
    	if ($methodCode != 'maxipagocheckoutapi_creditcard'
    		|| $order->getStatus() != Mage_Sales_Model_Order::STATE_PROCESSING
    		|| $order->hasInvoices()) {
    		
    		return $this;
    	}
    	
    	$processorId = $payment->getData('maxipago_processor_id');
    	$timestampCaptura = $payment->getData('maxipago_capture_timestamp');
    	$sucessoEstorno = true;
    	$tipoEstorno = '';
    	$canVoid = date('Ymd') == date('Ymd', $timestampCaptura);
    	$responseMP = null;
    	
    	try {
    		
    		// void
    		if ($canVoid) {
    			
    			$tipoEstorno = 'void';
    			$responseMP = Mage::getSingleton('checkoutapi/api')->void($order);
    			$sucessoEstorno = $responseMP && intval($responseMP->responseCode) == 0;
    		}
    		
    		// refund
    		if (!$canVoid || (!$sucessoEstorno && $processorId == '6')) {
    			
    			$chargeTotal = round($order->getGrandTotal(), 2);
    			
    			$tipoEstorno = 'refund';
    			$responseMP = Mage::getSingleton('checkoutapi/api')->returnPayment($order, $chargeTotal);
    			$sucessoEstorno = $responseMP && intval($responseMP->responseCode) == 0;
    		}
    		
    		if ($sucessoEstorno) {
    			
    			// Se foi estorno ou o adquirente é a Cielo
    			if ($tipoEstorno == 'void' || $processorId == '4') {
    				
    				$mensagem = 'maxiPago! - Estorno. Pagamento Estornado.';
    			}
    			else {
    				$mensagem = 'maxiPago! - O Estorno será processado e posteriormente o pedido será atualizado';
    			}
    			
    			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('core')->__($mensagem));
    		}
    		else {
    			
    			$mensagem = 'maxiPago! - Falha ao estornar a transação. '.(string)$responseMP->responseCode . ' - ' . (string)$responseMP->responseMessage . '(' . (string)$responseMP->errorMessage . ')';
    			Mage::throwException(Mage::helper('core')->__($mensagem));
    			exit();
    		}
    	}
    	catch (\Exception $ex) {
    		
    		Mage::throwException(Mage::helper('core')->__('Ocorreu um erro inesperado. Tente novamente mais tarde'));
    		exit();
    	}
    	
    	return $this;
    }
    
    public function salesOrderPaymentPlaceEnd($observer) {
    	
    	$payment = $observer->getEvent()->getPayment();
    	$methodCode = $payment->getMethodInstance()->getCode();
    	$responseMP = simplexml_load_string(Mage::getSingleton('core/session')->getResponseMp());
    	$responseCode = $responseMP ? intval($responseMP->responseCode) : -1;
    	$state = null;
    	
    	if ($methodCode != 'maxipagocheckoutapi_creditcard'
    		&& $methodCode != 'maxipagocheckoutapi_bankslip'
    		&& $methodCode != 'maxipagocheckoutapi_tef'
    		&& $methodCode != 'maxipagocheckoutapi_redepay') {
    		return $this;
    	}
    	
    	// Altera o status do pedido para o valor correto 
    	if ($responseCode == 0) {
    		if ($methodCode != 'maxipagocheckoutapi_creditcard'
    			|| $payment->getData('maxipago_processor_type') == 'authM') {
    			$state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
    		}
    		else {
    			$state = Mage_Sales_Model_Order::STATE_PROCESSING;
    		}
    	}
    	elseif ($responseCode == 5) {
    		$state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
    	}
    	
    	if ($state) {
    		$payment->getOrder()->setState($state, true);
    	}
    		
    	return $this;
    }
    
    public function salesOrderPlaceAfter($observer) {
    	
    	$order = $observer->getEvent()->getOrder();
    	$methodCode = $order->getPayment()->getMethodInstance()->getCode();
    	
    	if ($methodCode != 'maxipagocheckoutapi_creditcard') {
    		return $this;
    	}
    	
    	if ($order->getPayment()->getData('maxipago_processor_type') != 'authM')
    	{
    		Mage::getSingleton('checkoutapi/processador')->gerarFatura($order, $order->getPayment()->getTransactionId());
    	}
    	
    	return $this;
    }
}