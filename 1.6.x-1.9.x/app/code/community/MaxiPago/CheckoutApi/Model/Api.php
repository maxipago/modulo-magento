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

class MaxiPago_CheckoutApi_Model_Api
{
	const POST_XML = 'POST_XML';
	const POST_API = 'POST_API';
	const REPORTS_API = 'REPORTS_API';
	
	protected function encrypt($data)
	{
		if ($data) {
			return Mage::helper('core')->encrypt($data);
		}
		
		return $data;
	}
    
    protected function decrypt($data)
    {
        if ($data) {
            return Mage::helper('core')->decrypt($data);
        }

        return $data;
    }
    
    protected function getPostApiUrl($sandbox) {
    	if ($sandbox == '1') {
    		return 'https://testapi.maxipago.net/UniversalAPI/postAPI';
    	}
    	else {
    		return 'https://api.maxipago.net/UniversalAPI/postAPI';
    	}
    }
    
    protected function getPostXmlUrl($sandbox) {
    	if ($sandbox == '1') {
    		return 'https://testapi.maxipago.net/UniversalAPI/postXML';
    	}
    	else {
    		return 'https://api.maxipago.net/UniversalAPI/postXML';
    	}
    }
    
    protected function getReportsApiUrl($sandbox) {
    	if ($sandbox == '1') {
    		return 'https://testapi.maxipago.net/ReportsAPI/servlet/ReportsAPI';
    	}
    	else {
    		return 'https://api.maxipago.net/ReportsAPI/servlet/ReportsAPI';
    	}
    }
    
    public function sendMaxiPagoRequest($params = '', $sandbox = '1', $type = self::POST_XML)
    {
    	$url = '';
    	if($type == self::POST_XML) {
    		$url = $this->getPostXmlUrl($sandbox);
    		Mage::helper('checkoutapi')->log('URL de Request: ' . $url, 'maxipago.log');
    		$ch = curl_init($url);
    	}
    	elseif($type == self::POST_API) {
    		$url = $this->getPostApiUrl($sandbox);
    		Mage::helper('checkoutapi')->log('URL de Request: ' . $url, 'maxipago.log');
    		$ch = curl_init($url);
    	}
    	elseif($type == self::REPORTS_API) {
    		$url = $this->getReportsApiUrl($sandbox);
    		Mage::helper('checkoutapi')->log('URL de Request: ' . $url, 'maxipago.log');
    		$ch = curl_init($url);
    	}
        

    	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml; charset=utf-8'));
    	curl_setopt($ch, CURLOPT_ENCODING, '');
    
    	$xml = $params;
    	if ($type == self::POST_XML) {
    		$xml = preg_replace('/<number>(.*)<\/number>/m', '<number>*****</number>', $xml);
    		$xml = preg_replace('/<cvvNumber>(.*)<\/cvvNumber>/m', '<cvvNumber>***</cvvNumber>', $xml);
    		$xml = preg_replace('/<token>(.*)<\/token>/m', '<token>***</token>', $xml);
    	}
    	elseif ($type == self::POST_API) {
    		$xml = preg_replace('/<creditCardNumber>(.*)<\/creditCardNumber>/m', '<creditCardNumber>*****</creditCardNumber>', $xml);
    	}
    	Mage::helper('checkoutapi')->log('Request: '. $xml, 'maxipago.log');
    
    	$res = curl_exec($ch);
    
    	if (!$res) {
    		Mage::helper('checkoutapi')->log('Error: Erro na execucao! ', 'maxipago.log');
    		if(curl_errno($ch)){
    			Mage::helper('checkoutapi')->log('Error '.curl_errno($ch).': '. curl_error($ch), 'maxipago.log');
    		}else{
    			Mage::helper('checkoutapi')->log('Error : '. curl_error($ch), 'maxipago.log');
    		}
    
    		Mage::throwException("Ocorreu um erro inesperado. Por favor tente novamente em alguns instantes!");
    		curl_close ( $ch );
    		exit();
    	}
    
    	$httpCode = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
    
    	if ($httpCode != "200") {
    		Mage::helper('checkoutapi')->log('Error: Erro de requisicao em: ' . $url, 'maxipago.log');
    		http::httpError('Erro de requisicao em: ' . $url);
    		Mage::throwException('Ocorreu um erro inesperado. Por favor aguarde alguns instantes e tente novamente!');
    	}
    
    	if(curl_errno($ch)){
    		Mage::helper('checkoutapi')->log('Error: Erro de conexão: ' . curl_error($ch), 'maxipago.log');
    		http::httpError("Erro de conexão: " . curl_error($ch));
    		Mage::throwException("Ocorreu um erro inesperado. Por favor aguarde alguns instantes e tente novamente!");
    	}
    	curl_close($ch);
    
    	Mage::helper('checkoutapi')->log('HttpCode: '. $httpCode, 'maxipago.log');
    	$xml = $res;
    	$xml = preg_replace('/<token>(.*)<\/token>/m', '<token>*****</token>', $xml);
    	Mage::helper('checkoutapi')->log('Response: '. $xml, 'maxipago.log');
    	
    	return $res;
    }
    
