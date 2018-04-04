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
class MaxiPago_Payment_Block_Info_Ticket extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('maxipago/info/ticket.phtml');
        $this->setModuleName('Mage_Payment');
    }

    /**
     * Retrieve current order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        $order = Mage::registry('current_order');

        if (!$order) {
            if ($this->getInfo() instanceof Mage_Sales_Model_Order_Payment) {
                $order = $this->getInfo()->getOrder();
            }
            if ($this->getInfo() instanceof Mage_Sales_Model_Quote_Payment) {
                $order = $this->getInfo()->getQuote();
            }
        }

        return ($order);
    }
}
