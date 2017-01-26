<?php

class MaxiPago_CheckoutApi_RedepayController extends Mage_Sales_Controller_Abstract
{
	const STATUS_APPROVED    = 'APPROVED';
	const STATUS_REVERSED    = 'REVERSED';
	const STATUS_IN_DISPUTE  = 'IN_DISPUTE';
	const STATUS_CHARGEBACK  = 'CHARGEBACK';
	const STATUS_IN_ANALISYS = 'IN_ANALISYS';
	const STATUS_DENIED      = 'DENIED';
	
	/**
	 * Success action.
	 */
	public function successAction()
	{
		$this->_initLayout();
	
		if (!$lastOrderId = $this->_getLastOrderId()) {
			$this->_redirectCart();
			return;
		}
	
		Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
	
		$this->_renderLayout();
		$this->_clearSessions();
	}
	
	/**
	 * Pending action.
	 */
	public function pendingAction()
	{
		$this->_initLayout();
	
		if (!$lastOrderId = $this->_getLastOrderId()) {
			$this->_redirectCart();
			return;
		}
	
		$this->_renderLayout();
		$this->_clearSessions();
	}
	
	/**
	 * Pending action.
	 */
	public function stateAction()
	{
		$this->_initLayout();
	
		if (!$lastOrderId = $this->_getLastOrderId()) {
			$this->_redirectCart();
			return;
		}
	
		$this->_renderLayout();
		$this->_clearSessions();
	}
	
	/**
	 * Error action.
	 */
	public function errorAction()
	{
		$this->_initLayout();
	
		if (!$lastOrderId = $this->_getLastOrderId()) {
			$this->_redirectCart();
			return;
		}
	
		$this->_renderLayout();
		$this->_clearSessions();
	}
	
	/**
	 * Error action.
	 */
	public function deniedAction()
	{
		$this->_initLayout();
	
		if (!$lastOrderId = $this->_getLastOrderId()) {
			$this->_redirectCart();
			return;
		}
	
		$this->_renderLayout();
		$this->_clearSessions();
	}
	
	/**
	 * Payment Action
	 */
	public function paymentAction()
	{
		$this->_initLayout();
	
		if (!$lastOrderId = $this->_getLastOrderId()) {
			$this->_redirectCart();
			return;
		}
		
		$this->_renderLayout();
	}
	
