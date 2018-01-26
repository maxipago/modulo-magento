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
class MaxiPago_Payment_Block_Form_Cc
    extends MaxiPago_Payment_Block_Form_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('maxipago/form/cc.phtml');
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * If the method is for recurring profile products
     * @return bool
     */
    public function isRecurringProfile()
    {
        try {
            $items = $this->getQuote()->getAllItems();

            /** @var Mage_Sales_Model_Quote_Item $item */
            foreach ($items as $item) {
                if ($item->getData('is_recurring')) {
                    return true;
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;
    }
}
