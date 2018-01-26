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
class MaxiPago_Payment_Model_Method_Checkout2 extends MaxiPago_Payment_Model_Method_Abstract
{
    protected $_code = 'maxipago_checkout2';
    protected $_canUseForMultishipping = false;
    protected $_canUseCheckout = false;

    protected $_formBlockType = 'maxipago/form_checkout2';
    protected $_infoBlockType = 'maxipago/info_checkout2';

    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $installments = $data->getInstallments();
        $grandTotal = $data->getBaseGrandTotal();
        $hasInterest = $this->_getHelper()->getConfig('installment_type', $this->getCode());

        $cpfCnpj = $data->getCpfCnpj();
        if (!$this->_getHelper()->getConfig('show_taxvat_field')) {
            $cpfCnpj = $this->getTaxvatValue();
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation('description', $data->getDescription());
        $info->setAdditionalInformation('comments', $data->getComments());
        $info->setAdditionalInformation('subject', $data->getSubject());

        $interestRate = $this->_getHelper()->getConfig('interest_rate', $this->getCode());
        $installmentsWithoutInterest = $this->_getHelper()->getConfig('installments_without_interest_rate', $this->getCode());
        if ($installmentsWithoutInterest >= $installments) {
            $interestRate = null;
        }

        if ($installments > 1) {
            $installmentsValue = $this->_getHelper()->getInstallmentValue($grandTotal, $installments, $this->getCode());
            $totalOrderWithInterest = $installmentsValue * $installments;
            $interestValue = $totalOrderWithInterest - $grandTotal;
            $info->setAdditionalInformation('interest_amount', $interestValue);
            $info->setAdditionalInformation('interest_rate', $interestRate);
            $info->setAdditionalInformation('total_with_interest', $totalOrderWithInterest);
        }

        $info->setAdditionalInformation('cpf_cnpj', $cpfCnpj);
        $info->setAdditionalInformation('has_interest', $hasInterest);
        $info->setAdditionalInformation('interest_rate', $interestRate);
        $info->setAdditionalInformation('installment_value', $this->_getHelper()->getInstallmentValue($grandTotal, $installments, $this->getCode()));
        $info->setAdditionalInformation('installments', $installments);
        $info->setAdditionalInformation('base_grand_total', $grandTotal);

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function order(Varien_Object $payment, $amount)
    {
        $errors = null;
        try {
            /** @var MaxiPago_Payment_Model_Api $api */
            $api = $this->_getHelper()->getApi();

            $response = $api->checkout2Method($this, $payment, $amount);
            if (isset($response['errorCode']) && $response['errorCode'] != 1) {
                $payment = $this->setAdditionalInfo($payment, $response);
            } else {
                if ($this->_getHelper()->getConfig('stop_processing')) {
                    $errors = $this->_getHelper()->__('The transaction wasn\'t authorized by the issuer, please check your data and try again');
                    Mage::throwException($errors);
                }
                $payment->setSkipOrderProcessing(true);
                Mage::throwException(Mage::helper('payment')->__('There was an error processing your request. Please contact us or try again later.'));
            }

        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')->getQuote()->setReservedOrderId(null);
            $this->_getHelper()->log($e->getMessage());
            $exception = $errors ?: Mage::helper('payment')->__('There was an error processing your request. Please contact us or try again later.');
            Mage::throwException($exception);
        }

        return $this;
    }

}
