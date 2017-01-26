<?php

class MaxiPago_CheckoutApi_Model_Redepay extends Mage_Payment_Model_Method_Abstract
{
	protected $_code          	= 'maxipagocheckoutapi_redepay';
	protected $_formBlockType 	= 'checkoutapi/form_redepay';
	protected $_infoBlockType 	= 'checkoutapi/info_redepay';
	protected $_standardType	= 'redepay';
	
	/**
	 * Availability options
	 */
	protected $_allowCurrencyCode 		= array('BRL');
	protected $_isGateway               = true;
	protected $_canOrder                = true;
	protected $_canRefund               = true;
	protected $_canRefundInvoicePartial = true;
	protected $_canUseInternal          = false;
	
	/**
	 * @return string
	 */
	public function getConfigPaymentAction()
	{
		$action = parent::getConfigPaymentAction();
		 
		if (is_null($action) || trim($action) === '') {
			return Mage_Payment_Model_Method_Abstract::ACTION_ORDER;
		}
		 
		return $action;
	}
	
	/**
	 * @return string
	 */
	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('checkoutapi/redepay/payment', array('_secure' => true, 'type' => 'redepay'));
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
		
		// Chama o auth da maxiPago
		$responseMP = Mage::getSingleton('checkoutapi/api')->auth($order);
		
		// Trata o error code
		$errorCode = $responseMP && property_exists($responseMP, 'errorCode') ? intval($responseMP->errorCode) : 0;
		if (is_null($responseMP) || $errorCode == 1) {
			$errorMsg = $responseMP && property_exists($responseMP, 'errorMsg') ? (string)$responseMP->errorMsg : 'Erro inesperado.';
			Mage::helper('checkoutapi')->log('Transação com erro: ' . $errorMsg, 'maxipago.log');
			Mage::throwException('Ocorreu um erro inesperado. Tente novamente ou escolha outro meio de pagamento.');
		}
		
		$responseCode = $responseMP && property_exists($responseMP, 'responseCode') ? intval($responseMP->responseCode) : -1;
		switch ($responseCode) {
			case 0:
				$state  = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
				$txnId = (string)$responseMP->transactionID;
				
				//consulta a transação
				$transactionInfo = Mage::getSingleton('checkoutapi/api')->consultTransaction($txnId);
				if ($transactionInfo && property_exists($transactionInfo, 'result') 
						&& property_exists($transactionInfo->result, 'records') 
						&& property_exists($transactionInfo->result->records, 'record') 
						&& isset($transactionInfo->result->records->record[0]))
				{
					$payment->setData('maxipago_token_transaction', (string)$transactionInfo->result->records->record[0]->orderId);
				}
				
				$payment->setData('maxipago_transaction_id', $txnId);
				$payment->setData('maxipago_processor_type', 'maxipagocheckoutapi_redepay');
				$payment->setData('maxipago_processor_id', '18');
				if (property_exists($responseMP, 'save-on-file') && property_exists($responseMP->{'save-on-file'}, 'token')) {
					$ccToken = $this->encrypt((string)$responseMP->{'save-on-file'}->token);
					$payment->setData('maxipago_cc_token', $ccToken);
				}
				$payment->resetTransactionAdditionalInfo();
				$payment->setTransactionId($txnId);
				
				Mage::getSingleton('core/session')->setResponseMp($responseMP->asXML());
				
				break;
				
			case 1:
				Mage::helper('checkoutapi')->log('Transação não autorizada', 'maxipago.log');
				Mage::throwException('Transação não autorizada');
				Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($order->getStoreId());
				break;
				
			case 1024:
			case -1:
				$errorMessage = $responseMP && property_exists($responseMP, 'errorMessage') ? (string)$responseMP->errorMessage : 'Erro inesperado.';
				Mage::helper('checkoutapi')->log('Transação com parâmetros inválidos: ' . $errorMessage, 'maxipago.log');
				Mage::throwException('Transação não autorizada: ' . $errorMessage);
				Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($order->getStoreId());
				break;
		}
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
		$canVoid = true;
		$processorId = $payment->getData('maxipago_processor_id');
		$tipoEstorno = '';
		$sucessoEstorno = true;
		$chargeRefund = round($amount, 2);
		
		if ($timestampCaptura) {
			$canVoid = date('Ymd') == date('Ymd', $timestampCaptura);
		}
		 
		// void
		if ($canVoid) {
			 
			$invoice = Mage::helper('checkoutapi')->getCapturedInvoice($order);
			if ($invoice) {
				$chargeTotal = round($invoice->getGrandTotal(), 2);
				if (abs($chargeTotal - $chargeRefund) >= .0001) {
		
					Mage::throwException('Só é possível realizar uma operação de void com o valor total da fatura.');
					return $this;
				}
			}
	
			$tipoEstorno = 'void';
			$responseMP = Mage::getSingleton('checkoutapi/api')->void($order);
			$sucessoEstorno = $responseMP && property_exists($responseMP, 'responseCode') && intval($responseMP->responseCode) == 0;
		}
		else {
	
			$tipoEstorno = 'refund';
			$responseMP = Mage::getSingleton('checkoutapi/api')->returnPayment($order, $chargeRefund);
			$sucessoEstorno = $responseMP && property_exists($responseMP, 'responseCode') && intval($responseMP->responseCode) == 0;
		}
		 
		if ($sucessoEstorno) {
	
			// Se foi estorno
			if ($tipoEstorno == 'void') {
	
				$mensagem = 'maxiPago! - Estorno. Pagamento Estornado.';
			}
			else {
				 
				$mensagem = 'maxiPago! - Requisição de Estorno efetuada. Após alguns dias úteis o pagamento será estornado.';
			}
	
			$this->enviarNotificacaoMudancaStatus($order, $mensagem);
		}
		else {
			 
			if (property_exists($responseMP, 'errorMessage') && trim((string)$responseMP->errorMessage) != '') {
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
	
	public function updateOrder($order, $stateCode, $actualAmount) {
		switch ($stateCode){
			case '3':
			case '10':
			case '35':
			case '36':
				if ($order->getStatus() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
					$captura = $stateCode == '3';
					Mage::getSingleton('checkoutapi/processador')->aprovar($order, $captura, null);
				}
				break;
			case '44':
				if ($order->getStatus() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
					Mage::getSingleton('checkoutapi/processador')->aprovar($order, true, null);
				}
				break;
			case '7':
			case '9':
			case '45':
				if ($order->getStatus() != Mage_Sales_Model_Order::STATE_COMPLETE
				&& $order->getStatus() != Mage_Sales_Model_Order::STATE_CLOSED
				&& $order->getStatus() != Mage_Sales_Model_Order::STATE_CANCELED
				&& $order->getStatus() != Mage_Sales_Model_Order::STATE_HOLDED) {
		
					$chargeTotal = round($order->getGrandTotal(), 2);
					$chargeRefund = round($actualAmount, 2);
					if (abs($chargeTotal - $chargeRefund) < .0001) {
						 
						Mage::getSingleton('checkoutapi/processador')->estornar($order, null, true);
					}
				}
				break;
		}
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