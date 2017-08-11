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

class MaxiPago_CheckoutApi_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    protected $_allowCurrencyCode = array('BRL');

    /**
     * Can be edit order (renew order)
     *
     * @return bool
     */
    public function canEdit()
    {
        return false;
    }
    
    /**
     * @return boolean
     */
    public function canFetchTransactionInfo()
    {
    	return false;
    }
    
    /**
     * Using for multiple shipping address
     *
     * @return bool
     */
    public function canUseForMultishipping()
    {
    	return false;
    }
    
    /**
     *  Return Order Place Redirect URL
     *
     *  @return	  string Order Redirect URL
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('checkoutapi/standard/payment', array('_secure' => true, 'type' => 'standard'));
    }
 
     /**
     * Get checkoutapi session namespace
     *
     * @return MaxiPago_CheckoutApi_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('checkoutapi/session');
    }
    
    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
    
    public function encrypt($data) {
    	if ($data) {
    		return Mage::helper('core')->encrypt($data);
    	}
    	return $data;
    }
    
    public function decrypt($data) {
    	if ($data) {
    		return Mage::helper('core')->decrypt($data);
    	}
    	return $data;
    }
    
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock($_formBlockType, $name)
            ->setMethod('checkoutapi')
            ->setPayment($this->getPayment())
            ->setTemplate('maxipago/checkoutapi/form.phtml');
        return $block;
    }
    
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $info->setCcType($data->getCcType())
             ->setCcOwner($data->getCcOwner())
             ->setCcNumber($data->getCcNumber())
             ->setCcCid($data->getCcCid())
             ->setCcExpMonth($data->getCcExpMonth())
             ->setCcExpYear($data->getCcExpYear())
             ->setMaxipagoSplitNumber($data->getMaxipagoSplitNumber())
             ->setMaxipagoSplitValue($data->getMaxipagoSplitValue())
             ->setMaxipagoCcToken($data->getMaxipagoCcToken())
             ->setCcNumberEnc($info->encrypt($info->getCcNumber()))
        	 ->setCcCidEnc($info->encrypt($info->getCcCid()));
		if ($data->getCcLast4())
			$info->setCcLast4($data->getCcLast4());
		else
			$info->setCcLast4(substr($data->getCcNumber(), -4));
			
        return $this;
    }
    
    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        $info->setCcNumberEnc($info->encrypt($info->getCcNumber()))
	        ->setCcCidEnc($info->encrypt($info->getCcCid()))
        	->setCcNumber(null)
            ->setCcCid(null);
        return $this;
    }
    
    public function validateCcNum($ccNumber)
    {
        $cardNumber = strrev($ccNumber);
        $numSum = 0;

        for ($i=0; $i<strlen($cardNumber); $i++) {
            $currentNum = substr($cardNumber, $i, 1);

            /**
             * Double every second digit
             */
            if ($i % 2 == 1) {
                $currentNum *= 2;
            }

            /**
             * Add digits of 2-digit numbers together
             */
            if ($currentNum > 9) {
                $firstNum = $currentNum % 10;
                $secondNum = ($currentNum - $firstNum) / 10;
                $currentNum = $firstNum + $secondNum;
            }

            $numSum += $currentNum;
        }

        /**
         * If the total has no remainder it's OK
         */
        return ($numSum % 10 == 0);
    }
    
    public function validate()
    {
        parent::validate();
        
        $errorMsg = "";
        $quote = $this->getCheckout()->getQuote();
        
        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();
        
        $currency_code = $this->getQuote()->getBaseCurrencyCode();
        if (!$currency_code){
            $session = Mage::getSingleton('adminhtml/session_quote');
            $currency_code = $session->getQuote()->getBaseCurrencyCode();            
        } 
        if (!in_array($currency_code,$this->_allowCurrencyCode)) {
            Mage::throwException(Mage::helper('checkoutapi')->__('A moeda selecionada ('.$currency_code.') não é compatível com o MaxiPago'));
        }
        
        $ccType = $quote->getPayment()->getData('cc_type');
        $ccNumber = $quote->getPayment()->getData('cc_number');
        if(!in_array($ccType, array("VI","MC","AM","DI"))){
            if ($this->validateCcNum($ccNumber)) {
                switch ($ccType){
                    // Validação Visa
                    case "VI":
                        if (!preg_match('/^4([0-9]{12}|[0-9]{15})$/', $ccNumber)) {
                            $errorMsg .= Mage::helper('payment')->__('Invalid Credit Card Number')."\n";
                        }
                        break;
                    // Validação Master
                    case "MC":
                        if (!preg_match('/^5([1-5][0-9]{14})$/', $ccNumber)) {
                            $errorMsg .= Mage::helper('payment')->__('Invalid Credit Card Number')."\n";
                        }
                        break;
                    // Validação American Express
                    case "AM":
                        if (!preg_match('/^3[47][0-9]{13}$/', $ccNumber)) {
                            $errorMsg .= Mage::helper('payment')->__('Invalid Credit Card Number')."\n";
                        }
                        break;
                    // Validação Discovery
                    case "DI":
                        if (!preg_match('/^6011[0-9]{12}$/', $ccNumber)) {
                            $errorMsg .= Mage::helper('payment')->__('Invalid Credit Card Number')."\n";
                        }
                        break;
                }
            }
            else {
                $errorMsg = Mage::helper('payment')->__('Invalid Credit Card Number')."\n";
            }
        }
        if($ccType == "DC"){
            if (!preg_match('/^3(6|8)[0-9]{12}|^3(00|01|02|03|04|05)[0-9]{11}$/', $ccNumber)) {
                $errorMsg .= Mage::helper('payment')->__('Invalid Credit Card Number')."\n";
            }
        }
        
        if($errorMsg != ""){
            $errorMsg .= "\nVerifique as informações para finalizar a compra pelo ".$this->getConfigData('title');
            Mage::throwException($errorMsg);
        }
        return $this;
    }
    
    public function getConfigPaymentAction()
    {
    	$action = parent::getConfigPaymentAction();
    	
    	if (is_null($action) || trim($action) === '') {
    		return Mage_Payment_Model_Method_Abstract::ACTION_ORDER;
    	}
    	
    	return $action;
    }
	
	/**
     * Order the payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     */
    public function order(Varien_Object $payment, $amount)
    {
    	parent::order($payment, $amount);
    	
    	$payment->setAmount($amount);
    	$order = $payment->getOrder();
    	$mensagem = '';
    	
    	// Chama o auth da maxiPago
    	$responseMP = Mage::getSingleton('checkoutapi/api')->auth($order);
    	$responseCode = $responseMP && property_exists($responseMP, 'responseCode') ? intval($responseMP->responseCode) : -1;
        
        //Disparo email de ordem de compra
	if(!$order->getEmailSent()) {
	    $order->sendNewOrderEmail();
	    $order->setEmailSent(true);
	    $order->save();
	}

    	switch ($responseCode) {
    		
    		case 0:
    		case 5:
    			$state  = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
    			$processor = $this->getConfigData('processorType');
    			$txnId = (string)$responseMP->transactionID;
    			
    			$payment->setData('maxipago_transaction_id', $txnId);
    			$payment->setData('maxipago_token_transaction', (string)$responseMP->orderID);
                        
                        //Novos campos
                        $payment->setData('maxipago_authcode', (string)$responseMP->authCode);
                        $payment->setData('maxipago_processor_transaction_id', (string)$responseMP->processorTransactionID);
                        $payment->setData('maxipago_processor_reference_number', (string)$responseMP->processorReferenceNumber);
                                                
    			$payment->setData('maxipago_url_payment', (isset($responseMP->onlineDebitUrl)) ? (string)$responseMP->onlineDebitUrl : (string)$responseMP->boletoUrl);
    			$payment->setData('maxipago_fraud_score', (string)$responseMP->fraudScore);
    			$payment->setData('maxipago_processor_type', $processor);
    			$payment->setData('maxipago_processor_id', $this->getProcessor($payment->getCcType()));
    			if ($responseMP->{'save-on-file'} && $responseMP->{'save-on-file'}->token) {
    				$ccToken = $this->encrypt((string)$responseMP->{'save-on-file'}->token);
    				$payment->setData('maxipago_cc_token', $ccToken);
    			}
    			$payment->resetTransactionAdditionalInfo();
    			$payment->setTransactionId($txnId);
    			
    			// Autorização efetuada
    			if ($responseCode == 0) {
    			
    				$methodCode = $payment->getMethodInstance()->getCode();
	    			
	    			if ($methodCode != 'maxipagocheckoutapi_creditcard') {
	    				$note = '';
	    				if ($methodCode == 'maxipagocheckoutapi_bankslip') {
	    					$mensagem = 'maxiPago! - Aguardando pagamento via boleto bancário.';
	    					$note = Mage::helper('core')->__('Boleto bancário no valor de %s', round($amount, 2));
	    				}
	    				elseif ($methodCode == 'maxipagocheckoutapi_tef') {
	    					$mensagem = 'maxiPago! - Aguardando pagamento via transferência bancária.';
	    					$note = Mage::helper('core')->__('Transferência bancária no valor de %s', round($amount, 2));
	    				}
	    				
	    				$payment->setIsTransactionClosed(1);
	    				$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false, $note);
	    			}
	    			else {
	    				
		    			if ($processor == 'sale') {
		    				
		    				$payment->setData('maxipago_capture_timestamp', (string)$responseMP->transactionTimestamp);
		    				$payment->setIsTransactionClosed(1);
		    				$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false,
		    						Mage::helper('core')->__('Venda direta de %s.', round($amount, 2))
		    						);
		    			
		    				$state  = Mage_Sales_Model_Order::STATE_PROCESSING;
		    			}
		    			else {
		    				
		    				$payment->setIsTransactionClosed($processor == 'auth' ? 1 : 0);
		    				$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false,
		    						Mage::helper('core')->__('Autorização de %s.', round($amount, 2))
		    						);
		    			}
		    			
		    			if($processor == 'auth') {
		    				
		    				$responseCaptureMP = Mage::getSingleton('checkoutapi/api')->capture($order, $amount);
		    				$responseCaptureCode = $responseCaptureMP && property_exists($responseCaptureMP, 'responseCode') ? intval($responseCaptureMP->responseCode) : -1;

		    				if($responseCaptureCode == 0){
		    					
		    					$responseMP->responseMessage = 'CAPTURED';
		    					
		    					$payment->setData('maxipago_capture_timestamp', (string)$responseCaptureMP->transactionTimestamp);
		    					$payment->resetTransactionAdditionalInfo();
		    					$payment->setTransactionId((string)$responseCaptureMP->transactionID);
		    					$payment->setShouldCloseParentTransaction(true);
		    					$payment->setParentTransactionId($txnId);
		    					$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, false,
		    							Mage::helper('core')->__('Captura de %s.', round($amount, 2))
		    							);
		    					
		    					$state  = Mage_Sales_Model_Order::STATE_PROCESSING;
		    				}
		    			}
		    			
		    			switch ($processor)
		    			{
		    				case 'authM':
		    					$mensagem = 'maxiPago! - Autorizado. Pagamento pendente de captura.';
		    					break;
		    				case 'auth':
		    					$mensagem = 'maxiPago! - Autorizado. Pagamento confirmado automaticamente.';
		    					break;
		    				case 'sale':
		    					$mensagem = 'maxiPago! - Aprovado. Pagamento confirmado automaticamente.';
		    					break;
		    			}
	    			}
    			}
    			// Revisão de fraude
    			else {
    				$payment->setIsTransactionClosed(0);
    				$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false,
    						Mage::helper('core')->__('Autorização de %s.', round($amount, 2))
    						);
    				
    				$mensagem = 'maxiPago! - Pagamento Pendente.';
    				$state  = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
    			}
    			
    			$order->setState($state, true, $mensagem, false);
    			$this->enviarNotificacaoMudancaStatus($order, $mensagem);
    			
    			Mage::getSingleton('core/session')->setResponseMp($responseMP->asXML());
    			
    			break;
    		case 1:
    		case 2:
    		case 1022:
    			Mage::throwException("Transação não Autorizada.\nPor favor selecione outra forma de pagamento!");
    			Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($order->getStoreId());
    			break;
    		case 1024:
    		case 1025:
    		case 2048:
    		case 4097:
    		case -1;
    			$responseCodeError = (property_exists($responseMP, 'responseCode')) ? (string)$responseMP->responseCode : 'errorCode=1';
    			Mage::helper('checkoutapi')->log('Error Pedido Cancelado - Codigo: '.(string)$responseCodeError, 'maxipago.log');
    			Mage::throwException("Ocorreu um erro inesperado. Por favor aguarde alguns instantes e tente novamente!");
    			Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($order->getStoreId());
    			break;
    	}
    	
    	$order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, false);
    	$payment->setSkipOrderProcessing(true);
    	
    	return $this;
    }
    
    protected function enviarNotificacaoMudancaStatus($order, $mensagem) {
    	
    	$emailStatus = explode(',', $this->getConfigData('emailStatus'));
    	$sendEmail = in_array($order->getStatus(), $emailStatus) ? true :  false;
    	
    	$order->addStatusToHistory(
    			$order->getStatus(),
    			Mage::helper('checkoutapi')->__($mensagem),
    			$sendEmail
    			);
    	if($sendEmail) { $order->sendOrderUpdateEmail($sendEmail, ''); }
    }
}