    public function auth($order) {

    	$storeId = $order->getStoreId();
    	$sellerId = Mage::helper('checkoutapi')->getGlobalConfig('sellerId', $storeId);
    	$sellerKey = Mage::helper('checkoutapi')->getGlobalConfig('sellerKey', $storeId);
    	$secretKey = Mage::helper('checkoutapi')->getGlobalConfig('secretKey', $storeId);
    	$prefixo = Mage::helper('checkoutapi')->getPaymentConfig($order->getPayment(), 'prefixo', $storeId);
    	$orderIncrementId = $order->getIncrementId();
    	$sandbox = Mage::helper('checkoutapi')->getGlobalConfig('sandbox', $storeId);
    	$paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
    	$chargeTotal = number_format($order->getGrandTotal(), 2, '.', '');
    	$ccType = $order->getPayment()->getData('cc_type');
    	$billingAddress = $order->getBillingAddress();
    	$shippingAddress = $order->getIsVirtual() ? $order->getBillingAddress() : $order->getShippingAddress();
    	
    	$transaction = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transaction-request/>');
    	$transaction->addChild('version', '3.1.1.15');
        
    	$verification = $transaction->addChild('verification');
    	$verification->addChild('merchantId', $sellerId);
    	$verification->addChild('merchantKey', $sellerKey);
    	
    	$orderXml = $transaction->addChild('order');
    	if($paymentMethod == 'maxipagocheckoutapi_creditcard'){
    		$processorType = Mage::helper('checkoutapi')->getPaymentConfig($order->getPayment(), 'processorType', $storeId);
    		if($processorType == 'authM') {
    			$processorType = 'auth';
    		}
    		$sale = $orderXml->addChild($processorType);
			$processorId = $order->getPayment()->getMethodInstance()->getProcessor($ccType, $storeId);
			if ($processorId != null) {
    			$sale->addChild('processorID', $processorId);
			}
    	}
    	else {
    		$sale = $orderXml->addChild('sale');
    		if($paymentMethod == 'maxipagocheckoutapi_bankslip') {
    			$sale->addChild('processorID', Mage::helper('checkoutapi')->getPaymentConfig($order->getPayment(), 'bankSlip', $storeId));
    		}
    		elseif($paymentMethod == 'maxipagocheckoutapi_tef') {
    			$sale->addChild('processorID', $ccType);
    		}
    		elseif ($paymentMethod == 'maxipagocheckoutapi_redepay') {
    			$sale->addChild('processorID', '18');
    		}
    	}
    	 
    	$sale->addChild('referenceNum', $prefixo.$orderIncrementId);
    	
    	$remoteAddr = Mage::helper('core/http')->getRemoteAddr();
    	$sale->addChild('ipAddress', $remoteAddr);
    	
    	$fraudCheck = Mage::helper('checkoutapi')->getFraudCheck($order);
    	$sale->addChild('fraudCheck', $fraudCheck);
    	 
    	$shipping = $sale->addChild('shipping');
    	if ($order->getCustomerId())
    		$shipping->addChild('id', $order->getCustomerId());
    	else
    		$shipping->addChild('id', 0);
    	$shipping->addChild('name', $order->getData('customer_firstname') . ' ' . str_replace('(pj)', '', $order->getData('customer_lastname')));
    	$shipping->addChild('address', $shippingAddress->getStreet(1));
    	 
    	if($shippingAddress->getStreet(2) != null) {
    		$shipping->addChild('address2', $shippingAddress->getStreet(2));
    	}
    	else {
    		$shipping->addChild('address2', '-');
    		
    	}
    	if($billingAddress->getStreet(3) != NULL) {
    		$shipping->addChild('district', $shippingAddress->getStreet(3));
    	}
    	else {
    		$shipping->addChild('district', '-');
    	}
    	$shipping->addChild('city', $shippingAddress->getCity());
    	$shipping->addChild('state', ($shippingAddress->getRegionCode() != '') ? $shippingAddress->getRegionCode() : $shippingAddress->getRegion());
    	$shipping->addChild('postalcode', trim(str_replace('-', '', $shippingAddress->getPostcode())));
    	$shipping->addChild('country', 'BR');
    	$shipping->addChild('type', 'Individual');
    	$shipping->addChild('gender', $order->getCustomerGender() == '1' ? 'M' : 'F');
    	$shipping->addChild('email', $shippingAddress->getEmail());
    	$shipping->addChild('birthDate', date('Y-m-d', strtotime($order->getCustomerDob())));
    	
    	$shippingPhones = null;
    	$shippingPhoneNumber = explode(' ', $shippingAddress->getTelephone());
    	$shippingPhoneNumber = array_reverse($shippingPhoneNumber);
    	if (is_array($shippingPhoneNumber) && count($shippingPhoneNumber) > 0)
    	{
    		$shippingPhones = $shipping->addChild('phones');
    		$shippingPhone = $shippingPhones->addChild('phone');
    		$shippingPhone->addChild('phoneType', 'Residential');
    		if (array_key_exists(0, $shippingPhoneNumber))
    			$shippingPhone->addChild('phoneNumber', preg_replace('/[^0-9]/', '', $shippingPhoneNumber[0]));
    		if (array_key_exists(1, $shippingPhoneNumber))
    			$shippingPhone->addChild('phoneAreaCode', preg_replace('/[^0-9]/', '', $shippingPhoneNumber[1]));
    		if (array_key_exists(2, $shippingPhoneNumber))
    			$shippingPhone->addChild('phoneCountryCode', preg_replace('/[^0-9]/', '', $shippingPhoneNumber[2]));
    	}
    	
    	if ($shippingAddress->getFax()) {
    		$shippingMobileNumber = explode(' ', $shippingAddress->getFax());
    		$shippingMobileNumber = array_reverse($shippingMobileNumber);
    		if (is_array($shippingMobileNumber) && count($shippingMobileNumber) > 0)
    		{
    			if (is_null($shippingPhones))
    				$shipping->addChild('phones');
    			$shippingMobile = $shippingPhones->addChild('phone');
    			$shippingMobile->addChild('phoneType', 'Mobile');
    			if (array_key_exists(0, $shippingMobileNumber))
    				$shippingMobile->addChild('phoneNumber', preg_replace('/[^0-9]/', '', $shippingMobileNumber[0]));
    			if (array_key_exists(1, $shippingMobileNumber))
    				$shippingMobile->addChild('phoneAreaCode', preg_replace('/[^0-9]/', '', $shippingMobileNumber[1]));
    			if (array_key_exists(2, $shippingMobileNumber))
    				$shippingMobile->addChild('phoneCountryCode', preg_replace('/[^0-9]/', '', $shippingMobileNumber[2]));
    		}
    	}
    	
    	// Adiciona a informação do CPF
    	$documents = $shipping->addChild('documents');
    	$documents->addAttribute('documentCount', '1');
    	$document = $documents->addChild('document');
    	$document->addChild('documentIndex', '1');
    	$document->addChild('documentType', 'CPF');
    	$document->addChild('documentValue', Mage::helper('checkoutapi')->getDocument($order));
    	 
        
        $sale->addChild('customerIdExt',$order->getData("customer_taxvat"));
        
        
    	$billing = $sale->addChild('billing');
    	if ($order->getCustomerId())
    		$billing->addChild('id', $order->getCustomerId());
    	else
    		$billing->addChild('id', 0);
    	$billing->addChild('name', $order->getData('customer_firstname') . ' ' . str_replace('(pj)', '', $order->getData('customer_lastname')));
    	$billing->addChild('address', $billingAddress->getStreet(1));
    	if($billingAddress->getStreet(2) != NULL) {
    		$billing->addChild('address2', $billingAddress->getStreet(2));
    	}else {
    		$billing->addChild('address2', '-');
    	}
    	if($billingAddress->getStreet(3) != NULL) {
    		$billing->addChild('district', $billingAddress->getStreet(3));
    	}
    	else {
    		$billing->addChild('district', '-');
    	}
    	$billing->addChild('city', $billingAddress->getCity());
    	$billing->addChild('state', ($billingAddress->getRegionCode() != '') ? $billingAddress->getRegionCode() : $billingAddress->getRegion());
    	$billing->addChild('postalcode', trim(str_replace('-', '', $billingAddress->getPostcode())));
    	$billing->addChild('country', 'BR');
    	$billing->addChild('type', 'Individual');
    	$billing->addChild('gender', $order->getCustomerGender() == '1' ? 'M' : 'F');
    	$billing->addChild('email', $billingAddress->getEmail());
    	$billing->addChild('birthDate', date('Y-m-d', strtotime($order->getCustomerDob())));
    	
    	$billingPhones = null;
    	$billingPhoneNumber = explode(' ', $billingAddress->getTelephone());
    	$billingPhoneNumber = array_reverse($billingPhoneNumber);
    	if (is_array($billingPhoneNumber) && count($billingPhoneNumber) > 0)
    	{
    		$billingPhones = $billing->addChild('phones');
    		$billingPhone = $billingPhones->addChild('phone');
    		$billingPhone->addChild('phoneType', 'Residential');
    		if (array_key_exists(0, $billingPhoneNumber))
    			$billingPhone->addChild('phoneNumber', preg_replace('/[^0-9]/', '', $billingPhoneNumber[0]));
    		if (array_key_exists(1, $billingPhoneNumber))
    			$billingPhone->addChild('phoneAreaCode', preg_replace('/[^0-9]/', '', $billingPhoneNumber[1]));
    		if (array_key_exists(2, $billingPhoneNumber))
    			$billingPhone->addChild('phoneCountryCode', preg_replace('/[^0-9]/', '', $billingPhoneNumber[2]));
    	}
    	
    	if ($billingAddress->getFax()) {
    		$billingMobileNumber = explode(' ', $billingAddress->getFax());
    		$billingMobileNumber = array_reverse($billingMobileNumber);
    		if (is_array($billingMobileNumber) && count($billingMobileNumber) > 0)
    		{
    			if (is_null($billingPhones))
    				$billingPhones = $billing->addChild('phones');
    			$billingMobile = $billingPhones->addChild('phone');
    			$billingMobile->addChild('phoneType', 'Mobile');
    			if (array_key_exists(0, $billingMobileNumber))
    				$billingMobile->addChild('phoneNumber', preg_replace('/[^0-9]/', '', $billingMobileNumber[0]));
    			if (array_key_exists(1, $billingMobileNumber))
    				$billingMobile->addChild('phoneAreaCode', preg_replace('/[^0-9]/', '', $billingMobileNumber[1]));
    			if (array_key_exists(2, $billingMobileNumber))
    				$billingMobile->addChild('phoneCountryCode', preg_replace('/[^0-9]/', '', $billingMobileNumber[2]));
    		}
    	}
    	
    	// Adiciona a informação do CPF
        
    	$documents = $billing->addChild('documents');
    	$documents->addAttribute('documentCount', '1');
    	$document = $documents->addChild('document');
    	$document->addChild('documentIndex', '1');
    	$document->addChild('documentType', 'CPF');
    	$document->addChild('documentValue', Mage::helper('checkoutapi')->getDocument($order));
    	
    	if ($fraudCheck == 'Y') {
    		$fraudProcessorId = Mage::helper('checkoutapi')->getPaymentConfig($order->getPayment(), 'fraudProcessor', $storeId);
    		$captureOnLowRisk = Mage::helper('checkoutapi')->getPaymentConfig($order->getPayment(), 'captureOnLowRisk', $storeId) ? 'Y' : 'N';
    		$voidOnHighRisk = Mage::helper('checkoutapi')->getPaymentConfig($order->getPayment(), 'voidOnHighRisk', $storeId) ? 'Y' : 'N';
    		$salesChannel = substr(Mage::helper('checkoutapi')->getPaymentConfig($order->getPayment(), 'salesChannel', $storeId), 0, 8);
    		
    		$fraudDetails = $sale->addChild('fraudDetails');
    		$fraudDetails->addChild('fraudProcessorID', $fraudProcessorId);
    		$fraudDetails->addChild('captureOnLowRisk', $captureOnLowRisk);
    		$fraudDetails->addChild('voidOnHighRisk', $voidOnHighRisk);
    		if ($fraudProcessorId == '97' || $fraudProcessorId == '98')
    		{
    			$sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
    			$fraudDetails->addChild('fraudToken', $sessionId);
    		}
    		elseif ($fraudProcessorId == '99')
    		{
    			$hash = hash_hmac('md5', $sellerId.'*'.$prefixo.$orderIncrementId, $secretKey);
    			$fraudDetails->addChild('fraudToken', $hash);
    		}
    		$fraudDetails->addChild('websiteId', $salesChannel);
    	}
    	
    	$transactionDetail = $sale->addChild('transactionDetail');
    	$payType = $transactionDetail->addChild('payType');
    	$isCcToken = $order->getPayment()->getMethodInstance()->getConfigData('isCCtoken');
    	$ccToken = $order->getPayment()->getData('maxipago_cc_token');
    	if($paymentMethod == 'maxipagocheckoutapi_creditcard') {
    		if($isCcToken && !empty($ccToken)) {
    			$onFile = $payType->addChild('onFile');
    			$userLogged = Mage::getSingleton('customer/session')->getCustomer();
    			$onFile->addChild('customerId', $userLogged->getData('maxipago_customer_id'));
    			$onFile->addChild('token', $this->decrypt($ccToken));
    			if ($order->getPayment()->getData('cc_cid')) {
    				$onFile->addChild('cvvNumber', $order->getPayment()->getData('cc_cid'));
    			}
    			else {
    				$onFile->addChild('cvvNumber', $this->decrypt($order->getPayment()->getData('cc_cid_enc')));
    			}
    		}
    		else {
    			$creditCard = $payType->addChild('creditCard');
    			if ($order->getPayment()->getData('cc_number')) {
    				$creditCard->addChild('number', $order->getPayment()->getData('cc_number'));
    			}
    			else {
    				$creditCard->addChild('number', $this->decrypt($order->getPayment()->getData('cc_number_enc')));
    			}
    			$creditCard->addChild('expMonth', str_pad($order->getPayment()->getData('cc_exp_month'), 2, '0', STR_PAD_LEFT));
    			$creditCard->addChild('expYear', $order->getPayment()->getData('cc_exp_year'));
    			if ($order->getPayment()->getData('cc_cid')) {
    				$creditCard->addChild('cvvNumber', $order->getPayment()->getData('cc_cid'));
    			}
    			else {
    				$creditCard->addChild('cvvNumber', $this->decrypt($order->getPayment()->getData('cc_cid_enc')));
    			}
    		}    		 
    	}
    	elseif($paymentMethod == 'maxipagocheckoutapi_tef') {
    		//$sale->addChild('customerIdExt',$order->getData("customer_taxvat"));
    		$onlineDebit = $payType->addChild('onlineDebit');
    		$onlineDebit->addChild('parametersURL', '?id=' . $prefixo . $orderIncrementId);
    	}
    	elseif($paymentMethod == 'maxipagocheckoutapi_bankslip') {
    		$boleto = $payType->addChild('boleto');
    		$dayPayment = Mage::helper('checkoutapi')->getPaymentConfig($order->getPayment(), 'daypayment', $storeId);
    		$boleto->addChild('expirationDate', date('Y-m-d', strtotime('+' . $dayPayment . ' days')));
                
                
    		if($ccType == '11') {
    			$boleto->addChild('number', $ccType . substr($orderIncrementId, -6));
    		}
    		else {
    			$boleto->addChild('number', $ccType . substr($orderIncrementId, -8));
    		}
    	}
    	elseif($paymentMethod == 'maxipagocheckoutapi_redepay') {
    		$eWallet = $payType->addChild('eWallet');
    		$eWallet->addChild('parametersURL', 'type=redepay');
    	}
    	 
    	$paymentXml = $sale->addChild('payment');
    	$paymentXml->addChild('currencyCode', Mage::app()->getStore()->getCurrentCurrencyCode());
    	if($paymentMethod == 'maxipagocheckoutapi_creditcard') {
    		$paymentXml->addChild('softDescriptor', Mage::helper('checkoutapi')->getPaymentConfig($order->getPayment(), 'softDescriptor', $storeId));
    		
    		$splitNumber = intval($order->getPayment()->getData('maxipago_split_number'));
    		if ($splitNumber > 1) {
    			$creditInstallment = $paymentXml->addChild('creditInstallment');
    			$creditInstallment->addChild('numberOfInstallments', $splitNumber);
    			$chargeInterest = Mage::helper('checkoutapi')->getPaymentConfig($order->getPayment(), 'chargeInterest', $storeId);
    			$creditInstallment->addChild('chargeInterest', $chargeInterest);
    		}
    	}
    	$paymentXml->addChild('chargeTotal', $chargeTotal);
    	if($paymentMethod == 'maxipagocheckoutapi_redepay') {
    		$paymentXml->addChild('shippingTotal', number_format($order->getShippingAmount(), 2, '.', ''));
    	}
    	
    	// Adiciona as informações dos produtos
    	$itemIndex = 1;
    	$countItems = count($order->getAllVisibleItems());
    	if ($countItems > 0) {    	
	    	$itemList = $sale->addChild('itemList');
	    	$itemList->addAttribute('itemCount', $countItems);
	    	foreach ($order->getAllVisibleItems() as $product) {
	    		$item = $itemList->addChild('item');
	    		$item->addChild('itemIndex', $itemIndex);
	    		$item->addChild('itemProductCode', $product->getSku());
	    		$item->addChild('itemDescription', $product->getName());
	    		$item->addChild('itemQuantity', intval($product->getQtyOrdered()));
	    		$item->addChild('itemTotalAmount', number_format($product->getRowTotal(), 2, '.', ''));
	    		$item->addChild('itemUnitCost', number_format($product->getOriginalPrice(), 2, '.', ''));
	    		
	    		$itemIndex++;
	    	}
    	}
    	
    	$session = Mage::getSingleton('customer/session', array('name' => 'frontend'));
    	if($paymentMethod == 'maxipagocheckoutapi_creditcard' && $session->isLoggedIn() && $isCcToken && empty($ccToken)) {
    		$customer = $session->getCustomer();
    		$mpCustomerId = $customer->getData('maxipago_customer_id');
    		
    		if (empty($mpCustomerId)) {
    			try {
    				$responseCustomerMP = $this->addConsumer($customer);
    				if ($responseCustomerMP && $responseCustomerMP->errorCode == 0) {
    					$mpCustomerId = $responseCustomerMP->result->customerId;
    					$customer->setData('maxipago_customer_id', $mpCustomerId);
    					$customer->save();
    				}
    			}
    			catch (\Exception $e) {
    				Mage::helper('checkoutapi')->log('Erro ao adicionar o usuário na maxiPago!.', 'maxipago.log');
    			}
    		}
    		
    		if(!empty($mpCustomerId)) {
    			$saveOnFile = $sale->addChild('saveOnFile');
    			$saveOnFile->addChild('customerToken', $mpCustomerId);
    		}
    	}

    	$responseXml = $this->sendMaxiPagoRequest($transaction->asXML(), $sandbox);
    	 
    	$responseMP = simplexml_load_string($responseXml);
    	
    	return $responseMP;
    }
    
