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

class MaxiPago_CheckoutApi_Model_Processador
{
	public function autorizar($order, $transactionMpId = null) {
		
// 		$payment = $order->getPayment();
// 		$payment->resetTransactionAdditionalInfo();
// 		$payment->setTransactionId($transactionMpId);
// 		$payment->setIsTransactionClosed(0);
// 		$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false,
// 				Mage::helper('core')->__('Autorização de %s.', round($payment->getAmount(), 2))
// 				);
		
		$mensagem = 'maxiPago! - Autorizado. Pagamento pendente de captura.';
		$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $mensagem);
		
		$this->enviarNotificacaoMudancaStatus($order, $mensagem, 'Pagamento Pendente.');
		
		$transactionSave = Mage::getModel('core/resource_transaction')
		->addObject($order);
		$transactionSave->save();
	}
	
	public function aprovar($order, $captura = false, $transactionMpId = null, $transactionMpTimestamp = null) {
		
		$payment = $order->getPayment();
		$state  = Mage_Sales_Model_Order::STATE_PROCESSING;
		$status = true;
		$transactionStatus = $captura ? Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE
			: Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER;
		$authorizationTransaction = $payment->getAuthorizationTransaction();
		
		$payment->setData('maxipago_capture_timestamp', $transactionMpTimestamp);
		if ($transactionMpId)
			$payment->setTransactionId($transactionMpId);
		$payment->setShouldCloseParentTransaction(true);
		if ($authorizationTransaction)
			$payment->setParentTransactionId($authorizationTransaction->getTxnId());
		$payment->addTransaction($transactionStatus, null, false,
				Mage::helper('core')->__('Pagamento de %s confirmado.', round($order->getGrandTotal(), 2))
				);
		
		$qtys = array();
		foreach ($order->getAllItems() as $item) {
			$qtys[$item->getId()] = $item->getQtyOrdered();
		}
		
		$mensagem = 'maxiPago! - Aprovado. Pagamento confirmado.';
		$invoice = $order->prepareInvoice($qtys);
		$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
		$invoice->setTransactionId($payment->getTransactionId());
		$invoice->addComment($mensagem);
		$invoice->register();
		$invoice->setCanVoidFlag(true);
		$invoice->sendEmail(true);
		
		$order->setState($state, $status);
		$order->setIsInProcess(true);
		 
		$transactionSave = Mage::getModel('core/resource_transaction')
		->addObject($invoice)
		->addObject($order)
		->addObject($payment);
		$transactionSave->save();
		
		$this->enviarNotificacaoMudancaStatus($order, $mensagem, 'Pagamento confirmado');
	}
	
	public function gerarFatura($order, $invoiceTxnId, $mensagem = null)
	{
		$qtys = array();
		foreach ($order->getAllItems() as $item) {
			$qtys[$item->getId()] = $item->getQtyOrdered();
		}
		
		if (!$mensagem)
			$mensagem = 'maxiPago! - Aprovado. Pagamento confirmado.';
		
		$invoice = $order->prepareInvoice($qtys);
		$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
		$invoice->setTransactionId($invoiceTxnId);
		$invoice->addComment($mensagem);
		$invoice->register();
		$invoice->setCanVoidFlag(true);
		$invoice->sendEmail(true);
		
		$transactionSave = Mage::getModel('core/resource_transaction')
		->addObject($invoice)
		->addObject($order);
		$transactionSave->save();
	}
	
	public function estornar($order, $transactionMpId = null, $offline = false) {
		
		$state  = Mage_Sales_Model_Order::STATE_CANCELED;
		$statusModel = Mage::getModel('sales/order_status')->loadDefaultByState($state);
		$orderStatus = $statusModel->getStatus() ? $statusModel->getStatus() : $state;
		$order->setStatus($orderStatus);
		
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
					->setOfflineRequested($offline)
					->setTransactionId($invoice->getTransactionId())
					->addComment('Pagamento estornado.')
					->register();
		
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
		
					$transactionSave->addObject($invoice);
					$transactionSave->addObject($creditmemo);
				}
			}
			else {
		
				/** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
				$creditmemo = $service->prepareCreditmemo()
				->setOfflineRequested($offline)
				->addComment('Pagamento estornado.')
				->register();
					
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
					
				$transactionSave->addObject($creditmemo);
			}
		}
		else {
			
			foreach ($order->getAllItems() as $orderItem) {
				if (!$orderItem->getParentItemId()) {
					$orderItem->setBackToStock(true);
				}
				$orderItem->cancel();
			}
		}
		
		$mensagem = 'maxiPago! - Estorno. Pagamento Estornado.';
		$this->enviarNotificacaoMudancaStatus($order, $mensagem, 'Pagamento estornado');
			
		$transactionSave->addObject($order)
		->save();
	}
	
	public function atualizarPagamentoRecorrente(Mage_Payment_Model_Recurring_Profile $profile, $responseMP) {
		$orderIds = $profile->getResource()->getChildOrderIds($profile);
		
		$maxCycles = $profile->getIniAmount() ? $profile->getPeriodMaxCycles() : $profile->getPeriodMaxCycles() + 1;
		if (count($orderIds) >= $maxCycles) {
			return;
		}
		
		$collection = Mage::getModel('sales/order')->getCollection()
		->join(
			array('payment' => 'sales/order_payment'),
			'main_table.entity_id = payment.parent_id',
			array('payment.maxipago_transaction_id')
			)
			->addAttributeToFilter('main_table.entity_id', array('in' => $orderIds));
		
		$collection->getSelect()
			->reset(Zend_Db_Select::COLUMNS)
			->columns('payment.maxipago_transaction_id');

		$result = $collection->toArray();
		 
		$tranIds = array();
		if ($result && $result['totalRecords'] > 0) {
			foreach ($result['items'] as $item) {
				$tranIds[] = $item['maxipago_transaction_id'];
			}
		}

		if (!in_array((string)$responseMP->transactionId, $tranIds)) {
			$this->aprovarPagamentoRecorrente($profile, $responseMP);
		}
		 
		if (count($tranIds) >= $maxCycles){
			$profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED);
			$profile->save();
		}
	}
	
	public function aprovarPagamentoRecorrente(Mage_Payment_Model_Recurring_Profile $profile, $responseMP) {
		
		$txnId = (string)$responseMP->transactionId;
		
        $productItemInfo = new Varien_Object;
		$productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_REGULAR);
		$productItemInfo->setPrice((string)$responseMP->transactionAmount);
		$productItemInfo->setShippingAmount(0);
		$productItemInfo->setTaxAmount(0);
		$productItemInfo->setIsVirtual(1);
		 
		$order = $profile->createOrder($productItemInfo);
		$order->save();
		$profile->addOrderRelation($order->getId());
		
		$transactionSave = Mage::getModel('core/resource_transaction');
		
		$payment = $order->getPayment();
		$payment->setData('maxipago_transaction_id', $txnId);
		$payment->setData('maxipago_token_transaction', (string)$responseMP->orderId);
		$payment->setData('maxipago_capture_timestamp', strtotime((string)$responseMP->transactionDate));
		$payment->setData('maxipago_fraud_score', (string)$responseMP->fraudScore);
		$payment->setTransactionId($txnId)->setIsTransactionClosed(1);
		
		$additionalInfo = $profile->getAdditionalInfo();
		if (is_string($additionalInfo))
			$additionalInfo = unserialize($additionalInfo);
		if (is_array($additionalInfo) && !empty($additionalInfo))
		{
			if (array_key_exists('cc_type', $additionalInfo))
				$payment->setCcType($additionalInfo['cc_type']);
			if (array_key_exists('cc_owner', $additionalInfo))
				$payment->setCcOwner($additionalInfo['cc_owner']);
			if (array_key_exists('cc_last4', $additionalInfo))
				$payment->setCcLast4($additionalInfo['cc_last4']);
			if (array_key_exists('cc_number', $additionalInfo))
				$payment->setCcNumber($additionalInfo['cc_number']);
		}
		 
		$transaction= Mage::getModel('sales/order_payment_transaction');
		$transaction->setTxnId($txnId);
		$transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
		$transaction->setPaymentId($payment->getId());
		$transaction->setOrderId($order->getId());
		$transaction->setOrderPaymentObject($payment);
		$transaction->setIsClosed(1);
		
		$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
		$order->setIsInProcess(true);
		
		$transactionSave->addObject($profile)
		->addObject($order)
		->addObject($payment)
		->addObject($transaction)
		->save();
		
		$this->gerarFatura($order, $payment->getTransactionId());
		
		$this->enviarNotificacaoMudancaStatus($order, 'Pagamento Recorrente Processado.', 'Pagamento Recorrente');
	}
	
	public function enviarNotificacaoMudancaStatus($order, $mensagem, $titulo) {
		$payment = $order->getPayment();
		$emailStatus = explode(',', $payment->getMethodInstance()->getConfigData('emailStatus'));
		$sendEmail = in_array($order->getStatus(), $emailStatus) ? true :  false;
		$order->addStatusToHistory(
				$order->getStatus(),
				Mage::helper('checkoutapi')->__($mensagem),
				$sendEmail
				);
		if($sendEmail) { $order->sendOrderUpdateEmail($sendEmail, $titulo); }
	}
}