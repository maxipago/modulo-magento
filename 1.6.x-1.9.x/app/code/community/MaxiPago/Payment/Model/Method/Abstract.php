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
 * @copyright     Copyright (c) 2017
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
abstract class MaxiPago_Payment_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{
    protected $_isInitializeNeeded          = false;
    protected $_canUseInternal              = true;

    protected $_isGateway                   = true;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canCaptureOnce              = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = true;
    protected $_canFetchTransactionInfo     = true;
    protected $_canReviewPayment            = true;
    protected $_canCreateBillingAgreement   = false;
    protected $_canManageRecurringProfiles  = false;

    protected $_helper = null;
    protected $_orderHelper = null;

    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $cpfCnpj = $data->getCpfCnpj();
        if (!$this->_getHelper()->getConfig('show_taxvat_field')) {
            $cpfCnpj = $this->getTaxvatValue();
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation('cpf_cnpj', $cpfCnpj);

        return $this;
    }

    public function getOrderPlaceRedirectUrl()
    {
        $redirectUrl = Mage::registry('maxipago_redirect_url');
        return $redirectUrl;
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture()
    {
        return $this->_canCapture;
    }

    /**
     * Order increment ID getter (either real from order or a reserved from quote)
     *
     * @return string
     */
    protected function _getReservedOrderId()
    {
        //Order Increment ID
        $incrementOrderId = $this->_getHelper()->getSession()->getQuote()->getReservedOrderId();
        if (!$incrementOrderId) {
            $this->_getHelper()->getSession()->getQuote()->reserveOrderId();
        }

        return $this->_getHelper()->getSession()->getQuote()->getReservedOrderId();
    }

    /**
     * @return MaxiPago_Payment_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function _getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('maxipago');
        }

        return $this->_helper;
    }

    /**
     * @return MaxiPago_Payment_Helper_Order|Mage_Core_Helper_Abstract
     */
    protected function _getOrderHelper()
    {
        if (!$this->_orderHelper) {
            $this->_orderHelper = Mage::helper('maxipago/order');
        }

        return $this->_orderHelper;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Sales_Model_Order_Payment
     */
    protected function setAdditionalInfo(Mage_Sales_Model_Order_Payment $payment, $response)
    {
        //Set Transaction Id
        if (isset($response['transactionID'])) {
            $tid = $response['transactionID'];
            $payment->setAdditionalInformation('transaction_id', $tid);
            $payment->setTransactionId($tid);
        }

        if (isset($response['processorTransactionID']))
            $payment->setAdditionalInformation('processor_transaction_id', $response['processorTransactionID']);

        if (isset($response['processorReferenceNumber']))
            $payment->setAdditionalInformation('processor_reference_number', $response['processorReferenceNumber']);

        if (isset($response['creditCardScheme']))
            $payment->setAdditionalInformation('credit_card_scheme', $response['creditCardScheme']);

        if (isset($response['creditCardCountry']))
            $payment->setAdditionalInformation('credit_card_scheme', $response['creditCardCountry']);

        if (isset($response['responseMessage']))
            $payment->setAdditionalInformation('response_message', $response['responseMessage']);

        if (isset($response['responseCode']))
            $payment->setAdditionalInformation('response_code', $response['responseCode']);

        if (isset($response['authCode']))
            $payment->setAdditionalInformation('auth_code', $response['authCode']);

        if (isset($response['orderID']))
            $payment->setAdditionalInformation('order_id', $response['orderID']);

        if (isset($response['fraudScore']))
            $payment->setAdditionalInformation('fraud_score', $response['fraudScore']);

        if (isset($response['transactionState']))
            $payment->setAdditionalInformation('last_transaction_state', $response['transactionState']);

        if (isset($response['authenticationURL']))
            $payment->setAdditionalInformation('authentication_url', $response['authenticationURL']);

        if (isset($response['boletoUrl']))
            $payment->setAdditionalInformation('boleto_url', $response['boletoUrl']);

        if (isset($response['onlineDebitUrl']))
            $payment->setAdditionalInformation('boleto_url', $response['onlineDebitUrl']);

        if (isset($response['creditCardCountry']))
            $payment->setAdditionalInformation('cc_country', $response['creditCardCountry']);

        if (isset($response['creditCardScheme']))
            $payment->setAdditionalInformation('cc_scheme', $response['creditCardScheme']);

        if (isset($response['result']) && isset($response['result']['pay_order_id']))
            $payment->setAdditionalInformation('pay_order_id', $response['result']['pay_order_id']);

        return $payment;
    }

    public function getTaxvatValue($forceCustomer = false)
    {
        return $this->_getHelper()->getTaxvatValue($forceCustomer);
    }

    public function isAvailable($quote = null)
    {
        if ($this->_getHelper()->getSession()->getCustomerIsGuest()) {
            return false;
        }
        return parent::isAvailable($quote);
    }

}