    public function capture($order, $amount) {
    	Mage::helper('checkoutapi')->log('Capturando a transação', 'maxipago.log');
    	
    	$storeId = $order->getStoreId();
    	$sandbox = Mage::helper('checkoutapi')->getGlobalConfig('sandbox', $storeId);
    	$prefixo = Mage::helper('checkoutapi')->getPaymentConfig($order->getPayment(), 'prefixo', $storeId);
    	$chargeTotal = number_format($amount, 2, '.', '');
    	
    	$transactionCapture = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transaction-request/>');
    	$transactionCapture->addChild('version', '3.1.1.15');
    	 
    	$verificationCapture = $transactionCapture->addChild('verification');
    	$verificationCapture->addChild('merchantId', Mage::helper('checkoutapi')->getGlobalConfig('sellerId', $storeId));
    	$verificationCapture->addChild('merchantKey', Mage::helper('checkoutapi')->getGlobalConfig('sellerKey', $storeId));
    	
    	$orderCapture = $transactionCapture->addChild('order');
    	
    	$capture = $orderCapture->addChild('capture');
    	$capture->addChild('orderID', $order->getPayment()->getData('maxipago_token_transaction'));
    	$capture->addChild('referenceNum', $prefixo . $order->getIncrementId());
    	
    	$paymentXml = $capture->addChild('payment');
    	$paymentXml->addChild('chargeTotal', $chargeTotal);
    	
    	$responseXml = $this->sendMaxiPagoRequest($transactionCapture->asXML(), $sandbox);
    	 
    	$responseMP = simplexml_load_string($responseXml);
    	
    	return $responseMP;
    }
    
