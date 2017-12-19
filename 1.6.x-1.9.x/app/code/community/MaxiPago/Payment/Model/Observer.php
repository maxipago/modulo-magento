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
class MaxiPago_Payment_Model_Observer extends Varien_Event_Observer
{
    protected $_helper;
    protected $_helperOrder;

    public function controllerFrontInitBefore(Varien_Event_Observer $event)
    {
        self::autoload();
    }

    public static function autoload()
    {
        // Add our vendor folder to our include path
        set_include_path(get_include_path() . PATH_SEPARATOR . Mage::getBaseDir('lib') . DS . 'MaxiPago');
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function salesOrderPaymentPlaceEnd(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $observer->getEvent()->getPayment();
        $availableMethods = $this->_getHelper()->getAvailableMethods();
        $status = false;

        $methodCode = $payment->getMethod();
        $message = '';
        if (in_array($methodCode, $availableMethods)) {
            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();

            $responseCode = $payment->getAdditionalInformation('response_code');
            $responseMessage = $payment->getAdditionalInformation('response_message');
            $tid = $payment->getAdditionalInformation('transaction_id');

            $canCancel = $this->_getHelper()->getConfig('automatically_cancel');
            $canInvoice = false;

            // Altera o status do pedido para o valor correto
            if ($responseCode == 0) {
                if ($methodCode == 'maxipago_cc') {
                    $paymentAction = $this->_getHelper()->getConfig('cc_payment_action', 'maxipago_cc');
                    $authStatus = $this->_getHelper()->getConfig('authorized_order_status', 'maxipago_cc');
                    $saleStatus = $this->_getHelper()->getConfig('captured_order_status', 'maxipago_cc');
                    $message = Mage::helper('maxipago')->__('The payment was authorized - Transaction ID: %s', (string)$tid);
                    if ($paymentAction == 'sale') {
                        $canInvoice = true;
                        $status = $saleStatus;
                    } else {
                        $status = $authStatus;
                    }
                }
            } elseif ($responseCode != 5) {
                $message = Mage::helper('getnet')->__('The payment was\'t authorized - Transaction ID: %s', (string)$tid);
                //If can cancel, return order
                if ($canCancel) {
                    if ($order->canCancel()) {
                        $order->cancel();
                    }
                }
            }

            if ($canInvoice) {
                $this->getOrderHelper()->createInvoice($order, $tid);
            }

            if ($message) {
                $order->addStatusHistoryComment($message, $status)->setIsCustomerNotified(true);
                $order->save();
            }
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function salesOrderPaymentCapture(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $observer->getEvent()->getPayment();
        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();
        $invoice->setTransactionId($payment->getAdditionalInformation('transaction_id'));
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function salesOrderPaymentCancel(Varien_Event_Observer $observer)
    {
        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = $observer->getEvent()->getPayment()->getOrder();
            /** @var Mage_Sales_Model_Order_Payment $payment */
            $payment = $order->getPayment();

            $methodCode = $payment->getMethod();
            if ($methodCode == 'maxipago_cc') {
                $cancelled = $payment->getAdditionalInformation('cancelled');
                if (!$cancelled) {
                    $captured = $payment->getAdditionalInformation('captured');
                    $maxipagoOrderId = $payment->getAdditionalInformation('order_id');
                    $transactionId = $payment->getAdditionalInformation('transaction_id');
                    if ($captured) {
                        $amount = ($order->getBaseTotalInvoiced()) ? $order->getBaseTotalInvoiced() : $order->getBaseGrandTotal();

                        $capturedDate = $payment->getAdditionalInformation('captured_date');
                        $currentDate = new DateTime();
                        $currentDate = $currentDate->format('Y-m-d');

                        if ($captured && $capturedDate == $currentDate) {
                            $this->_getHelper()->getApi()->void($order, $maxipagoOrderId);
                        } else {
                            $this->_getHelper()->getApi()->refund($order, $maxipagoOrderId, $amount);
                        }
                    } else {
                        $this->_getHelper()->getApi()->void($order, $transactionId);
                    }

                    $payment->setAdditionalInformation('cancelled', true);
                    $payment->save();
                }
            } else if ($methodCode == 'maxipago_checkout2') {

                $payOrderId = $payment->getAdditionalInformation('pay_order_id');
                $this->_getHelper()->getApi()->cancelPaymentOrder($order, $payOrderId);

                $payment->setAdditionalInformation('cancelled', true);
                $payment->save();

            }

        } catch (Exception $e) {
            Mage::logException($e);
            Mage::throwException($e->getMessage());
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
