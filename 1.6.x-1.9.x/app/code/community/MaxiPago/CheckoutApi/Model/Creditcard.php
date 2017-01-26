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
 
class MaxiPago_CheckoutApi_Model_Creditcard extends MaxiPago_CheckoutApi_Model_Standard
    implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{
    protected $_code  = 'maxipagocheckoutapi_creditcard';
    protected $_formBlockType = 'checkoutapi/form_creditcard';
    protected $_blockType = 'checkoutapi/creditcard';
    protected $_infoBlockType = 'checkoutapi/info_creditcard';
    protected $_standardType = 'creditcard';
    
    /**
     * Availability options
     */
    protected $_isGateway                   = false;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_canUseInternal              = false;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = false;
    protected $_canFetchTransactionInfo     = true;
    protected $_canCreateBillingAgreement   = true;
    protected $_canReviewPayment            = true;
    
    /**
     * Verify if can void the payment
     * @param Varien_Object $payment
     * @return bool
     */
    public function canVoid(Varien_Object $payment)
    {
    	foreach ($payment->getOrder()->getAllItems() as $item) {
    		if (!$item->getIsVirtual())
    			return false;
    	}
    	return true;
    }
    
    public function getOrderPlaceRedirectUrl(){
        return Mage::getUrl('checkoutapi/standard/payment', array('_secure' => true, 'type' => 'creditcard'));
    }
    
    /**
     * Get the related processor of the cc type
     * @param string $ccType
     * @param string $storeId
     * @return string|NULL
     */
    public function getProcessor($ccType, $storeId = null) {
    	$config = $this->getConfigData('multiprocessor', $storeId);
    	if ($config) {
    		$config = unserialize($config);
    		if (is_array($config)) {
    			foreach ($config as $item) {
    				if ($item['cc_type'] == $ccType) {
    					return $item['processor'];
    				}
    			}
    		}
    	}
		
    	return null;
    }
    
    /**
     * Validate RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
    
    }
    
    /**
     * Submit RP to the gateway
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @param Mage_Payment_Model_Info $paymentInfo
     */
    public function submitRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile,
                                           Mage_Payment_Model_Info $paymentInfo) {
        $quote = $profile->getQuote();
        
        $responseMP = Mage::getSingleton('checkoutapi/api')->recurringPayment($profile, $paymentInfo);
        
        $errorCode = (string)$responseMP->errorCode;
        if ($errorCode && intval($errorCode) != 0) {
        	Mage::helper('checkoutapi')->log('Erro ao processar o pagamento recorrente.: ' . ((string)$responseMP->errorMessage), 'maxipago.log');
        	Mage::throwException('Erro ao processar o pagamento recorrente.');
        	$profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_CANCELED);
        	return;
        }
        
        $quote->getPayment()->setData('maxipago_transaction_id', (string)$responseMP->transactionID);
        $quote->getPayment()->setData('maxipago_token_transaction', (string)$responseMP->orderID);
        $quote->getPayment()->setData('maxipago_fraud_score', (string)$responseMP->fraudScore);
        $quote->getPayment()->save();
        $profile->setData('reference_id', (string)$responseMP->orderID);
        switch (intval((string)$responseMP->responseCode)) {
            case 0:
            	if ($profile->getInitAmount()) {
            		$txnId = (string)$responseMP->transactionID;
            		
	            	$productItemInfo = new Varien_Object;
	            	$productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_INITIAL);
	            	$productItemInfo->setPrice($profile->getInitAmount());
	            	$productItemInfo->setShippingAmount(0);
	            	$productItemInfo->setTaxAmount(0);
	            	$productItemInfo->setIsVirtual(1);
	            	
	            	$order = $profile->createOrder($productItemInfo);
	            	$order->save();
	            	$profile->addOrderRelation($order->getId());
	            	
	            	$transactionSave = Mage::getModel('core/resource_transaction');
	            	
	            	$payment = $order->getPayment();
	            	$payment->setCcType($quote->getPayment()->getCcType())
			            ->setCcOwner($quote->getPayment()->getCcOwner())
			            ->setCcNumber($quote->getPayment()->getCcNumber())
			            ->setCcCid($quote->getPayment()->getCcCid())
			            ->setCcExpMonth($quote->getPayment()->getCcExpMonth())
			            ->setCcExpYear($quote->getPayment()->getCcExpYear())
			            ->setCcNumberEnc($quote->getPayment()->getCcNumberEnc())
			        	->setCcCidEnc($quote->getPayment()->getCcCidEnc())
	            		->setMaxipagoSplitNumber($quote->getPayment()->getMaxipagoSplitNumber())
			            ->setMaxipagoSplitValue($quote->getPayment()->getMaxipagoSplitValue());
			        if ($quote->getPayment()->getCcLast4())
			        	$payment->setCcLast4($quote->getPayment()->getCcLast4());
			        else
			        	$payment->setCcLast4(substr($quote->getPayment()->getCcNumber(), -4));
	            	$payment->setData('maxipago_transaction_id', $txnId);
	            	$payment->setData('maxipago_token_transaction', (string)$responseMP->orderID);
	            	$payment->setData('maxipago_capture_timestamp', (string)$responseMP->transactionTimestamp);
	            	$payment->setData('maxipago_processor_type', $this->getConfigData('processorType'));
	            	$payment->setData('maxipago_processor_id', $this->getProcessor($payment->getCcType()));
	            	$payment->setData('maxipago_fraud_score', (string)$responseMP->fraudScore);
	            	if ($responseMP->{'save-on-file'} && $responseMP->{'save-on-file'}->token) {
	            		$ccToken = $this->encrypt((string)$responseMP->{'save-on-file'}->token);
	            		$payment->setData('maxipago_cc_token', $ccToken);
	            	}
	            	$payment->setTransactionId($txnId)->setIsTransactionClosed(1);
	            	
	            	$transaction= Mage::getModel('sales/order_payment_transaction');
	            	$transaction->setTxnId((string)$responseMP->transactionID);
	            	$transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
	            	$transaction->setPaymentId($payment->getId());
	            	$transaction->setOrderId($order->getId());
	            	$transaction->setOrderPaymentObject($payment);
	            	$transaction->setIsClosed(1);

			    	$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
			    	$order->setIsInProcess(true);
	            	
	            	$transactionSave->addObject($order)
	            	->addObject($payment)
	            	->addObject($transaction)
	            	->save();
	            	
	            	Mage::getSingleton('checkoutapi/processador')->gerarFatura($order, $payment->getTransactionId());
	            	
	            	$profile->setAdditionalInfo(serialize(array(
	            			'cc_type' => $payment->getCcType(),
	            			'cc_owner' => $payment->getCcOwner(),
	            			'cc_last4' => (string)$payment->getccLast4(),
	            			'cc_number' => (string)$payment->getCcNumber(),
	            	)));
            	}
            	
            	$profile->setNewState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE)
	            	->setIsProfileActive(1)
	            	->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE);
                break;
            case 1:
            case 2:
            case 1022:
            	Mage::throwException("Transação não Autorizada.\nPor favor selecione outra forma de pagamento!");
                $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_CANCELED);
                break;
            case 1024:
            case 1025:
            case 2048:
            case 4097:
            	Mage::throwException("Ocorreu um erro inesperado. Por favor aguarde alguns instantes e tente novamente!");
                $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_CANCELED);
                break;
        }
        
        return $this;
    }
    
    /**
     * Fetch RP details
     *
     * @param string $referenceId
     * @param Varien_Object $result
     */
    public function getRecurringProfileDetails($referenceId, Varien_Object $result)
    {
    	Mage::helper('checkoutapi')->log('Atualizando perfil recorrente ' . $referenceId, 'maxipago.log');
    	
    	$profile = Mage::getModel('sales/recurring_profile')->load($referenceId, 'reference_id');
    	
    	$responseMP = Mage::getSingleton('checkoutapi/api')->detailReport($profile->getReferenceId());
    	
    	if (intval($responseMP->header->errorCode) != 0) {
    		$msg = 'Erro ao realizar a consulta: ' . (string)$responseMP->header->errorMsg;
    		Mage::helper('checkoutapi')->log($msg);
    		Mage::throwException($msg);
    	}
    	
    	$setInfo = $responseMP->result->resultSetInfo;
    	$pageToken = (string)$setInfo->pageToken;
    	$numberOfPages = intval($setInfo->numberOfPages);
    	$totalNumberOfRecords = intval($setInfo->totalNumberOfRecords);
    	if($totalNumberOfRecords > 0) {
    		$records = $responseMP->result->records->record;
    		foreach($records as $record) {
    			if (intval($record->recurringPaymentFlag) == 1) {
    				Mage::getSingleton('checkoutapi/processador')->atualizarPagamentoRecorrente($profile, $record);
    			}
    		}
    	
    		for($i = 2 ; $i <= $numberOfPages; $i++) {
    			$responseMP = Mage::getSingleton('checkoutapi/api')->detailReport($pageToken, $i);
    			$records = $responseMP->result->records->record;
    			foreach($records as $record){
    				if (intval($record->recurringPaymentFlag) == 1) {
    					Mage::getSingleton('checkoutapi/processador')->atualizarPagamentoRecorrente($profile, $record);
    				}
    			}
    		}
    	}
    	
    	if ($profile->getState() == Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE)
    		$result->setData('is_profile_active', true);
    	elseif ($profile->getState() == Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED)
    		$result->setData('is_profile_suspended', true);
    }
    
    /**
     * Whether can get recurring profile details
     */
    public function canGetRecurringProfileDetails()
    {
        return true;
    }
    
    /**
     * Update RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
    	
    }
    
    /**
     * Manage RP status
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile)
    {
        $params = Mage::app()->getRequest()->getParams();
        $action = $params['action'];
        
        switch($action) {
        	case 'cancel':
        		$responseMP = Mage::getSingleton('checkoutapi/api')->cancelRecurring($profile);
        		if (intval($responseMP->responseCode) != 0) {
        			Mage::helper('checkoutapi')->log('Erro no cancelamento do perfil recorrente - Codigo: ' . (string)$responseMP->responseCode, 'maxipago.log');
        			Mage::throwException('Ocorreu um erro no cancelamento do perfil recorrente!');
        		}
        		break;
        	case 'suspend':
        		$responseMP = Mage::getSingleton('checkoutapi/api')->updateRecurring($profile, false);
        		if (intval($responseMP->responseCode) != 0) {
        			Mage::helper('checkoutapi')->log('Erro na suspensão do perfil recorrente - Codigo: ' . (string)$responseMP->responseCode, 'maxipago.log');
        			Mage::throwException('Ocorreu um erro na suspensão do perfil recorrente!');
        		}
        		break;
        	case 'activate':
        		$responseMP = Mage::getSingleton('checkoutapi/api')->updateRecurring($profile, true);
        		if (intval($responseMP->responseCode) != 0) {
        			Mage::helper('checkoutapi')->log('Erro na ativação do perfil recorrente - Codigo: ' . (string)$responseMP->responseCode, 'maxipago.log');
        			Mage::throwException('Ocorreu um erro na ativação do perfil recorrente!');
        		}
        		break;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see MaxiPago_CheckoutApi_Model_Standard::order()
     */
    public function order(Varien_Object $payment, $amount)
    {
    	parent::order($payment, $amount);
    	
    	return $this;
    }
    
    /**
     * Capture the payment
     * 
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     */
    public function capture(Varien_Object $payment, $amount)
    {
    	parent::capture($payment, $amount);
    	
    	$order = $payment->getOrder();
    	$storeId = $order->getStoreId();
    	
    	// Já foi capturado
    	if ($order->getStatus() == Mage_Sales_Model_Order::STATE_PROCESSING) {
    		return $this;
    	}
    	
    	$responseMP = Mage::getSingleton('checkoutapi/api')->capture($order, $amount);
    	
    	if($responseMP && intval($responseMP->responseCode) == 0) {
    		
    		$state  = Mage_Sales_Model_Order::STATE_PROCESSING;
    		$status = true;
    		$authorizationTransaction = $payment->getAuthorizationTransaction();
    		$txnId = (string)$responseMP->transactionID;
    		
    		$payment->setData('maxipago_capture_timestamp', (string)$responseMP->transactionTimestamp);
    		$payment->setTransactionId($txnId);
    		$payment->setShouldCloseParentTransaction(true);
    		$payment->setParentTransactionId($authorizationTransaction->getTxnId());
    		$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, false,
    				Mage::helper('core')->__('Captura de %s.', round($amount, 2))
    				);
    		$order->setState($state, $status);
    		$order->setIsInProcess(true);
    		
    		$mensagem = 'maxiPago! - Aprovado. Pagamento confirmado.';
    		$this->enviarNotificacaoMudancaStatus($order, $mensagem);
    	}
    	else {
    		
    		if ($responseMP->errorMessage && trim((string)$responseMP->errorMessage) != '') {
    			Mage::helper('checkoutapi')->log('Erro Captura não efetuada - Codigo: '.(string)$responseMP->responseCode.'\nMensagem: '.(string)$responseMP->errorMessage, 'maxipago.log');
    			Mage::throwException('Ocorreu o seguinte erro na captura:\n'.(string)$responseMP->responseCode);
    		}
    		else {
	    		Mage::helper('checkoutapi')->log('Erro Captura não efetuada - Codigo: '.(string)$responseMP->responseCode, 'maxipago.log');
	    		Mage::throwException('Ocorreu um erro inesperado. Por favor aguarde alguns instantes e tente novamente!');
    		}
    	}
    	
    	return $this;
    }
    
    public function void(Varien_Object $payment) {
    	
    	parent::void($payment);
    	
    	return $this;
    }
    
    /**
     * Refunds the payment
     * @param Varien_Object $payment
     * @param unknown $amount
     */
    public function refund(Varien_Object $payment, $amount)
    {
    	parent::refund($payment, $amount);
    	
    	$order = $payment->getOrder();
    	$timestampCaptura = $payment->getData('maxipago_capture_timestamp');
    	$canVoid = date('Ymd') == date('Ymd', $timestampCaptura);
    	$processorId = $payment->getData('maxipago_processor_id');
    	$tipoEstorno = '';
    	$sucessoEstorno = true;
    	$chargeRefund = round($amount, 2);
    	
    	// void
    	if ($canVoid) {
    	
    		$invoice = Mage::helper('checkoutapi')->getCapturedInvoice($order);
    		$chargeTotal = round($invoice->getGrandTotal(), 2);
    		if (abs($chargeTotal - $chargeRefund) >= .0001) {
    		
    			Mage::throwException('Só é possível realizar uma operação de void com o valor total da fatura.');
    			return $this;
    		}
    		
    		$tipoEstorno = 'void';
    		$responseMP = Mage::getSingleton('checkoutapi/api')->void($order);
    		$sucessoEstorno = $responseMP && intval($responseMP->responseCode) == 0;
    	}
    	
    	// refund
    	if (!$canVoid || (!$sucessoEstorno && $processorId == '6')) {
    		
    		$tipoEstorno = 'refund';
    		$responseMP = Mage::getSingleton('checkoutapi/api')->returnPayment($order, $chargeRefund);
    		$sucessoEstorno = $responseMP && intval($responseMP->responseCode) == 0;
    	}
    	
    	if ($sucessoEstorno) {
    		
	    	// Se foi estorno ou o adquirente é a Cielo
    		if ($tipoEstorno == 'void' || $processorId == '4') {
    				
    			$mensagem = 'maxiPago! - Estorno. Pagamento Estornado.';
    		}
    		else {
    			
    			$mensagem = 'maxiPago! - Requisição de Estorno efetuada. Após alguns dias úteis o pagamento será estornado.';
    		}
    		
    		$this->enviarNotificacaoMudancaStatus($order, $mensagem);
    	}
    	else {
    	
    		if ($responseMP->errorMessage && trim((string)$responseMP->errorMessage) != '') {
    			Mage::helper('checkoutapi')->log('Erro Estorno não efetuado - Codigo: '.(string)$responseMP->responseCode.'\nMensagem: '.(string)$responseMP->errorMessage, 'maxipago.log');
    			Mage::throwException('Ocorreu o seguinte erro no estorno:\n'.(string)$responseMP->responseCode);
    		}
    		else {
    			Mage::helper('checkoutapi')->log('Erro Estorno não efetuado - Codigo: '.(string)$responseMP->responseCode, 'maxipago.log');
    			Mage::throwException('Ocorreu um erro inesperado. Por favor aguarde alguns instantes e tente novamente!');
    		}
    	}
    	
    	return $this;
    }
}