    public function void($order) {
    	Mage::helper('checkoutapi')->log('Realizando o void da transação', 'maxipago.log');
    	
    	$storeId = $order->getStoreId();
    	$sandbox = Mage::helper('checkoutapi')->getGlobalConfig('sandbox', $storeId);
    	
    	$transaction = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transaction-request/>');
    	$transaction->addChild('version', '3.1.1.15');
    	
    	$verification = $transaction->addChild('verification');
    	$verification->addChild('merchantId', Mage::helper('checkoutapi')->getGlobalConfig('sellerId', $storeId));
    	$verification->addChild('merchantKey', Mage::helper('checkoutapi')->getGlobalConfig('sellerKey', $storeId));
    	
    	$orderXml = $transaction->addChild('order');
    	
    	$void = $orderXml->addChild('void');
    	$void->addChild('transactionID', $order->getPayment()->getData('maxipago_transaction_id'));
    	
    	$responseXml = $this->sendMaxiPagoRequest($transaction->asXML(), $sandbox);
    	
    	$responseMP = simplexml_load_string($responseXml);
    	 
    	return $responseMP;
    }
    
    public function returnPayment($order, $amount)
    {
    	Mage::helper('checkoutapi')->log('Estornando a transação', 'maxipago.log');
    	
    	$storeId = $order->getStoreId();
    	$sandbox = Mage::helper('checkoutapi')->getGlobalConfig('sandbox', $storeId);
    	$chargeTotal = number_format($amount, 2, '.', '');
    	
    	$transaction = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transaction-request/>');
    	$transaction->addChild('version', '3.1.1.15');
    	
    	$verification = $transaction->addChild('verification');
    	$verification->addChild('merchantId', Mage::helper('checkoutapi')->getGlobalConfig('sellerId', $storeId));
    	$verification->addChild('merchantKey', Mage::helper('checkoutapi')->getGlobalConfig('sellerKey', $storeId));
    	
    	$orderXML = $transaction->addChild('order');
    	
    	$return = $orderXML->addChild('return');
    	$return->addChild('orderID', $order->getPayment()->getData("maxipago_token_transaction"));
    	$return->addChild('referenceNum', Mage::getStoreConfig('payment/'.$order->getPayment()->getMethodInstance()->getCode().'/prefixo', $storeId).$order->getIncrementId());
    	
    	$payment = $return->addChild('payment');
    	$payment->addChild('chargeTotal', $chargeTotal);
    	
    	$responseXml = $this->sendMaxiPagoRequest($transaction->asXML(), $sandbox);
    	 
    	$responseMP = simplexml_load_string($responseXml);
    	
    	return $responseMP;
    }
    
