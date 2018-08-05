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
class MaxiPago_Payment_Model_Method_Eft extends MaxiPago_Payment_Model_Method_Abstract
{
    protected $_code = 'maxipago_eft';
    protected $_canUseInternal = false;

    protected $_formBlockType = 'maxipago/form_eft';
    protected $_infoBlockType = 'maxipago/info_eft';

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
        $info->setAdditionalInformation('bank', $data->getBank());
        $info->setAdditionalInformation('cpf_cnpj', $cpfCnpj);

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
            $response = $api->eftMethod($this, $payment, $amount);

            if (isset($response['transactionID']) && $response['transactionID']) {

                $tid = $response['transactionID'];
                $payment->setCcTransId($tid);

                $payment = $this->setAdditionalInfo($payment, $response);

                if ($response['responseCode'] != 0 && $response['responseCode'] != 5){
                    if ($this->_getHelper()->getConfig('stop_processing')) {
                        $errors = $this->_getHelper()->__('The transaction wasn\'t authorized by the issuer, please check your data and try again');
                        Mage::throwException($errors);
                    }
                    $payment->setSkipOrderProcessing(true);
                } else {

                    if (isset($response['onlineDebitUrl'])) {
                        Mage::register('maxipago_redirect_url', $response['onlineDebitUrl']);
                    }

                }
            } else {
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
