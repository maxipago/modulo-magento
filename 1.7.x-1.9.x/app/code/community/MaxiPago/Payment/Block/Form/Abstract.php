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
abstract class MaxiPago_Payment_Block_Form_Abstract
    extends Mage_Payment_Block_Form
{
    protected $_years;
    protected $_months;
    protected $_helper;

    /**
     * Retrieve payment configuration object
     *
     * @return Mage_Payment_Model_Config|Mage_Core_Model_Abstract
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code => $name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        if (!$this->_months) {
            $this->_months = array(
                '' => $this->__('Mês')
            );
            $this->_months = array_merge($this->_months, $this->_getConfig()->getMonths());
        }
        return $this->_months;
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        if (!$this->_years) {
            $this->_years = array(
                '' => $this->__('Ano')
            );
            $this->_years = array_merge($this->_years, $this->_getConfig()->getYears());
        }
        return $this->_years;
    }

    /*
    * Whether switch/solo card type available
    */
    public function hasSsCardType()
    {
        $availableTypes = explode(',', $this->getMethod()->getConfigData('cctypes'));
        $ssPresenations = array_intersect(array('SS', 'SM', 'SO'), $availableTypes);
        if ($availableTypes && count($ssPresenations) > 0) {
            return true;
        }
        return false;
    }

    /*
    * solo/switch card start year
    * @return array
    */
    public function getSsStartYears()
    {
        $years = array();
        $first = date("Y");

        for ($index = 5; $index >= 0; $index--) {
            $year = $first - $index;
            $years[$year] = $year;
        }
        $years = array(0 => $this->__('Ano')) + $years;
        return $years;
    }

    /**
     * @return MaxiPago_Payment_Helper_Data
     */
    protected function _getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('maxipago');
        }
        return $this->_helper;
    }



    /**
     * Retrieve field value data from payment info object
     *
     * @param   string $field
     * @return  mixed
     */
    public function getInfoData($field)
    {
        $value = '';
        try {
            $infoInstance = $this->getMethod()->getInfoInstance();
            $value = $this->escapeHtml($infoInstance->getData($field));
            if (!$value) {
                $value = $infoInstance->getAdditionalInformation($field);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $value;
    }

}