    public function recurringPayment(Mage_Payment_Model_Recurring_Profile $profile,
                                           Mage_Payment_Model_Info $paymentInfo) {
    	Mage::helper('checkoutapi')->log('Criando um pagamento recorrente', 'maxipago.log');
    	
    	$quote = $profile->getQuote();
    	$storeId = $profile->getData('store_id');
    	$sandbox = Mage::helper('checkoutapi')->getGlobalConfig('sandbox', $storeId);
    	$ccType = $quote->getPayment()->getData('cc_type');
		$processorId = $quote->getPayment()->getMethodInstance()->getProcessor($ccType, $storeId);
    	$prefixo = Mage::helper('checkoutapi')->getPaymentConfig($quote->getPayment(), 'prefixo', $storeId);
    	$chargeTotal = number_format($profile->getTaxAmount() + $profile->getBillingAmount() + $profile->getShippingAmount(), 2, '.', '');
    	//$chargeRecurring = ceil(100 * $chargeTotal / $profile->getData('period_max_cycles')) / 100;
    	$isOrderVirtual = $quote->getIsVirtual();
    	$billingAddress = $quote->getBillingAddress();
    	$shippingAddress = $isOrderVirtual ? $quote->getBillingAddress() : $quote->getShippingAddress();
    	
    	$session = Mage::getSingleton('customer/session', array('name' => 'frontend'));
    	$customer = $session->getCustomer();
    	$taxvat = $customer->getData('taxvat');
    	if (empty($taxvat)) {
    		$taxvat = $shippingAddress->getData('taxvat');
    		if (empty($taxvat)) {
    			$taxvat = $quote->getCustomerTaxvat();
    		}
    	}
    	$taxvat = preg_replace('/[^0-9]/', '', $taxvat);
    	
    	$phoneNumber = str_replace(' ', '', $shippingAddress->getTelephone());
    	$phoneNumber = str_replace('(', '', $phoneNumber);
    	$phoneNumber = str_replace(')', '', $phoneNumber);
    	$phoneNumber = str_replace('-', '', $phoneNumber);
    	
    	$transaction = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transaction-request/>');
    	$transaction->addChild('version', '3.1.1.15');
    	
    	$verification = $transaction->addChild('verification');
    	$verification->addChild('merchantId', Mage::helper('checkoutapi')->getGlobalConfig('sellerId', $profile->getData('store_id')));
    	$verification->addChild('merchantKey', Mage::helper('checkoutapi')->getGlobalConfig('sellerKey', $profile->getData('store_id')));
    	
    	$orderXml = $transaction->addChild('order');
    	
    	$recurringPayment = $orderXml->addChild('recurringPayment');
    	if ($processorId != null) {
    		$recurringPayment->addChild('processorID', $processorId);
    	}
    	$recurringPayment->addChild('referenceNum', $prefixo . $profile->getData('internal_reference_id'));
    	$recurringPayment->addChild('ipAddress', Mage::helper('core/http')->getRemoteAddr());
    	
    	if (!$isOrderVirtual) {
    		$shipping = $recurringPayment->addChild('shipping');
    		$shipping->addChild('name', $quote->getData('customer_firstname') . ' ' . str_replace('(pj)', '', $quote->getData('customer_lastname')));
    		$shipping->addChild('address', $shippingAddress->getStreet(1));
    		
    		if($shippingAddress->getStreet(2) != null) {
    			$shipping->addChild('address2', $shippingAddress->getStreet(2));
    		}
    		else {
    			$shipping->addChild('address2', 'Bairro');
    		}
    		$shipping->addChild('city', $shippingAddress->getCity());
    		$shipping->addChild('state', ($shippingAddress->getRegionCode() != '') ? $shippingAddress->getRegionCode() : $shippingAddress->getRegion());
    		$shipping->addChild('postalcode', trim(str_replace('-', '', $shippingAddress->getPostcode())));
    		$shipping->addChild('country', 'BR');
    		$shipping->addChild('phone', $phoneNumber);
    		$shipping->addChild('email', $shippingAddress->getEmail());
    		
//     		if ($sandbox != '1') {
//     			// Adiciona a informação do CPF
//     			$documents = $shipping->addChild('documents');
//     			$documents->addAttribute('documentCount', '1');
//     			$document = $documents->addChild('document');
//     			$document->addChild('documentIndex', '1');
//     			$document->addChild('documentType', 'CPF');
//     			$document->addChild('documentValue', $taxvat);
//     		}
    	}
    	
    	$billing = $recurringPayment->addChild('billing');
    	$billing->addChild('name', $quote->getData('customer_firstname') . ' ' . str_replace('(pj)', '', $quote->getData('customer_lastname')));
    	$billing->addChild('address', $billingAddress->getStreet(1));
    	if($billingAddress->getStreet(2)!= NULL) {
    		$billing->addChild('address2', $billingAddress->getStreet(2));
    	}else {
    		$billing->addChild('address2', '');
    	}
    	$billing->addChild('city', $billingAddress->getCity());
    	$billing->addChild('state', ($billingAddress->getRegionCode() != '') ? $billingAddress->getRegionCode() : $billingAddress->getRegion());
    	$billing->addChild('postalcode', trim(str_replace('-', '', $billingAddress->getPostcode())));
    	$billing->addChild('country', 'BR');
    	$billing->addChild('phone', $phoneNumber);
    	$billing->addChild('email', $billingAddress->getEmail());
        
        
    	
//     	if ($sandbox != '1') {
//     		// Adiciona a informação do CPF
//     		$documents = $billing->addChild('documents');
//     		$documents->addAttribute('documentCount', '1');
//     		$document = $documents->addChild('document');
//     		$document->addChild('documentIndex', '1');
//     		$document->addChild('documentType', 'CPF');
//     		$document->addChild('documentValue', $taxvat);
//     	}
    	
    	$transactionDetail = $recurringPayment->addChild('transactionDetail');
    	
    	$payType = $transactionDetail->addChild('payType');
    	$isCcToken = $quote->getPayment()->getMethodInstance()->getConfigData('isCCtoken');
    	$ccToken = $quote->getPayment()->getData('maxipago_cc_token');
    	if($session->isLoggedIn() && $isCcToken) {
    		$customer = $session->getCustomer();
    		$mpCustomerId = $customer->getData('maxipago_customer_id');
    		
    		if (empty($ccToken))
    		{
    			if (empty($mpCustomerId)) {
    				try {
    					$responseCustomerMP = $this->addConsumer($customer);
    					if ($responseCustomerMP && intval($responseCustomerMP->errorCode) == 0) {
    						$mpCustomerId = (string)$responseCustomerMP->result->customerId;
    						$customer->setData('maxipago_customer_id', $mpCustomerId);
    						$customer->save();
    					}
    				}
    				catch (\Exception $e) {
    					Mage::helper('checkoutapi')->log('Erro ao adicionar o usuário na maxiPago!', 'maxipago.log');
    				}
    			}
				
    			try {
    				$responseCardMP = Mage::getSingleton('checkoutapi/api')->addCard($quote, $mpCustomerId);
    				if ($responseCardMP && $responseCardMP->errorCode == 0) {
    					$ccToken = $this->encrypt($responseCardMP->result->token);
    				}
    			}
    			catch (\Exception $e) {
    				Mage::helper('checkoutapi')->log('Erro ao salvar o cartão de crédito na maxiPago!.', 'maxipago.log');
    			}
    		}
    		
    		$onFile = $payType->addChild('onFile');
    		$onFile->addChild('customerId', $mpCustomerId);
    		$onFile->addChild('token', $this->decrypt($ccToken));
    		if ($quote->getPayment()->getData('cc_cid')) {
    			$onFile->addChild('cvvNumber', $quote->getPayment()->getData('cc_cid'));
    		}
    		else {
    			$onFile->addChild('cvvNumber', $this->decrypt($quote->getPayment()->getData('cc_cid_enc')));
    		}
    		
    		$quote->getPayment()->setData('maxipago_cc_token', $ccToken);
    	}
    	else {
    		$creditCard = $payType->addChild('creditCard');
    		$creditCard->addChild('number', $this->decrypt($quote->getPayment()->getData('cc_number_enc')));
    		$creditCard->addChild('expMonth', $quote->getPayment()->getData('cc_exp_month'));
    		$creditCard->addChild('expYear', $quote->getPayment()->getData('cc_exp_year'));
    		$creditCard->addChild('cvvNumber', $this->decrypt($quote->getPayment()->getData("cc_cid_enc")));
    		
//     		if($session->isLoggedIn()) {
//     			$isCcToken = $quote->getPayment()->getMethodInstance()->getConfigData('isCCtoken');
//     			if($isCcToken && !empty($customerId) && empty($ccToken)) {
//     				$saveOnFile = $recurringPayment->addChild('saveOnFile');
//     				$saveOnFile->addChild('customerToken', $customerId);
//     			}
//     		}
    	}
    	
    	$payment = $recurringPayment->addChild('payment');
    	$payment->addChild('currencyCode', 'BRL');
    	$payment->addChild('chargeTotal', $chargeTotal);
    	
//     	// Adiciona as informações dos produtos
//     	$itemIndex = 1;
//     	$countItems = count($quote->getAllVisibleItems());
//     	if ($countItems > 0) {
//     		$itemList = $recurringPayment->addChild('itemList');
//     		$itemList->addAttribute('itemCount', $countItems);
//     		foreach ($quote->getAllVisibleItems() as $product) {
//     			$item = $itemList->addChild('item');
//     			$item->addChild('itemIndex', $itemIndex);
//     			$item->addChild('itemProductCode', $product->getSku());
//     			$item->addChild('itemDescription', $product->getName());
//     			$item->addChild('itemQuantity', intval($product->getQtyOrdered()));
//     			$item->addChild('itemTotalAmount', number_format($product->getRowTotal(), 2, '.', ''));
//     			$item->addChild('itemUnitCost', number_format($product->getOriginalPrice(), 2, '.', ''));
    			 
//     			$itemIndex++;
//     		}
//     	}
    	
    	$recurring = $recurringPayment->addChild('recurring');
    	$recurring->addChild('action', 'new');
    	$recurring->addChild('frequency', $profile->getData('period_frequency'));
    	switch($profile->getData('period_unit')){
    		case 'day': $recurring->addChild('period', 'daily'); break;
    		case 'week': $recurring->addChild('period', 'weekly'); break;
    		case 'month': $recurring->addChild('period', 'monthly'); break;
    		default: $recurring->addChild('period', 'monthly'); break;
    	}
    	$installments = $profile->getData('period_max_cycles');
    	if (empty($installments) || $installments == 0)
    		$installments = '1';
    	$recurring->addChild('installments', $installments);
    	$recurring->addChild('failureThreshold', $profile->getData('suspension_threshold'));
    	if ($profile->getData('start_datetime')) {
    		$startDate = date('Y-m-d', strtotime($profile->getData('start_datetime')));
    	}
    	else {
    		$startDate = date('Y-m-d');
    	}
    	if ($profile->getInitAmount()) {
    		if ($startDate == date('Y-m-d')) {
    			$startDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
    		}
    		$recurring->addChild('firstAmount', number_format($profile->getInitAmount(), 2, '.', ''));
    	}
    	$recurring->addChild('startDate', $startDate);
    	
    	$responseXml = $this->sendMaxiPagoRequest($transaction->asXML(), $sandbox);
    	
    	$responseMP = simplexml_load_string($responseXml);
    	
    	return $responseMP;
    }
    
