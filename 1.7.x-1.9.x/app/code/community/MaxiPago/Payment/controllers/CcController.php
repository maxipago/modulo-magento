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
class MaxiPago_Payment_CcController extends Mage_Core_Controller_Front_Action
{
    /**
     * Order instance
     */
    protected $_order;
    protected $_api;
    protected $errorMessageMaxiPago = '';
    protected $errorCodeMaxiPago = '';
    protected $errorTypeErrorMaxiPago = '';
    
    protected function _getSession()
    {
    	return Mage::getSingleton('adminhtml/session');
    }
    
    public function saveAction()
    {             
       $this->loadLayout();
       $this->renderLayout();       
    }
    
    public function editAction()
    {
       $this->loadLayout();
       $this->renderLayout();
    }

    /**
     * List all saved credit cards at the store
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');

        if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('maxipago/cc');
        }
        if ($block = $this->getLayout()->getBlock('maxipago_cc_index')) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $this->getLayout()->getBlock('head')->setTitle($this->__('My Saved Cards'));

        $this->renderLayout();
    }

    /**
     * Method that removes a saved credit card at the store and also at maxiPago!
     */
    public function deleteAction()
    {
        try {
            $cardId = (int)$this->getRequest()->getParam('id');

            if ($cardId) {
                $customerId = Mage::getSingleton('customer/session')->getCustomerId();
                /** @var MaxiPago_Payment_Model_Card $card */
                $card = Mage::getModel('maxipago/card')->load($cardId);
                if ($card->getCustomerId() == $customerId) {
                    $card->delete();

                    $cardData = array(
                        'customer_id' => $card->getCustomerIdMaxiPago(),
                        'token' => $card->getToken(),
                    );

                    if ($this->getApi()->deleteCC($cardData)) {
                        Mage::getSingleton('core/session')->addNotice($this->__('Credit Card removed successfully'));
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('core/session')->addNotice($this->__('There\'s was an error removing the credit card'));
        }

        $this->_redirectReferer();

    }

    public function removeCardAction()
    {
        $response = array('code' => 500, 'response' => 'error');
        $customerId = $this->getRequest()->getParam('custId');

        try {
            /** @var Mage_Customer_Model_Session $session */
            $session = Mage::getSingleton('customer/session');
            $currentCustomer = $session->getCustomer();
            if ($currentCustomer && $customerId == $currentCustomer->getId()) {
                $cardId = $this->getRequest()->getParam('cId');
                if ($cardId) {
                    $collection = Mage::getResourceModel('maxipago/card_collection')
                        ->addFieldToFilter('customer_id', $customerId)
                        ->addFieldToFilter('entity_id', $cardId);
                    if ($collection->getSize() > 0) {
                        foreach ($collection as $item) {
                            $item->delete();
                        }
                        $response = array('code' => 200, 'response' => 'success');
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $this->getResponse()->setHttpResponseCode($response['code']);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));

    }

    /**
     * @return MaxiPago_Payment_Model_Api|Mage_Core_Model_Abstract
     */
    public function getApi()
    {
        if (!$this->_api) {
            $this->_api = Mage::getModel('maxipago/api');
        }

        return $this->_api;
    }
}