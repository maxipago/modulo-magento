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

class MaxiPago_CheckoutApi_StandardController extends Mage_Core_Controller_Front_Action 
{

    /**
     * Order instance
     */
    protected $_order;
    protected $errorMessageMaxiPago = '';
    protected $errorCodeMaxiPago = '';
    protected $errorTypeErrorMaxiPago = '';
    
    protected function _getSession()
    {
    	return Mage::getSingleton('adminhtml/session');
    }
    
    public function paymentAction()
    {             
       $this->loadLayout();
       $this->renderLayout();       
    }
    
    public function logAction()
    {
        $file_contents = fopen( Mage::getBaseDir()."/var/log/maxipago.log", "r" );
        print_r("<pre>".fread($file_contents,filesize(Mage::getBaseDir()."/var/log/maxipago.log"))."</pre>");
        fclose($file_contents);

        //$this->loadLayout();
        //$this->renderLayout();
    }
    
    public function returnAction()
    {
       $this->loadLayout();
       $this->renderLayout();
    }
    
    public function paymentbackendAction() 
    {
        $this->loadLayout();
        $this->renderLayout();

        $hash = explode("/order/", $this->getRequest()->getOriginalRequest()->getRequestUri());
        $hashdecode = explode(":", Mage::getModel('core/encryption')->decrypt($hash[1]));

        $order = Mage::getModel('sales/order')
                ->getCollection()
                ->addFieldToFilter('increment_id', $hashdecode[0])
                ->addFieldToFilter('quote_id', $hashdecode[1])
                ->getFirstItem();

        if ($order) {
            $session = Mage::getSingleton('checkout/session');
            $session->setLastQuoteId($order->getData('quote_id'));
            $session->setLastOrderId($order->getData('entity_id'));
            $session->setLastSuccessQuoteId($order->getData('quote_id'));
            $session->setLastRealOrderId($order->getData('increment_id'));
            $session->setCheckoutApiQuoteId($order->getData('quote_id'));
            $this->_redirect('checkoutapi/standard/payment/type/standard');
        } else {
            Mage::getSingleton('checkout/session')->addError('URL informada é inválida!');
            $this->_redirect('checkout/cart');
        }
    }

    public function errorAction()
    {
       $this->loadLayout();
       $this->renderLayout();
    }
    
