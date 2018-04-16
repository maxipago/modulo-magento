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
class MaxiPago_Payment_Model_Cron extends Varien_Event_Observer
{
    CONST CRON_FILE = 'maxipago-cron.log';

    protected $_helper;
    protected $_helperOrder;

    public function queryPayments()
    {
        set_include_path(get_include_path() . PATH_SEPARATOR . Mage::getBaseDir('lib') . DS . 'MaxiPago');

        $this->_getHelper()->log('STARTING CRON', self::CRON_FILE);

        $ninStatuses = array(
            'complete',
            'canceled',
            'closed',
            'holded'
        );

        $date = new DateTime('-15 DAYS'); // first argument uses strtotime parsing
        $fromDate = $date->format('Y-m-d');

        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getModel('sales/order')->getCollection()
            ->join(
                array('payment' => 'sales/order_payment'),
                'main_table.entity_id=payment.parent_id',
                array('payment_method' => 'payment.method')
            )
            ->addFieldToFilter('payment.method', array('like' => 'maxipago_%'))
            ->addFieldToFilter('state', array('nin' => array($ninStatuses)))
            ->addFieldToFilter('created_at', array('gt' => $fromDate))
        ;


        /** @var Mage_Sales_Model_Order $order */
        foreach ($collection as $order) {

            if ($order->getId()) {

                $order = Mage::getModel('sales/order')->load($order->getId());
                $this->_getHelper()->log('pullReport ' . $order->getIncrementId(), self::CRON_FILE);
                /** @var MaxiPago_Payment_Helper_Data $helper */
                $helper = $this->_getHelper();

                $response = $helper->getApi()->pullReport($order, false, self::CRON_FILE);
                if (isset($response['records'])) {
                    $record = isset($response['records'][0]) ? $response['records'][0] : null;
                    if ($record) {
                        $recurringPayment = isset($record['recurringPaymentFlag']) ? $record['recurringPaymentFlag'] : false;
                        if ($recurringPayment) {
                            /** @var Mage_Sales_Model_Recurring_Profile $profile */
                            $profile = Mage::getModel('sales/recurring_profile')->load($record['orderId'], 'reference_id');
                            if ($profile->getId()) {
                                $this->getOrderHelper()->updateRecurringProfile($profile, $record);
                            }
                        } else {
                            $this->getOrderHelper()->updatePayment($order, $record);
                        }
                    }
                }

            }

        }
        $this->_getHelper()->log('ENDING CRON', self::CRON_FILE);
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
    protected function getOrderHelper()
    {
        if (!$this->_helperOrder) {
            $this->_helperOrder = Mage::helper('maxipago/order');
        }

        return $this->_helperOrder;
    }
}