    public function cancelRecurring(Mage_Payment_Model_Recurring_Profile $profile) {
    	Mage::helper('checkoutapi')->log('Cancelando um pagamento recorrente', 'maxipago.log');
    	
    	$storeId = $profile->getData('store_id');
    	$sandbox = Mage::helper('checkoutapi')->getGlobalConfig('sandbox', $storeId);
    	
    	$cancelRecurring = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><api-request/>');
    	
    	$verification = $cancelRecurring->addChild('verification');
    	$verification->addChild('merchantId', Mage::helper('checkoutapi')->getGlobalConfig('sellerId', $storeId));
    	$verification->addChild('merchantKey', Mage::helper('checkoutapi')->getGlobalConfig('sellerKey', $storeId));
    	
    	$command = $cancelRecurring->addChild('command','cancel-recurring');
    	
    	$request = $cancelRecurring->addChild('request');
    	$request->addChild('orderID', $profile->getData('reference_id'));
    	
    	$responseXml = $this->sendMaxiPagoRequest($cancelRecurring->asXML(), $sandbox, self::POST_API);
    	 
    	$responseMP = simplexml_load_string($responseXml);
    	 
    	return $responseMP;
    }
    
    public function updateRecurring($profile, $flagActive) {
    	if ($flagActive) {
    		Mage::helper('checkoutapi')->log('Ativando um pagamento recorrente', 'maxipago.log');
    	}
    	else {
    		Mage::helper('checkoutapi')->log('Suspendendo um pagamento recorrente', 'maxipago.log');
    	}
    	
    	$storeId = $profile->getData('store_id');
    	$sandbox = Mage::helper('checkoutapi')->getGlobalConfig('sandbox', $storeId);
    	
    	$apiRequest = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><api-request/>');
    	
    	$verification = $apiRequest->addChild('verification');
    	$verification->addChild('merchantId', Mage::helper('checkoutapi')->getGlobalConfig('sellerId', $storeId));
    	$verification->addChild('merchantKey', Mage::helper('checkoutapi')->getGlobalConfig('sellerKey', $storeId));
    	
    	$command = $apiRequest->addChild('command','modify-recurring');
    	
    	$request = $apiRequest->addChild('request');
    	$request->addChild('orderID', $profile->getData('reference_id'));
    	
    	$recurring = $request->addChild('recurring');
    	$recurring->addChild('action', $flagActive ? 'enable' : 'disable');
    	
    	$responseXml = $this->sendMaxiPagoRequest($apiRequest->asXML(), $sandbox, self::POST_API);
    	
    	$responseMP = simplexml_load_string($responseXml);
    	
    	return $responseMP;
    }
    
