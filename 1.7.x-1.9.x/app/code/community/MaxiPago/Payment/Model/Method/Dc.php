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
 *
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class MaxiPago_Payment_Model_Method_Dc extends MaxiPago_Payment_Model_Method_Abstract
{
    /**
     * unique internal payment method identifier
     * @var string [a-z0-9_]
     */
    protected $_code = 'maxipago_dc';
    protected $_canUseForMultishipping = false;
    protected $_canUseInternal = false;
    protected $_canSaveCc = true;

    protected $_formBlockType = 'maxipago/form_dc';
    protected $_infoBlockType = 'maxipago/info_dc';

    protected $_helper;

    /**
     * @param mixed $data
     * @return $this
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        /** @var Mage_Payment_Model_Info $info */
        $info = $this->getInfoInstance();

        $cpfCnpj = $data->getCpfCnpj();
        if (!$this->_getHelper()->getConfig('show_taxvat_field')) {
            $cpfCnpj = $this->getTaxvatValue();
        }

        $dcType = $data->getDcType();
        $dcOwner = $data->getDcOwner();
        $dcNumber = preg_replace("/[^0-9]/", '', $data->getDcNumber());
        $dcCid = $data->getDcCid();
        $dcExpMonth = str_pad($data->getDcExpMonth(), 2, '0', STR_PAD_LEFT);
        $dcExpYear = $data->getDcExpYear();

        $dcNumberEnc = $info->encrypt($dcNumber);
        $dcLast4 = substr($dcNumber, -4);
        $dcCidEnc = $info->encrypt($dcCid);


        $info->setCcType($dcType);
        $info->setCcOwner($dcOwner);
        $info->setCcNumber($dcNumber);
        $info->setCcExpMonth($dcExpMonth);
        $info->setCcExpYear($dcExpYear);
        $info->setCcNumberEnc($dcNumberEnc);
        $info->setCcCidEnc($dcCidEnc);
        $info->setCcLast4($dcLast4);

        $info->setAdditionalInformation('cpf_cnpj', $cpfCnpj);

        Mage::unregister('maxipago_dc_cid');
        Mage::register('maxipago_dc_cid', $dcCid);

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
            $response = $api->dcMethod($this, $payment, $amount);

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

                    if (isset($response['authenticationURL'])) {
                        Mage::register('maxipago_redirect_url', $response['authenticationURL']);
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

    public function isAvailable($quote = null)
    {
        $methods = $this->_getHelper()->getMethodsEnabled($this->getCode());
        if (empty($methods)) {
            return false;
        }
        return parent::isAvailable($quote);
    }
}
