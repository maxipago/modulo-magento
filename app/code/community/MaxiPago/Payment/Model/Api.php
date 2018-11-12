<?php
/**
 * Bizcommerce Desenvolvimento de Plataformas Digitais Ltda - Epp
 *
 * INFORMAÇÕES SOBRE LICENÇA
 *
 * Open Software License (OSL 3.0).
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Não edite este arquivo caso você pretenda atualizar este módulo futuramente
 * para novas versões.
 *
 * @category      maxiPago!
 * @package       MaxiPago_Payment
 * @author        Thiago Contardi <thiago@contardi.com.br>
 *
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class MaxiPago_Payment_Model_Api extends Mage_Core_Model_Abstract
{
    protected $_maxipago;
    protected $_helper;
    protected $_customerHelper;
    protected $_orderHelper;
    protected $_paymentHelper;

    const DEFAULT_IP = '127.0.0.1';
    const DEFAULT_TICKET_BANK = 12;
    const DEFAULT_EFT_BANK = 17;
    const DEFAULT_REDEPAY_BANK = 18;
    const DEFAULT_RECURRING_PROCESSOR_ID = 1;

    /**
     * maxiPago! lib Object
     * @return MaxiPago
     */
    public function getMaxipago()
    {
        if (!$this->_maxipago) {
            $merchantId = $this->_getHelper()->getConfig('merchant_id');
            $merchantKey = $this->_getHelper()->getConfig('merchant_key');
            $merchantSecret = $this->_getHelper()->getConfig('merchant_secret');
            if ($merchantId && $merchantKey) {
                $environment = ($this->_getHelper()->getConfig('sandbox')) ? 'TEST' : 'LIVE';
                $this->_maxipago = new MaxiPago();
                $this->_maxipago->setCredentials($merchantId, $merchantKey);
                $this->_maxipago->setEnvironment($environment);
            }
        }

        return $this->_maxipago;

    }

    /**
     * Remove the Credit Card frm maxiPago! Account and remove from the store Account
     *
     * @param $ccSaved
     * @return bool
     */
    public function deleteCC($ccSaved)
    {
        try {
            $data = array(
                'command' => 'delete-card-onfile',
                'customerId' => $ccSaved['customer_id'],
                'token' => $ccSaved['token']
            );

            $this->getMaxipago()->deleteCreditCard($data);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('remove_card', $data, $response, null, false, false);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return MaxiPago_Payment_Model_Customer
     */
    public function getMaxipagoCustomer(Mage_Sales_Model_Order $order)
    {
        /** @var MaxiPago_Payment_Model_Customer $mpCustomer */
        $mpCustomer = Mage::getModel('maxipago/customer')->load($order->getCustomerId(), 'customer_id');

        if (!$mpCustomer->getId()) {

            $customerData = array(
                'customerIdExt' => $order->getCustomerId(),
                'firstName' => $order->getCustomerFirstname(),
                'lastName' => $order->getCustomerLastname()
            );

            $this->getMaxipago()->addProfile($customerData);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('add_profile', $customerData, $response);
            if ($response['errorCode'] != 1) {
                $mpCustomerId = $this->getMaxipago()->getCustomerId();
                $mpCustomer->setData('customer_id', $order->getCustomerId());
                $mpCustomer->setData('customer_id_maxipago', $mpCustomerId);
                $mpCustomer->save();
            }
        }
        return $mpCustomer;
    }


    /**
     * @param MaxiPago_Payment_Model_Method_Cc $method
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return object|boolean
     */
    public function ccMethod(MaxiPago_Payment_Model_Method_Abstract $method, $payment, $amount)
    {
        try {
            $code = $method->getCode();
            $softDescriptor = $this->_getHelper()->getConfig('soft_descriptor', $code);
            $processingType = $this->_getHelper()->getConfig('cc_payment_action', $code); //auth || sale

            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();
            $response = null;

            //Order Data
            $orderId = $order->getIncrementId();
            $fraudCheck = $this->_getHelper()->getConfig('fraud_check', $code) ? 'Y' : 'N';
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            $use3DS = $this->_getHelper()->getConfig('use_3ds', $code);

            $ccCid = Mage::registry('maxipago_cc_cid');

            $ccInstallments = $payment->getAdditionalInformation('cc_installments');
            $ipAddress = $this->getIpAddress();
            $hasInterest = $this->_getHelper()->getConfig('installment_type', $code);
            if ($hasInterest == 'N' && $payment->getAdditionalInformation('cc_total_with_interest') > 0) {
                $amount = number_format($payment->getAdditionalInformation('cc_total_with_interest'), 2, '.', '');
            }

            $token = $payment->getAdditionalInformation('cc_token');
            $amount = number_format($amount, 2, '.', '');
            $shippingAmount = number_format($order->getShippingAmount(), 2, '.', '');

            $ccBrand = $payment->getCcType();
            $processorID = $this->_getHelper()->getProcessor($ccBrand);

            if ($token) {
                $customerIdMp = $payment->getAdditionalInformation('cc_customer_id_maxipago');

                $data = array(
                    'customerId' => $customerIdMp,
                    'token' => $token,
                    'cvvNumber' => $ccCid,
                    'referenceNum' => $orderId,
                    'processorID' => $processorID, //Processor
                    'ipAddress' => $ipAddress,
                    'fraudCheck' => $fraudCheck,
                    'currencyCode' => $currencyCode,
                    'chargeTotal' => $amount,
                    'shippingTotal' => $shippingAmount,
                    'numberOfInstallments' => $ccInstallments,
                    'chargeInterest' => $hasInterest,
                    'softDescriptor' => $softDescriptor
                );

            } else {

                $ccOwner = $payment->getCcOwner();
                $ccNumber = $payment->decrypt($payment->getCcNumberEnc());
                $ccExpMonth = str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT);
                $ccExpYear = $payment->getCcExpYear();

                $data = array(
                    'referenceNum' => $orderId, //Order ID
                    'processorID' => $processorID, //Processor
                    'ipAddress' => $ipAddress,
                    'fraudCheck' => $fraudCheck,
                    'number' => $ccNumber,
                    'expMonth' => $ccExpMonth,
                    'expYear' => $ccExpYear,
                    'cvvNumber' => $ccCid,
                    'currencyCode' => $currencyCode,
                    'chargeTotal' => $amount,
                    'shippingTotal' => $shippingAmount,
                    'numberOfInstallments' => $ccInstallments,
                    'chargeInterest' => $hasInterest,
                    'softDescriptor' => $softDescriptor
                );

                $ccSaveCard = $payment->getAdditionalInformation('cc_save_card');
                if ($ccSaveCard) {
                    $this->saveCard($payment);
                }
            }

            if ($fraudCheck == 'Y' && $processingType == 'auth') {
                $fraudProcessorId = $this->_getHelper()->getConfig('fraud_processor', $code);

                if ($fraudProcessorId) {
                    $captureOnLowRisk = $this->_getHelper()->getConfig('capture_on_low_risk', $code) ? 'Y' : 'N';
                    $voidOnHighRisk = $this->_getHelper()->getConfig('void_on_high_risk', $code) ? 'Y' : 'N';

                    $data['fraudProcessorID'] = $fraudProcessorId;
                    $data['captureOnLowRisk'] = $captureOnLowRisk;
                    $data['voidOnHighRisk'] = $voidOnHighRisk;
                    if ($fraudProcessorId == '98') {
                        $data['fraudToken'] = $this->_getHelper()->getFraudToken('clearsale');
                    } else if ($fraudProcessorId == '99') {
                        $data['fraudToken'] = $this->_getHelper()->getFraudToken('kount');
                    }
                    $data['websiteId'] = 'DEFAULT';
                }

            }

            if ($use3DS) {
                $data['mpiProcessorID'] = $this->_getHelper()->getConfig('mpi_processor', $code);
                $data['onFailure'] = $this->_getHelper()->getConfig('failure_action', $code);
            }

            $billingData = $this->getCustomerHelper()->getAddressData($order, $payment);
            $shippingData = $this->getCustomerHelper()->getAddressData($order, $payment, 'shipping');
            $orderData = $this->getOrderHelper()->getOrderData($order, $payment);

            $data = array_merge($data, $billingData, $shippingData, $orderData);

            if ($processingType == 'auth') {
                if ($use3DS) {
                    $this->getMaxipago()->authCreditCard3DS($data);
                } else {
                    $this->getMaxipago()->creditCardAuth($data);
                }
            } else {
                if ($use3DS) {
                    $this->getMaxipago()->saleCreditCard3DS($data);
                } else {
                    $this->getMaxipago()->creditCardSale($data);
                }
            }
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('credit_card', $data, $response, $orderId);

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }


            return $response;

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;

    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return object|boolean
     */
    public function fraudRequest($order)
    {
        try {
            /** @var Mage_Payment_Model_Method_Abstract $order */
            $payment = $order->getPayment()->getMethodInstance();
            $response = null;
            $code = MaxiPago_Payment_Helper_Data::MAXIPAGO_FRAUD_CODE;

            //Order Data
            $orderId = $order->getIncrementId();
            $fraudCheck = 'Y';
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            $ipAddress = $this->getIpAddress();
            $chargeTotal = number_format($order->getGrandTotal(), 2, '.', '');
            $shippingAmount = number_format($order->getShippingAmount(), 2, '.', '');

            $data = array(
                'processorID' => MaxiPago_Payment_Helper_Data::MAXIPAGO_FRAUD_PROCESSOR,
                'referenceNum' => $orderId, //Order ID
                'customerIdExt' => $order->getCustomerTaxvat(),
                'ipAddress' => $ipAddress,
                'fraudCheck' => $fraudCheck,
                'currencyCode' => $currencyCode,
                'chargeTotal' => $chargeTotal,
                'shippingTotal' => $shippingAmount
            );

            $fraudProcessorId = $this->_getHelper()->getConfig('fraud_processor', $code);
            if ($fraudProcessorId) {
                if ($fraudProcessorId == '98') {
                    $data['fraudToken'] = $this->_getHelper()->getFraudToken('clearsale');
                } else if ($fraudProcessorId == '99') {
                    $data['fraudToken'] = $this->_getHelper()->getFraudToken('kount');
                }
                $data['websiteId'] = 'DEFAULT';
            }

            $billingData = $this->getCustomerHelper()->getAddressData($order, $payment);
            $shippingData = $this->getCustomerHelper()->getAddressData($order, $payment, 'shipping');
            $orderData = $this->getOrderHelper()->getOrderData($order, $payment);

            $data = array_merge($data, $billingData, $shippingData, $orderData);

            $this->getMaxipago()->fraud($data);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('fraud', $data, $response, $orderId);

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }

            return $response;

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;

    }

    /**
     * @param MaxiPago_Payment_Model_Method_Cc $method
     * @param Mage_Payment_Model_Info $paymentInfo
     * @param float $amount
     * @return object|boolean
     */
    public function recurringMethod(MaxiPago_Payment_Model_Method_Abstract $method, $profile, $paymentInfo, $action = 'new')
    {
        try {
            $quote = $profile->getQuote();

            $code = $method->getCode();
            $softDescriptor = $this->_getHelper()->getConfig('soft_descriptor', $code);

            $response = null;

            //Order Data
            $referenceNum = $profile->getData('internal_reference_id');
            $fraudCheck = $this->_getHelper()->getConfig('fraud_check', $code) ? 'Y' : 'N';
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

            $ccCid = Mage::registry('maxipago_cc_cid');

            $ipAddress = $this->getIpAddress();

            $amount = number_format($profile->getTaxAmount() + $profile->getBillingAmount() + $profile->getShippingAmount(), 2, '.', '');

            $ccBrand = $paymentInfo->getCcType();
            $processorID = self::DEFAULT_RECURRING_PROCESSOR_ID;//$this->_getHelper()->getProcessor($ccBrand);
            $cpfCnpj = $paymentInfo->getAdditionalInformation('cpf_cnpj');

            $ccNumber = $paymentInfo->decrypt($paymentInfo->getCcNumberEnc());
            $ccExpMonth = str_pad($paymentInfo->getCcExpMonth(), 2, '0', STR_PAD_LEFT);
            $ccExpYear = $paymentInfo->getCcExpYear();

            $startDate = date('Y-m-d');
            if ($profile->getData('start_datetime')) {
                $startDate = date('Y-m-d', strtotime($profile->getData('start_datetime')));
            }

            $frequency = $profile->getData('period_frequency');
            $failureThreshold = $profile->getData('suspension_threshold');
            $installments = $profile->getData('period_max_cycles');
            if (!$installments) {
                $installments = '1';
            }

            $period = 'monthly';
            switch($profile->getData('period_unit')){
                case 'day':
                    $period = 'daily';
                    break;
                case 'week':
                    $period = 'weekly';
                    break;
                case 'month':
                    $period = 'monthly';
                    break;
            }

            $data = array(
                'referenceNum' => $referenceNum, //Profile reference NUM
                'processorID' => $processorID, //Processor
                'ipAddress' => $ipAddress,
                'customerIdExt' => $cpfCnpj,
                //'fraudCheck' => $fraudCheck,
                'number' => $ccNumber,
                'expMonth' => $ccExpMonth,
                'expYear' => $ccExpYear,
                'cvvNumber' => $ccCid,
                'currencyCode' => $currencyCode,
                'chargeTotal' => $amount,
                'softDescriptor' => $softDescriptor,
                'action' => $action,
                //Recurring data
                'startDate' => $startDate,
                'frequency' => $frequency,
                'period' => $period,
                'installments' => $installments,
                'failureThreshold' => $failureThreshold
            );

            if ($profile->getInitAmount()) {
                if ($startDate == date('Y-m-d')) {
                    $data['startDate'] = date('Y-m-d', strtotime($startDate . ' +1 day'));
                }
                $data['firstAmount'] = number_format($profile->getInitAmount(), 2, '.', '');
            }

            $billingData = $this->getCustomerHelper()->getAddressData($quote, $paymentInfo);
            $shippingData = $this->getCustomerHelper()->getAddressData($quote, $paymentInfo, 'shipping');

            $data = array_merge($data, $billingData, $shippingData);

            $this->getMaxipago()->createRecurring($data);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('recurring_payment', $data, $response, $referenceNum);

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }


            return $response;

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;
    }

    /**
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @return boolean
     */
    public function cancelRecurring(Mage_Payment_Model_Recurring_Profile $profile)
    {
        try {
            $data = array(
                'command' => 'cancel-recurring',
                'orderID' =>  $profile->getData('reference_id')
            );

            $this->getMaxipago()->cancelRecurring($data);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }

            $this->_getHelper()->saveTransaction('cancel_recurring', $data, $response);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function updateRecurring($profile, $flagActive)
    {
        try {
            $data = array(
                'command' => 'modify-recurring',
                'orderID' =>  $profile->getData('reference_id'),
                'action' =>  (!$flagActive) ? 'disable' : 'enable'
            );

            $this->getMaxipago()->cancelRecurring($data);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }

            $this->_getHelper()->saveTransaction('cancel_recurring', $data, $response);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Send the payment method Credit Card to the gateway
     * @param MaxiPago_Payment_Model_Method_Cc $method
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return object|boolean
     */
    public function dcMethod(MaxiPago_Payment_Model_Method_Abstract $method, $payment, $amount)
    {
        try {
            $code = $method->getCode();

            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();
            $response = null;

            //Order Data
            $orderId = $order->getIncrementId();
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            $ipAddress = $this->getIpAddress();

            $ccCid = Mage::registry('maxipago_dc_cid');

            $ccOwner = $payment->getCcOwner();
            $ccNumber = $payment->decrypt($payment->getCcNumberEnc());
            $ccExpMonth = str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT);
            $ccExpYear = $payment->getCcExpYear();
            $ccBrand = $payment->getCcType();
            $processorID = $this->_getHelper()->getProcessor($ccBrand);
            $amount = number_format($amount, 2, '.', '');

            $data = array(
                'referenceNum' => $orderId, //Order ID
                'processorID' => $processorID, //Processor
                'ipAddress' => $ipAddress,
                'number' => $ccNumber,
                'expMonth' => $ccExpMonth,
                'expYear' => $ccExpYear,
                'cvvNumber' => $ccCid,
                'currencyCode' => $currencyCode,
                'chargeTotal' => $amount
            );


            $data['mpiProcessorID'] = $this->_getHelper()->getConfig('mpi_processor', $code);
            $data['onFailure'] = $this->_getHelper()->getConfig('failure_action', $code);

            $billingData = $this->getCustomerHelper()->getAddressData($order, $payment);
            $shippingData = $this->getCustomerHelper()->getAddressData($order, $payment, 'shipping');
            $orderData = $this->getOrderHelper()->getOrderData($order, $payment);
            $data = array_merge($data, $billingData, $shippingData, $orderData);

            $this->getMaxipago()->saleDebitCard3DS($data);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('debit_card', $data, $response, $orderId);

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }

            return $response;

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;

    }

    /**
     * @param MaxiPago_Payment_Model_Method_Cc $method
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return object|boolean
     */
    public function ticketMethod(MaxiPago_Payment_Model_Method_Abstract $method, $payment, $amount)
    {
        try {
            $code = $method->getCode();

            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();
            $response = null;

            //Order Data
            $orderId = $order->getIncrementId();
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            $ipAddress = $this->getIpAddress();

            $bank = $payment->getAdditionalInformation('bank');
            $cpfCnpj = $payment->getAdditionalInformation('cpf_cnpj');

            $dayToExpire = (int) $this->_getHelper()->getConfig('due_days', $code);
            $instructions = $this->_getHelper()->getConfig('instructions', $code);

            $date = new DateTime();
            $date->modify('+' . $dayToExpire . ' days');
            $expirationDate = $date->format('Y-m-d');

            $bank = ($this->_getHelper()->getConfig('sandbox')) ? self::DEFAULT_TICKET_BANK : $bank;
            $amount = number_format($amount, 2, '.', '');

            $data = array(
                'referenceNum' => $orderId, //Order ID
                'processorID' => $bank, //Bank Number
                'ipAddress' => $ipAddress,
                'chargeTotal' => $amount,
                'customerIdExt' => $cpfCnpj,
                'expirationDate' => $expirationDate,
                'currencyCode' => $currencyCode,
                'number' => str_pad($order->getId(), 8, "0", STR_PAD_LEFT),//Our Number
                'instructions' => $instructions, //Instructions
            );

            $billingData = $this->getCustomerHelper()->getAddressData($order, $payment);
            $shippingData = $this->getCustomerHelper()->getAddressData($order, $payment, 'shipping');
            $orderData = $this->getOrderHelper()->getOrderData($order, $payment);
            $data = array_merge($data, $billingData, $shippingData, $orderData);

            $this->getMaxipago()->boletoSale($data);
            $response = $this->getMaxipago()->response;
            $transactionUrl = isset($response['boletoUrl']) ? $response['boletoUrl'] : null;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('ticket', $data, $response, $orderId, $transactionUrl);

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }

            $this->_getHelper()->saveTransaction('debit_card', $data, $response, $orderId);


            return $response;

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;

    }

    /**
     * @param MaxiPago_Payment_Model_Method_Cc $method
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return object|boolean
     */
    public function eftMethod(MaxiPago_Payment_Model_Method_Abstract $method, $payment, $amount)
    {
        try {
            $code = $method->getCode();

            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();
            $response = null;

            //Order Data
            $orderId = $order->getIncrementId();
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            $ipAddress = $this->getIpAddress();

            $bank = $payment->getAdditionalInformation('bank');
            $cpfCnpj = $payment->getAdditionalInformation('cpf_cnpj');

            $bank = ($this->_getHelper()->getConfig('sandbox')) ? self::DEFAULT_EFT_BANK : $bank;
            $amount = number_format($amount, 2, '.', '');

            $data = array(
                'referenceNum' => $orderId, //Order ID
                'processorID' => $bank, //Bank Number
                'ipAddress' => $ipAddress,
                'currencyCode' => $currencyCode,
                'chargeTotal' => $amount,
                'customerIdExt' => $cpfCnpj,
                'parametersURL' => 'oid=' . $orderId
            );


            $customerData = $this->getCustomerHelper()->getCustomerData($order->getCustomerId());
            $billingData = $this->getCustomerHelper()->getAddressData($order, $payment);
            $data = array_merge($data, $customerData, $billingData);

            $this->getMaxipago()->onlineDebitSale($data);
            $response = $this->getMaxipago()->response;
            $transactionUrl = isset($response['onlineDebitUrl']) ? $response['onlineDebitUrl'] : null;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('eft', $data, $response, $orderId, $transactionUrl);
            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }

            return $response;

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;

    }

    /**
     * @param MaxiPago_Payment_Model_Method_Cc $method
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return object|boolean
     */
    public function redepayMethod(MaxiPago_Payment_Model_Method_Abstract $method, $payment, $amount)
    {
        try {
            $code = $method->getCode();

            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();
            $response = null;
            $amount = number_format($amount, 2, '.', '');
            $shippingAmount = number_format($order->getShippingAmount(), 2, '.', '');

            //Order Data
            $orderId = $order->getIncrementId();
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            $ipAddress = $this->getIpAddress();

            $bank = $payment->getAdditionalInformation('bank');
            $cpfCnpj = $payment->getAdditionalInformation('cpf_cnpj');

            $bank = self::DEFAULT_REDEPAY_BANK;
            $amount = number_format($amount, 2, '.', '');

            $data = array(
                'referenceNum' => $orderId, //Order ID
                'processorID' => $bank, //Bank Number
                'ipAddress' => $ipAddress,
                'currencyCode' => $currencyCode,
                'chargeTotal' => $amount,
                'shippingTotal' => $shippingAmount,
                'customerIdExt' => $cpfCnpj,
                'parametersURL' => 'oid=' . $orderId
            );


            $billingData = $this->getCustomerHelper()->getAddressData($order, $payment);
            $shippingData = $this->getCustomerHelper()->getAddressData($order, $payment, 'shipping');
            $orderData = $this->getOrderHelper()->getOrderData($order, $payment);
            $data = array_merge($data, $billingData, $shippingData, $orderData);

            $this->getMaxipago()->redepay($data);
            $response = $this->getMaxipago()->response;
            $transactionUrl = isset($response['onlineDebitUrl']) ? $response['onlineDebitUrl'] : null;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('redepay', $data, $response, $orderId, $transactionUrl);

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }



            return $response;

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;

    }

    /**
     * @param MaxiPago_Payment_Model_Method_Cc $method
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return object|boolean
     */
    public function checkout2Method(MaxiPago_Payment_Model_Method_Abstract $method, $payment, $amount)
    {
        try {
            $code = $method->getCode();

            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();
            $response = null;
            $amount = number_format($amount, 2, '.', '');
            $hasInterest = $this->_getHelper()->getConfig('installment_type', $code);
            if ($hasInterest == 'N') {
                $amount = number_format($payment->getAdditionalInformation('total_with_interest'), 2, '.', '');
            }
            $operation = $this->_getHelper()->getConfig('checkout2_payment_action', $code);

            //Order Data
            $orderId = $order->getIncrementId();
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

            $installments = $payment->getAdditionalInformation('installments');
            $description = $payment->getAdditionalInformation('description');
            $comments = $payment->getAdditionalInformation('comments');
            $subject = $payment->getAdditionalInformation('subject');
            $cpfCnpj = $payment->getAdditionalInformation('cpf_cnpj');

            $ipAddress = $this->getIpAddress();
            $fraudCheck = $this->_getHelper()->getConfig('fraud_check', $code) ? 'Y' : 'N';

            $dayToExpire = (int) $this->_getHelper()->getConfig('due_days', $code);
            $processorId = $this->_getHelper()->getConfig('processor_id', $code);

            $date = new DateTime();
            $date->modify('+' . $dayToExpire . ' days');
            $expirationDate = $date->format('Y-m-d');
            $expirationDate = $this->_getHelper()->getDate($expirationDate, 'm/d/Y');

            $amount = number_format($amount, 2, '.', '');

            $data = array(
                'referenceNum' => $orderId, //Order ID
                'processorID' => $processorId,
                'customerIdExt' => $cpfCnpj,
                'fraudCheck' => $fraudCheck,
                'ipAddress' => $ipAddress,
                'currencyCode' => $currencyCode,
                'numberOfInstallments' => $installments,
                'expirationDate' => $expirationDate,
                'chargeTotal' => $amount,
                'description' => $description,
                'comments' => $comments,
                'emailSubject' => $subject,
                'operation' => $operation
            );

            $customerData = $this->getCustomerHelper()->getCustomerData($order->getCustomerId(), 'm/d/Y');
            $data = array_merge($data, $customerData);

            $this->getMaxipago()->addPaymentOrder($data);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);
            $this->_getHelper()->saveTransaction('checkout2', $data, $response, $orderId);

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }

            return $response;

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;

    }

    /**
     * Save the credit card at the database
     * @param Mage_Payment_Model_Info $order
     * @return null
     */
    public function saveCard(Mage_Sales_Model_Order_Payment $payment)
    {
        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();
            $address = $this->getCustomerHelper()->getAddressData($order, $payment);

            $customerId = $order->getCustomerId();
            $firstname = $order->getCustomerFirstname();
            $lastname = $order->getCustomerLastname();
            $mpCustomerId = null;

            $ccBrand = $payment->getCcType();
            $ccNumber = $payment->decrypt($payment->getCcNumberEnc());
            $ccExpMonth = str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT);
            $ccExpYear = $payment->getCcExpYear();

            /** @var MaxiPago_Payment_Model_Customer $mpCustomer */
            $mpCustomer = $this->getMaxipagoCustomer($order);

            if ($mpCustomer->getId()) {

                /** @var MaxiPago_Payment_Model_Card $card */
                $description = substr($ccNumber, 0, 6) . 'XXXXXX' . substr($ccNumber, -4, 4);

                /** @var MaxiPago_Payment_Model_Resource_Card_Collection $cards */
                $cards = Mage::getModel('maxipago/card')->getCollection()
                    ->addFieldToFilter('description', $description)
                    ->addFieldToFilter('customer_id_maxipago', $mpCustomer->getCustomerIdMaxipago())
                ;

                if (!$cards->count()) {

                    $date = new DateTime($ccExpYear . '-' . $ccExpMonth . '-01');
                    $date->modify('+1 month');
                    $endDate = $date->format('m/d/Y');
                    $payment->getAdditionalInformation('cpf_cnpj');
                    $mpCustomerId = $mpCustomer->getCustomerIdMaxipago();

                    $ccData = array(
                        'customerId' => $mpCustomerId,
                        'creditCardNumber' => $ccNumber,
                        'expirationMonth' => $ccExpMonth,
                        'expirationYear' => $ccExpYear,
                        'billingName' => $firstname . ' ' . $lastname,
                        'billingAddress1' => $address['billingAddress'],
                        'billingAddress2' => $address['billingAddress2'],
                        'billingCity' => $address['billingCity'],
                        'billingState' => $address['billingState'],
                        'billingZip' => $address['billingZip'],
                        'billingPostCode' => $address['billingZip'],
                        'billingCountryCode' => $address['billingCountryCode'],
                        'billingPhoneType' => $address['billingPhoneType'],
                        'billingPhoneAreaCode' => $address['billingPhoneAreaCode'],
                        'billingPhoneNumber' => $address['billingPhoneNumber'],
                        'billingEmail' => $address['billingEmail'],
                        'onFileEndDate' => $endDate,
                        'onFilePermissions' => 'ongoing'
                    );

                    $this->getMaxipago()->addCreditCard($ccData);
                    $token = $this->getMaxipago()->getToken();
                    $response = $this->getMaxipago()->response;

                    $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
                    $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

                    $this->_getHelper()->saveTransaction('save_card', $ccData, $response);

                    if ($token) {

                        /** @var MaxiPago_Payment_Model_Card $card */
                        $card = Mage::getModel('maxipago/card');
                        $card->setData(
                            array(
                                'customer_id' => $customerId,
                                'customer_id_maxipago' => $mpCustomerId,
                                'token' => $token,
                                'description' => $description,
                                'brand' => $ccBrand
                            )
                        );
                        $card->save();
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

        return true;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $maxipagoOrderId
     * @param $amount
     * @return array|bool
     */
    public function capture($order, $maxipagoOrderId, $amount)
    {
        try {
            $amount = number_format($amount, 2, '.', '');
            $data = array(
                'orderID' => $maxipagoOrderId,
                'referenceNum' => $order->getIncrementId(),
                'chargeTotal' => $amount,
            );
            $this->getMaxipago()->creditCardCapture($data);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('capture', $data, $response, $order->getIncrementId());

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }

            return $response;

        } catch (Exception $e) {
            $this->_getHelper()->log('Error capturing order ' . $order->getIncrementId() . ': ' . $e->getMessage());
        }
        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $maxipagoOrderId
     * @param $amount
     * @return bool
     */
    public function refund($order, $maxipagoOrderId, $amount)
    {
        try {
            $amount = number_format($amount, 2, '.', '');
            $data = array(
                'orderID' => $maxipagoOrderId,
                'referenceNum' => $order->getIncrementId(),
                'chargeTotal' => $amount,
            );

            $this->getMaxipago()->creditCardRefund($data);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('refund', $data, $response, $order->getIncrementId());

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }

            return $response;

        } catch (Exception $e) {
            $this->_getHelper()->log('Error refunding order ' . $order->getIncrementId() . ': ' . $e->getMessage());
        }

        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $maxipagoOrderId
     * @return bool
     */
    public function void($order, $transactionId)
    {
        try {
            $data = array(
                'transactionID' => $transactionId
            );

            $this->getMaxipago()->creditCardVoid($data);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('void', $data, $response, $order->getIncrementId());

            if (
                (isset($response['errorMessage']) && $response['errorMessage'])
                ||
                (isset($response['errorMsg']) && $response['errorMsg'])
            ) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }

            return $response;

        } catch (Exception $e) {
            $this->_getHelper()->log('Error voiding order ' . $order->getIncrementId() . ': ' . $e->getMessage());
        }

        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $payOrderId
     */
    public function cancelPaymentOrder($order, $payOrderId)
    {
        try {
            /** @var Mage_Sales_Model_Order_Payment $payment */
            $payment = $order->getPayment();
            $data = array(
                "payOrderId" => $payment->getAdditionalInformation('pay_order_id'),
            );
            $this->getMaxipago()->cancelPaymentOrder($data);
            $response = $this->getMaxipago()->response;

            $this->_getHelper()->log($this->getMaxipago()->xmlRequest);
            $this->_getHelper()->log($this->getMaxipago()->xmlResponse);

            $this->_getHelper()->saveTransaction('cancel_checkout2', $data, $response, $order->getIncrementId());

            if ( isset($response['errorCode']) && $response['errorCode'] != 0) {
                $error = isset($response['errorMessage']) ? $response['errorMessage'] : $response['errorMsg'];
                Mage::throwException($error);
            }

            return $response;

        } catch (Exception $e) {
            $this->_getHelper()->log('Error cancelling order ' . $order->getIncrementId() . ': ' . $e->getMessage());
        }

        return false;

    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param boolean $transactionId
     * @return array|false
     */
    public function pullReport(Mage_Sales_Model_Order $order, $transactionId = false, $logFile = false)
    {
        try {
            /** @var Mage_Sales_Model_Order_Payment $payment */
            $payment = $order->getPayment();
            if ($order->getPayment()->getMethod() == 'maxipago_checkout2') {
                $data = array(
                    "payOrderId" => $payment->getAdditionalInformation('pay_order_id'),
                );
                $this->getMaxipago()->pullPaymentOrder($data);
            } else {
                if ($transactionId) {
                    $data = array(
                        "transactionID" => $payment->getAdditionalInformation('transaction_id'),
                    );
                } else {
                    $data = array(
                        "orderID" => $payment->getAdditionalInformation('order_id'),
                    );
                }
                $this->getMaxipago()->pullReport($data);
            }

            $response = $this->getMaxipago()->response;

            if ($logFile) {
                $this->_getHelper()->log($this->getMaxipago()->xmlRequest, $logFile);
                $this->_getHelper()->log($this->getMaxipago()->xmlResponse, $logFile);
            }

            return $response;

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;
    }

    /**
     * @param string $orderId
     * @param string $pageToken
     * @param string $pageNumber
     * @return array|false
     */
    public function pullReportByOrderId($orderId, $pageToken = null, $pageNumber = null)
    {
        try {
            $data = array(
                "orderID" =>$orderId,
            );

            if ($pageToken) {
                $data['pageToken'] = $pageToken;
            }

            if ($pageNumber) {
                $data['pageNumber'] = $pageNumber;
            }
            $this->getMaxipago()->pullReport($data);
            $response = $this->getMaxipago()->response;
            return $response;

        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;
    }

    /**
     * @return MaxiPago_Payment_Helper_Data|Mage_Core_Helper_Abstract
     */
    public function _getHelper()
    {
        if (!$this->_helper) {
            /** @var MaxiPago_Payment_Helper_Data _helper */
            $this->_helper = Mage::helper('maxipago');
        }

        return $this->_helper;
    }

    /**
     * @return MaxiPago_Payment_Helper_Customer|Mage_Core_Helper_Abstract
     */
    public function getCustomerHelper()
    {
        if (!$this->_customerHelper) {
            /** @var MaxiPago_Payment_Helper_Customer _customerHelper */
            $this->_customerHelper = Mage::helper('maxipago/customer');
        }

        return $this->_customerHelper;
    }

    /**
     * @return MaxiPago_Payment_Helper_Order|Mage_Core_Helper_Abstract
     */
    public function getOrderHelper()
    {
        if (!$this->_orderHelper) {
            /** @var MaxiPago_Payment_Helper_Order _orderHelper */
            $this->_orderHelper = Mage::helper('maxipago/order');
        }

        return $this->_orderHelper;
    }

    /**
     * @return MaxiPago_Payment_Helper_Customer|Mage_Core_Helper_Abstract
     */
    public function getPaymentHelper()
    {
        if (!$this->_paymentHelper) {
            /** @var MaxiPago_Payment_Helper_Payment _paymentHelper */
            $this->_paymentHelper = Mage::helper('maxipago/payment');
        }

        return $this->_paymentHelper;
    }

    /**
     * @param string $ipAddress
     * @return string
     */
    public function validateIP($ipAddress)
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ipAddress;
        }

        return self::DEFAULT_IP;
    }

    protected function getIpAddress()
    {
        $ipAddress = Mage::helper('core/http')->getRemoteAddr() ? $this->validateIP(Mage::helper('core/http')->getRemoteAddr()) : self::DEFAULT_IP;
        return $ipAddress;
    }
}