    public function addConsumer($consumer) {
    	Mage::helper('checkoutapi')->log('Salvando os dados do cliente na maxiPago!', 'maxipago.log');
    	
    	$sandbox = Mage::helper('checkoutapi')->getGlobalConfig('sandbox');
    	
    	$customerXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><api-request/>');
    	
    	$verificationCustomer = $customerXml->addChild('verification');
    	$verificationCustomer->addChild('merchantId', Mage::helper('checkoutapi')->getGlobalConfig('sellerId'));
    	$verificationCustomer->addChild('merchantKey', Mage::helper('checkoutapi')->getGlobalConfig('sellerKey'));
    	
    	$customerXml->addChild('command', 'add-consumer');
    	
    	$requestCustomer = $customerXml->addChild('request');
    	$requestCustomer->addChild('customerIdExt', $consumer->getData('entity_id'));
    	$requestCustomer->addChild('firstName', $consumer->getData('firstname'));
    	$requestCustomer->addChild('lastName', $consumer->getData('lastname'));
    	$requestCustomer->addChild('email', $consumer->getData('email'));
    	if ($consumer->getData('dob')) {
    		$requestCustomer->addChild('dob', date("m/d/Y", strtotime($consumer->getData('dob'))));
    	}
    	$requestCustomer->addChild('ssn', $consumer->getData('taxvat'));
    	if ($consumer->getData('gender')) {
    		$requestCustomer->addChild('sex', $consumer->getData('gender') == 1 ? 'M' : 'F');
    	}
    	
    	$responseXml = $this->sendMaxiPagoRequest($customerXml->asXML(), $sandbox, self::POST_API);
    	
    	$responseMP = simplexml_load_string($responseXml);
    	 
    	return $responseMP;
    }
    