	/**
	 * Redirect Action
	 */
	public function redirectAction()
	{
		$hp_time = $this->getRequest()->getParam('hp_time');
		$hp_responsecode = $this->getRequest()->getParam('hp_responsecode');
		$hp_responsemsg = $this->getRequest()->getParam('hp_responsemsg');
		$hp_processortxnid = $this->getRequest()->getParam('hp_processortxnid');
		$hp_processorrefno = $this->getRequest()->getParam('hp_processorrefno');
		$hp_processorcode = $this->getRequest()->getParam('hp_processorcode');
		$hp_processormsg = $this->getRequest()->getParam('hp_processormsg');
		$hp_refnum = $this->getRequest()->getParam('hp_refnum');
		$hp_transid = $this->getRequest()->getParam('hp_transid');
		$hp_avsresponse = $this->getRequest()->getParam('hp_avsresponse');
		$hp_authcode = $this->getRequest()->getParam('hp_authcode');
		$hp_orderid = $this->getRequest()->getParam('hp_orderid');
		$hp_amount = $this->getRequest()->getParam('hp_amount');
		$hp_errormsg = $this->getRequest()->getParam('hp_errormsg');
		
		Mage::helper('checkoutapi')->log('Retorno da maxiPago! - RedePay', 'maxipago.log');
		Mage::helper('checkoutapi')->log('Parâmetros: ' . $this->getRequest()->getParams(), 'maxipago.log');
		
		$order = $this->_initOrder($hp_refnum);
		
		switch ($hp_responsecode)
		{
			case '0':
				/**
				 * @var string                        $orderState
				 * @var string                        $orderStatus
				 * @var Mage_Sales_Model_Order_Status $statusModel
				 */
				$orderState  = Mage_Sales_Model_Order::STATE_PROCESSING;
				$statusModel = Mage::getModel('sales/order_status')->loadDefaultByState($orderState);
				$orderStatus = $statusModel->getStatus() ? $statusModel->getStatus() : $orderState;
				
				$order->setIsInProcess(true);
				$order->setStatus($orderStatus);
					
				$mensagem = 'Pedido faturado pela maxiPago! - RedePay.';
				$order->addStatusHistoryComment($mensagem);
				
				Mage::getSingleton('checkoutapi/processador')
					->gerarFatura($order, $order->getPayment()->getTransactionId(), $mensagem);
				break;
			case '1':
			case '2':
				if (!$order->canCancel()) {
					$mensagem = 'Pedido cancelado pela !maxiPago - RedePay';
					
					$order->getPayment()->cancel();
					$order->registerCancellation($mensagem);
					
					Mage::dispatchEvent('order_cancel_after', array('order' => $this));
					
					$order->save();
				}
				break;
			case '5':
				/**
				 * @var string                        $orderState
				 * @var string                        $orderStatus
				 * @var Mage_Sales_Model_Order_Status $statusModel
				 */
				$orderState  = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
				$statusModel = Mage::getModel('sales/order_status')->loadDefaultByState($orderState);
				$orderStatus = $statusModel->getStatus() ? $statusModel->getStatus() : $orderState;
				$order->setStatus($orderStatus);
				$order->save();
				break;
			case '1022':
			case '1024':
			case '1025':
			case '2048':
			case '4097':
			default:
				if (!$order->canCancel()) {
					$mensagem = 'Error ao processar o pagamento pela !maxiPago - RedePay. Pedido cancelado.';
					$order->getPayment()->cancel();
					$order->registerCancellation($mensagem);
					
					Mage::dispatchEvent('order_cancel_after', array('order' => $this));
					
					$order->save();
				}
				break;
		}
		
		$this->_redirectByOrder($order);
	}
	
