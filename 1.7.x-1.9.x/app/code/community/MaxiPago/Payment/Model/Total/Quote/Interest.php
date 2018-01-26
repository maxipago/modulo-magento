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
class MaxiPago_Payment_Model_Total_Quote_Interest
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{

    protected $_helper;
    protected $_code = 'interest';

    public function __construct()
    {
        $this->setCode($this->_code);
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);
        if ($address->getAddressType() == Mage_Sales_Model_Quote_Address::TYPE_BILLING) {
            if ($address->getQuote()->getPayment()->getMethod() == 'maxipago_cc') {
                $interestAmount = $this->_getHelper()->getSession()->getQuote()->getInterestAmount();

                if ($interestAmount != 0) {
                    $address->setInterestAmount($interestAmount);
                    $address->setBaseInterestAmount($interestAmount);
                    $this->_addAmount($interestAmount);
                    $this->_addBaseAmount($interestAmount);
                }

            }
        }
        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        if ($address->getAddressType() == Mage_Sales_Model_Quote_Address::TYPE_BILLING) {
            $amount = $address->getInterestAmount();
            if ($amount != 0) {
                $address->addTotal(array(
                    'code' => $this->getCode(),
                    'title' => Mage::helper('maxipago')->__('Interest'),
                    'value' => $amount
                ));
            }
        }

        return $this;
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
}