    public function addCard($order, $consumerId) {
    	Mage::helper('checkoutapi')->log('Salvando os dados do cartão na maxiPago!', 'maxipago.log');
    	
    	$sandbox = Mage::helper('checkoutapi')->getGlobalConfig('sandbox');
    	
    	$ba = $order->getBillingAddress();
    	
    	$phoneNumber = str_replace(' ', '', $ba->getTelephone());
    	$phoneNumber = str_replace('(', '', $phoneNumber);
    	$phoneNumber = str_replace(')', '', $phoneNumber);
    	$phoneNumber = str_replace('-', '', $phoneNumber);
    	
    	$nameCustomer = $order->getData('customer_firstname') . ' ' . str_replace('(pj)', '', $order->getData('customer_lastname'));
    	
    	$creditCardNumber = $this->decrypt($order->getPayment()->getData('cc_number_enc'));
    	$creditCardExpMonth = str_pad($order->getPayment()->getData('cc_exp_month'), 2, '0', STR_PAD_LEFT);
    	$creditCardExpYear =  $order->getPayment()->getData('cc_exp_year');
    	$creditCardCvv = $this->decrypt($order->getPayment()->getData("cc_cid_enc"));
    	
    	$cardAdd = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><api-request/>');
    	
    	$verificationCard = $cardAdd->addChild('verification');
    	$verificationCard->addChild('merchantId', Mage::helper('checkoutapi')->getGlobalConfig('sellerId'));
    	$verificationCard->addChild('merchantKey', Mage::helper('checkoutapi')->getGlobalConfig('sellerKey'));
    	
    	$cardAdd->addChild('command', 'add-card-onfile');
    	
    	$requestCard = $cardAdd->addChild('request');
    	$requestCard->addChild('customerId', $consumerId);
    	$requestCard->addChild('creditCardNumber', $creditCardNumber);
    	$requestCard->addChild('expirationMonth', $creditCardExpMonth);
    	$requestCard->addChild('expirationYear', $creditCardExpYear);
    	$requestCard->addChild('billingName', $nameCustomer);
    	$requestCard->addChild('billingAddress1', $ba->getStreet(1));
    	$requestCard->addChild('billingAddress2', $ba->getStreet(2));
    	$requestCard->addChild('billingCity', $ba->getCity());
    	$requestCard->addChild('billingState', ($ba->getRegionCode() != '') ? $ba->getRegionCode() : $ba->getRegion());
    	$requestCard->addChild('billingZip', trim(str_replace('-', '', $ba->getPostcode())));
    	$requestCard->addChild('billingCountry', 'BR');
    	$requestCard->addChild('billingPhone', $phoneNumber);
    	$requestCard->addChild('billingEmail', $ba->getEmail());
    	
    	$responseXml = $this->sendMaxiPagoRequest($cardAdd->asXML(), $sandbox, self::POST_API);
    	
    	$responseMP = simplexml_load_string($responseXml);
    	
    	return $responseMP;
    }
    
    public function detailReport($orderId = null, $pageToken = null, $pageNumber = 1) {
                
    	$sandbox = Mage::helper('checkoutapi')->getGlobalConfig('sandbox');
    	
    	$rapi = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rapi-request/>');
    	
    	$verification = $rapi->addChild('verification');
    	$verification->addChild('merchantId', Mage::helper('checkoutapi')->getGlobalConfig('sellerId'));
    	$verification->addChild('merchantKey', Mage::helper('checkoutapi')->getGlobalConfig('sellerKey'));
    	
    	$rapi->addChild('command', 'transactionDetailReport');
    	
    	$request = $rapi->addChild('request');
    	
    	$filterOptions = $request->addChild('filterOptions');
        
    	if (!empty($orderId) and $orderId != null ) {
            $filterOptions->addChild('orderId', $orderId);
    	}
    	elseif (empty($pageToken)) {
            $filterOptions->addChild('period', 'range');
            $filterOptions->addChild('pageSize', '100');
            $filterOptions->addChild('startDate', date('m/d/Y', strtotime('-3 days')));
            $filterOptions->addChild('endDate', date('m/d/Y'));
            $filterOptions->addChild('startTime', '00:00:00');
            $filterOptions->addChild('endTime', '23:59:59');
            $filterOptions->addChild('orderByName', 'transactionDate');
            $filterOptions->addChild('orderByDirection', 'asc');
    	}
    	else {
            $filterOptions->addChild('pageToken', $pageToken);
            $filterOptions->addChild('pageNumber', $pageNumber);
    	}
        
    	$responseXml = $this->sendMaxiPagoRequest($rapi->asXML(), $sandbox, self::REPORTS_API);
    	 
    	$responseMP = simplexml_load_string($responseXml);    	
    	return $responseMP;
    }
    
    public function consultTransaction($transactionId) {
    	$sandbox = Mage::helper('checkoutapi')->getGlobalConfig('sandbox');
    	 
    	$rapi = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rapi-request/>');
    	$verification = $rapi->addChild('verification');
    	$verification->addChild('merchantId', Mage::helper('checkoutapi')->getGlobalConfig('sellerId'));
    	$verification->addChild('merchantKey', Mage::helper('checkoutapi')->getGlobalConfig('sellerKey'));    	 
    	$rapi->addChild('command', 'transactionDetailReport');
    	$request = $rapi->addChild('request');
    	$filterOptions = $request->addChild('filterOptions');
    	$filterOptions->addChild('transactionId', $transactionId);
    	
    	$responseXml = $this->sendMaxiPagoRequest($rapi->asXML(), $sandbox, self::REPORTS_API);

    	$responseMP = simplexml_load_string($responseXml);
    	 
    	return $responseMP;
    }
    
    private function get_callstack($delim="\n") {
    	$dt = debug_backtrace();
    	$cs = '';
    	foreach ($dt as $t) {
    		$cs .= $t['file'] . ' line ' . $t['line'] . ' calls ' . $t['function'] . "()" . $delim;
    	}
    
    	return $cs;
    }
}