	/**
	 * Update Action
	 */
	public function updateAction()
	{
		$session = Mage::getSingleton('adminhtml/session');
		$key = $this->getRequest()->getParam('key');
		$orderId = $this->getRequest()->getParam('order_id');
		
		if (!$orderId)
		{
			$session->addError(Mage::helper('core')->__('Operação não permitida.'));
			$this->_redirectReferer();
			return;
		}
		
		$order = Mage::getModel('sales/order')->load($orderId);
		if (!$order->getId())
		{
			$session->addError(Mage::helper('core')->__('Operação não permitida.'));
			$this->_redirectReferer();
			return;
		}
		
		$mpOrderId = $order->getPayment()->getData('maxipago_token_transaction');
		if (!$mpOrderId)
		{
			$session->addError(Mage::helper('core')->__('Não é possível atualizar o pedido.'));
			$this->_redirectReferer();
			return;
		}
		
		try {				
			$responseMP = Mage::getSingleton('checkoutapi/api')->detailReport($mpOrderId);
			
			if (!$responseMP || !property_exists($responseMP, 'header') 
					|| (property_exists($responseMP, 'errorCode') && intval($responseMP->header->errorCode) != 0)) {
				if ($responseMP && property_exists($responseMP, 'header') && property_exists($responseMP->header, 'errorMsg'))
				{
					$msg = msg . ' ' . (string)$responseMP->header->errorMsg;
				}
				Mage::helper('checkoutapi')->log($msg);
				$session->addError(Mage::helper('core')->__($msg));
			}
			
			$methodInstance = $order->getPayment()->getMethodInstance();
			$pageToken = (string)$responseMP->result->resultSetInfo->pageToken;
			$numberOfPages = intval($responseMP->result->resultSetInfo->numberOfPages);
			$totalNumberOfRecords = intval($responseMP->result->resultSetInfo->totalNumberOfRecords);
			if($totalNumberOfRecords > 0) {
				$records = $responseMP->result->records->record;
				foreach($records as $record) {
					$methodInstance->updateOrder($order, (string)$record->transactionState, round($record->transactionAmount, 2));
				}
				 
				for($i = 2 ; $i <= $numberOfPages; $i++) {
					$responseMP = Mage::getSingleton('checkoutapi/api')->detailReport($pageToken, $i);
					$records = $responseMP->result->records->record;
					foreach($records as $record){
						$methodInstance->updateOrder($order, (string)$record->transactionState, round($record->transactionAmount, 2));
					}
				}
			}
		}
		catch (\Exception $ex) {
			$session->addError(Mage::helper('core')->__('Ocorreu um erro inesperado. Tente novamente mais tarde'));
			$this->_redirectReferer();
			return;
		}
		
		$session->addError(Mage::helper('core')->__('Pedido atualizado com sucesso.'));
		$this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId  , 'key' => $key));
	}
	
	/**
	 * @param bool|null|string $handles
	 * @param bool             $generateBlocks
	 * @param bool             $generateXml
	 *
	 * @return $this
	 */
	protected function _initLayout($handles = null, $generateBlocks = true, $generateXml = true)
	{
		$this->loadLayout($handles, $generateBlocks, $generateXml);
		$this->_title('maxiPago! - RedePay');
	
		return $this;
	}
	
	/**
	 * @param string $output
	 *
	 * @return $this
	 */
	protected function _renderLayout($output = '')
	{
		$this->renderLayout($output);
		return $this;
	}
	
	/**
	 * @return $this
	 */
	protected function _redirectCart()
	{
		$this->_redirect('checkout/cart');
		return $this;
	}
	
	/**
	 * @return $this
	 */
	protected function _clearSessions()
	{
		Mage::getSingleton('adminhtml/session')->clear();
		Mage::getSingleton('adminhtml/session')->unsetData('last_transaction_info');
	
		Mage::getSingleton('checkout/session')->clear();
		Mage::getSingleton('checkout/session')->unsetData('last_order_id');
		Mage::getSingleton('checkout/session')->unsetData('last_quote_id');
	
		return $this;
	}
	
	/**
	 * @return int
	 */
	protected function _getLastOrderId()
	{
		$lastOrderId = Mage::getSingleton('checkout/session')->getLastOrderId();
		if (empty($lastOrderId)) {
			$lastOrderId = Mage::getSingleton('adminhtml/session')->getLastOrderId();
		}
	
		return (int) $lastOrderId;
	}
	
	/**
	 * @return Mage_Sales_Model_Order
	 */
	protected function _initOrder($orderId = null)
	{
		if (!$orderId) {
			$this->_forward('noRoute');
			return false;
		}
	
		/** @var Mage_Sales_Model_Order $order */
		$order = Mage::registry('current_order');
		if (!order)
		{
			$order = Mage::getModel('sales/order')->load($orderId);
			Mage::register('current_order', $order);
		}
	
		if (!$order->getId()) {
			$this->_forward('noRoute');
			return false;
		}
	
		return $order;
	}
	
	/**
	 * @param Mage_Sales_Model_Order $order
	 *
	 * @return $this
	 */
	protected function _redirectByOrder(Mage_Sales_Model_Order $order)
	{
		if (!$order->getId()) {
			$this->_redirect('checkout/cart');
			return $this;
		}
	
		if ($order->isCanceled()) {
			$this->_redirectPaymentError();
			return $this;
		}
	
		switch ($order->getState()) {
			case Mage_Sales_Model_Order::STATE_NEW:
				$this->_redirect('*/redepay/pending');
				break;
			case Mage_Sales_Model_Order::STATE_CANCELED:
				$this->_redirect('*/redepay/error');
				break;
			case Mage_Sales_Model_Order::STATE_PROCESSING:
			case Mage_Sales_Model_Order::STATE_COMPLETE:
			case Mage_Sales_Model_Order::STATE_CLOSED:
			case Mage_Sales_Model_Order::STATE_HOLDED:
			case Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW:
			default:
				$this->_redirect('*/redepay/state');
				break;
		}
	
		return $this;
	}
}