    public function captureAction()
    {
        $params = $this->getRequest();
        $order = Mage::getModel('sales/order')->load($params->getParam('order_id'));
        
        try {
        	
        	$responseMP = Mage::getSingleton('checkoutapi/api')->capture($order, $order->getGrandTotal());
        	
        	if(intval($responseMP->responseCode) == 0) {

        		Mage::getSingleton('checkoutapi/processador')->aprovar($order, true, (string)$responseMP->transactionID, (string)$responseMP->transactionTimestamp);
        		$mensagem = 'maxiPago! - Transação capturada com sucesso.';
        		$this->_getSession()->addSuccess(Mage::helper('core')->__($mensagem));
        	}
        	else {
        		
	        	$mensagem = 'maxiPago! - Falha ao capturar a transação. '. (string)$responseMP->responseCode . ' - ' . (string)$responseMP->responseMessage . '(' . (string)$responseMP->errorMessage . ')';
	            $this->_getSession()->addError(Mage::helper('core')->__($mensagem));
	            $this->_redirectReferer();
	            return;
        	}
        }
        catch (\Exception $ex) {
        	
        	$this->_getSession()->addError(Mage::helper('core')->__('Ocorreu um erro inesperado. Tente novamente mais tarde'));
        	$this->_redirectReferer();
        	return;
        }
        
        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $params->getParam('order_id')  ,'key' => $params->getParam('key')));
    }
    
    public function reversalAction() {
    	
    	$params = $this->getRequest();
    	$order = Mage::getModel('sales/order')->load($params->getParam('order_id'));
//     	$payment = $order->getPayment();
//     	$methodCode = $payment->getMethodInstance()->getCode();
//     	$processorId = $payment->getData('maxipago_processor_id');
//     	$timestampCaptura = $payment->getData('maxipago_capture_timestamp');
//     	$sucessoEstorno = true;
//     	$tipoEstorno = '';
//     	$canVoid = date('Ymd') == date('Ymd', $timestampCaptura) && $methodCode == 'maxipagocheckoutapi_creditcard';
//     	$responseMP = null;
    	
    	try {
    		
    		$transactionSave = Mage::getModel('core/resource_transaction');
    		
    		if ($order->canCreditmemo()) {
    		
    			/** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoiceCollection */
    			$invoiceCollection = $order->getInvoiceCollection();
    			/** @var Mage_Sales_Model_Service_Order $service */
    			$service = Mage::getModel('sales/service_order', $order);
    		
    			if ($invoiceCollection && count($invoiceCollection) > 0)
    			{
    				foreach ($invoiceCollection as $invoice) {
    		
    					/** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
    					$creditmemo = $service->prepareInvoiceCreditmemo($invoice)
    					->setOfflineRequested(false)
    					->setTransactionId($invoice->getTransactionId())
    					->addComment('Pagamento estornado.');
    		
    					foreach ($creditmemo->getAllItems() as $creditmemoItem) {
    						/**
    						 * @var  $creditmemoItems
    						 * @var  $orderItem
    						 */
    						$orderItem = $creditmemoItem->getOrderItem();
    		
    						if (!$orderItem->getParentItemId()) {
    							$creditmemoItem->setBackToStock(true);
    						}
    					}
    		
    					$creditmemo->register();
    					$transactionSave->addObject($invoice);
    					$transactionSave->addObject($creditmemo);
    				}
    			}
    			else {
    		
    				/** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
    				$creditmemo = $service->prepareCreditmemo()
    				->setOfflineRequested(false)
    				->addComment('Pagamento estornado.');
    					
    				foreach ($creditmemo->getAllItems() as $creditmemoItem) {
    					/**
    					 * @var  $creditmemoItems
    					 * @var  $orderItem
    					 */
    					$orderItem = $creditmemoItem->getOrderItem();
    		
    					if (!$orderItem->getParentItemId()) {
    						$creditmemoItem->setBackToStock(true);
    					}
    				}
    				
    				$creditmemo->register();
    				$transactionSave->addObject($creditmemo);
    			}
    		}
    		else {
    			
				$order->cancel();
    		}
    			
    		$transactionSave->addObject($order)
    		->save();
    		
    		$mensagem = 'maxiPago! - Estorno. Pagamento Estornado.';
    		Mage::helper('checkoutapi')->enviarNotificacaoMudancaStatus($order, $mensagem);
    		
    		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('core')->__($mensagem));
    	}
    	catch (\Exception $ex) {
    		
    		$this->_getSession()->addError(Mage::helper('core')->__('Ocorreu um erro inesperado. Tente novamente mais tarde'));
    	}
    	
    	$this->_redirect('adminhtml/sales_order/view', array('order_id' => $params->getParam('order_id')  ,'key' => $params->getParam('key')));
    }
    
    /**
     *  Get order
     *
     *  @return	  Mage_Sales_Model_Order
     */
    public function getOrder() {
        
        if ($this->_order == null) {
            
        }
        
        return $this->_order;
    }

    protected function _expireAjax() {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    /**
     * Get singleton with checkout standard order transaction information
     *
     * @return MaxiPago_CheckoutApi_Model_Api
     */
    public function getApi() 
    {
        return Mage::getSingleton('checkoutapi/'.$this->getRequest()->getParam("type"));
    }

    /**
     * When a customer chooses MaxiPago on Checkout/Payment page
     *
     */
    public function redirectAction() 
    {
        
        $type = $this->getRequest()->getParam('type', false);
        
        $session = Mage::getSingleton('checkout/session');

        $session->setCheckoutApiQuoteId($session->getQuoteId());
        
        $this->getResponse()->setHeader("Content-Type", "text/html; charset=ISO-8859-1", true);

        $this->getResponse()->setBody($this->getLayout()->createBlock('checkoutapi/redirect')->toHtml());

        $session->unsQuoteId();
    }

    /**
     * When a customer cancel payment from MaxiPago .
     */
    public function cancelAction() 
    {
        
        $session = Mage::getSingleton('checkout/session');

        $session->setQuoteId($session->getCheckoutApiQuoteId(true));

        // cancel order
        if ($session->getLastRealOrderId()) {

            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

            if ($order->getId()) {
                $order->cancel()->save();
            }
        }

        $this->_redirect('checkout/cart');
    }
    
    private function getUrlPostCheckoutApi($sandbox)
    {
         if ($sandbox == '1')
         {
        	return 'https://testapi.maxipago.net/UniversalAPI/postXML';
         } else {
		return 'https://api.maxipago.net/UniversalAPI/postXML';
         }
    }
    
    /**
     * when checkout returns
     * The order information at this point is in POST
     * variables.  However, you don't want to "process" the order until you
     * get validation from the return post.
     */
    public function successAction() 
    {
        $_type = $this->getRequest()->getParam('type', false);
        $token = $this->getApi()->getConfigData('title');

// 	    $urlPost = $this->getUrlPostCheckoutApi($this->getApi()->getConfigData('sandbox'));
        $urlPost = $this->getUrlPostCheckoutApi(Mage::helper('checkoutapi')->getGlobalConfig('sandbox'));

        $dados_post = $this->getRequest()->getPost();
         
        $order_number_conf = utf8_encode(str_replace($this->getApi()->getConfigData('prefixo'),'',$dados_post['transaction']['order_number']));
        $transaction_token= $dados_post['transaction']['transaction_token']; 

        ob_start(); 
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $urlPost); 
        curl_setopt($ch, CURLOPT_POST, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, array("token"=>trim($transaction_token), "type_response"=>"J")); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Expect:")); 
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_exec ($ch); 

        /* XML ou Json de retorno */ 
        $resposta = ob_get_contents(); 
        ob_end_clean(); 

        /* Capturando o http code para tratamento dos erros na requisi��o*/ 
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
        curl_close($ch); 
        $arrResponse = json_decode($resposta,TRUE);
        $xml = simplexml_load_string($resposta);
        $emailStatus = explode(",",$this->getApi()->getConfigData('emailStatus'));
        if($httpCode != "200" ){
            $codigo_erro = $xml->codigo;
            $descricao_erro = $xml->descricao;
            if ($codigo_erro == ''){
                $codigo_erro = '0000000';
            }
            if ($descricao_erro == ''){
                $descricao_erro = 'Erro Desconhecido';
            }
            $this->_redirect('checkoutapi/standard/error', array('_secure' => true , 'descricao' => urlencode(utf8_encode($descricao_erro)),'codigo' => urlencode($codigo_erro)));
        }else{
        	
            $transaction = $arrResponse['data_response']['transaction'];
            $order_number = str_replace($this->getApi()->getConfigData('prefixo'),'',$transaction['order_number']);
        	if($order_number != $order_number_conf) {
        		$codigo_erro = '0000000';
                $descricao_erro = "Pedido: " . $order_number_conf . " não corresponte com a pedido consultado: ".$order_number."!";
                $this->_redirect('checkoutapi/standard/error', array('_secure' => true , 'descricao' => urlencode(utf8_encode($descricao_erro)),'codigo' => urlencode($codigo_erro)));
        	}
            
            if (isset($transaction['status_id'])) {
                $comment .= " " . $transaction['status_id'];
            }

            if (isset($transaction['status_name'])) {
                $comment .= " - " . $transaction['status_name'];
            }
            echo "Pedido: $order_number - $comment - ID: ".$dados_post['transaction']['transaction_id'];
            $order = Mage::getModel('sales/order');

            $order->loadByIncrementId($order_number);
            
            if ($order->getId()) {

                if ($transaction['price_original'] != $order->getGrandTotal()) {
                    
                    $frase = 'Total pago é diferente do valor original.';
                    $sendEmail = (in_array($order->getStatus(),$emailStatus)) ? true :  false;
                    $order->addStatusToHistory(
                            $order->getStatus(), //continue setting current order status
                            Mage::helper('checkoutapi')->__($frase), $sendEmail
                    );
                    if($sendEmail){$order->sendOrderUpdateEmail($sendEmail, 'Aguardando Pagamento');}
                } else {
                    $cod_status = $transaction['status_id'];

                    switch ($cod_status){
                        case '4': 
                        case '5':
                        case '88':
                                $sendEmail = (in_array( Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,$emailStatus)) ? true :  false;
                                $order->addStatusToHistory(
                                    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                                    Mage::helper('checkoutapi')->__('maxiPago! enviou automaticamente o status: %s', $comment),
                                    $sendEmail
                                );
                                if($sendEmail){$order->sendOrderUpdateEmail($sendEmail, 'Aguardando Pagamento');}
                            break;
                        case '6':
                                $items = $order->getAllItems();

                                $thereIsVirtual = false;

                                foreach ($items as $itemId => $item) {
                                    if ($item["is_virtual"] == "1" || $item["is_downloadable"] == "1") {
                                        $thereIsVirtual = true;
                                    }
                                }

                                // what to do - from admin
                                $toInvoice = $this->getApi()->getConfigData('acaopadraovirtual') == "1" ? true : false;

                                if ($thereIsVirtual && !$toInvoice) {

                                    $frase = $this->getApi()->getConfigData('title').' - Aprovado. Pagamento (fatura) confirmado automaticamente.';
                                    $sendEmail = (in_array(Mage_Sales_Model_Order::STATE_PROCESSING,$emailStatus)) ? true :  false;
                                    $order->addStatusToHistory(
                                        Mage_Sales_Model_Order::STATE_PROCESSING, //continue setting current order status
                                        Mage::helper('checkoutapi')->__($frase), $sendEmail
                                    );

                                    if($sendEmail){$order->sendOrderUpdateEmail($sendEmail, 'Pagamento confirmado');}
                                } else {
									
                                    if (!$order->canInvoice()) {
                                    	$isHolded = ( $order->getStatus() == Mage_Sales_Model_Order::STATE_HOLDED );

										$status = ($isHolded) ? Mage_Sales_Model_Order::STATE_COMPLETE :  $order->getStatus();
										$frase  = ($isHolded) ? $this->getApi()->getConfigData('title').' - Aprovado. Confirmado automaticamente o pagamento do pedido.' : 'Erro ao criar pagamento (fatura).';
										
                                        //when order cannot create invoice, need to have some logic to take care
                                        $sendEmail = (in_array($status,$emailStatus)) ? true :  false;
                                        $order->addStatusToHistory(
                                            $status, //continue setting current order status
                                            Mage::helper('checkoutapi')->__( $frase ),$sendEmail
                                        );
                                        if($sendEmail){$order->sendOrderUpdateEmail($sendEmail, '');}
                                    } else {
										$txnId = intval($dados_post['transaction']['transaction_id']);
	                                	//need to save transaction id
                                    	$order->getPayment()->setTransactionId($txnId);
                                    
                                        //need to convert from order into invoice
                                        $invoice = $order->prepareInvoice();

                                        if ($this->getApi()->canCapture()) {
                                            $invoice->register()->capture();
                                        }

                                        Mage::getModel('core/resource_transaction')
                                                ->addObject($invoice)
                                                ->addObject($invoice->getOrder())
                                                ->save();

                                        $frase = 'Pagamento (fatura) ' . $invoice->getIncrementId() . ' foi criado. '.$this->getApi()->getConfigData('title').' - Aprovado. Confirmado automaticamente o pagamento do pedido.';

                                        if ($thereIsVirtual) {

                                            $sendEmail = (in_array($order->getStatus(),$emailStatus)) ? true :  false;
                                            $order->addStatusToHistory(
                                                $order->getStatus(), Mage::helper('checkoutapi')->__($frase), $sendEmail
                                            );
                                            if($sendEmail){$order->sendOrderUpdateEmail($sendEmail, 'Pagamento Aprovado');}

                                        } else {

                                            $sendEmail = (in_array(Mage_Sales_Model_Order::STATE_COMPLETE,$emailStatus)) ? true :  false;
                                            $order->addStatusToHistory(
                                                Mage_Sales_Model_Order::STATE_COMPLETE, //update order status to processing after creating an invoice
                                                Mage::helper('checkoutapi')->__($frase), $sendEmail
                                            );
                                            if($sendEmail){$order->sendOrderUpdateEmail($sendEmail, 'Pagamento Aprovado');}
                                        }

                                        //$invoice->sendEmail(true, $frase);
                                    }
                                }
                            break;
                        case '24':
                                $sendEmail = (in_array(Mage_Sales_Model_Order::STATE_HOLDED,$emailStatus)) ? true :  false;
                                $order->addStatusToHistory(
                                    Mage_Sales_Model_Order::STATE_HOLDED, Mage::helper('checkoutapi')->__($this->getApi()->getConfigData('title').' enviou automaticamente o status: %s', $comment),$sendEmail
                                );
                                if($sendEmail){$order->sendOrderUpdateEmail($sendEmail, 'Aguardando Pagamento');}
                            break;
                        case '7':
                        case '89':                        	
                                $frase = $this->getApi()->getConfigData('title').' - Cancelado. Pedido cancelado automaticamente (transação foi cancelada, pagamento foi negado, pagamento foi estornado ou ocorreu um chargeback).';
                                $sendEmail = (in_array(Mage_Sales_Model_Order::STATE_HOLDED,$emailStatus)) ? true :  false;
                                $order->addStatusToHistory(
                                    Mage_Sales_Model_Order::STATE_CANCELED, Mage::helper('checkoutapi')->__($frase), $sendEmail
                                );

                                if($sendEmail){$order->sendOrderUpdateEmail($sendEmail, 'Pagamento não confirmado');}

                                $order->cancel();
                            break;
                        case '87':
                                $sendEmail = (in_array(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,$emailStatus)) ? true :  false;
                                $order->addStatusToHistory(
                                    Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, Mage::helper('checkoutapi')->__($this->getApi()->getConfigData('title').' enviou automaticamente o status: %s', $comment),$sendEmail
                                );
                                if($sendEmail){$order->sendOrderUpdateEmail($sendEmail, 'Aguardando Pagamento');}
                            break;
                    }
                }
                $order->save();
            }
        }
    }

}