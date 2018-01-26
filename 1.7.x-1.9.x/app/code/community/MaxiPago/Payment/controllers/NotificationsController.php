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
class MaxiPago_Payment_NotificationsController extends Mage_Sales_Controller_Abstract
{
    protected $_maxipago;
    protected $_helper;
    protected $_customerHelper;
    protected $_orderHelper;
    protected $_paymentHelper;

    /**
     * Index action.
     */
    public function indexAction()
    {
        $this->logParams();
        if (!$lastOrderId = $this->_getLastOrderId()) {
            $this->_redirectCart();
            return;
        }

        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
        $this->_redirectComplete();
    }

	/**
	 * Success action.
	 */
	public function successAction()
	{
        $this->logParams();
		if (!$lastOrderId = $this->_getLastOrderId()) {
			$this->_redirectCart();
			return;
		}

        try {
            $this->_updateOrder();
        } catch (Exception $e) {
            Mage::logException($e);
        }

		Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
		$this->_redirectComplete();
	}
	
	/**
	 * Error action.
	 */
	public function errorAction()
	{
        $this->logParams();

		if (!$lastOrderId = $this->_getLastOrderId()) {
			$this->_redirectCart();
			return;
		}

        try {
            $this->_updateOrder();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $this->_redirectComplete();
	}

	protected function _updateOrder()
    {
        $orderId = $this->getRequest()->getParam('hp_orderid');

        if ($orderId) {
            /** @var MaxiPago_Payment_Helper_Data $helper */
            $helper = $this->_getHelper();

            $response = $helper->getApi()->pullReportByOrderId($orderId);
            $record = isset($response['records'][0]) ? $response['records'][0] : null;
            if ($record) {
                $incrementId = isset($record['referenceNumber']) ? $record['referenceNumber'] : null;
                /** @var Mage_Sales_Model_Order $order */
                $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
                if ($order->getId()) {
                    $this->getOrderHelper()->updatePayment($order, $record);
                }
            }

        }
    }


	/**
	 * @return $this
	 */
	protected function _redirectCart()
	{
		$this->_redirect('checkout/cart');
		return $this;
	}

	/**
	 * @return $this
	 */
	protected function _redirectComplete()
	{
		$this->_redirect('checkout/onepage/success');
		return $this;
	}
	
	/**
	 * @return int
	 */
	protected function _getLastOrderId()
	{
		$lastOrderId = Mage::getSingleton('checkout/session')->getLastOrderId();
		if (empty($lastOrderId)) {
			$lastOrderId = Mage::getSingleton('adminhtml/session')->getLastOrderId();
		}
	
		return (int) $lastOrderId;
	}

    /**
     * @return MaxiPago_Payment_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function _getHelper()
    {
        if (!$this->_helper) {
            /** @var MaxiPago_Payment_Helper_Data _helper */
            $this->_helper = Mage::helper('maxipago');
        }

        return $this->_helper;
    }

    /**
     * @return MaxiPago_Payment_Helper_Customer|Mage_Core_Helper_Abstract
     */
    protected function getCustomerHelper()
    {
        if (!$this->_customerHelper) {
            /** @var MaxiPago_Payment_Helper_Customer _customerHelper */
            $this->_customerHelper = Mage::helper('maxipago/customer');
        }

        return $this->_customerHelper;
    }

    /**
     * @return MaxiPago_Payment_Helper_Order|Mage_Core_Helper_Abstract
     */
    protected function getOrderHelper()
    {
        if (!$this->_orderHelper) {
            /** @var MaxiPago_Payment_Helper_Order _orderHelper */
            $this->_orderHelper = Mage::helper('maxipago/order');
        }

        return $this->_orderHelper;
    }

    /**
     * @return MaxiPago_Payment_Helper_Customer|Mage_Core_Helper_Abstract
     */
    protected function getPaymentHelper()
    {
        if (!$this->_paymentHelper) {
            /** @var MaxiPago_Payment_Helper_Payment _paymentHelper */
            $this->_paymentHelper = Mage::helper('maxipago/payment');
        }

        return $this->_paymentHelper;
    }

    protected function logParams()
    {
        $this->_getHelper()->log($this->getRequest()->getOriginalPathInfo());
        $this->_getHelper()->log('Params');
        $this->_getHelper()->log($this->getRequest()->getParams());
        $this->_getHelper()->log($this->getRequest()->getRawBody());
    }

}