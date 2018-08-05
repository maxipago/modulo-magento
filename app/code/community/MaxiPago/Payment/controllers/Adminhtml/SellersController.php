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
class MaxiPago_Payment_Adminhtml_SellersController
    extends Mage_Adminhtml_Controller_Action
{
    protected $_helper;
    protected $_orderHelper;

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/sales/maxipago/sellers');
    }

    protected function _initAction()
    {
        $this->_title($this->__('MaxiPago - Magage Sellers'));
        $this->loadLayout()
            ->_setActiveMenu('admin/sales')
            ->_addBreadcrumb(
                $this->_getHelper()->__('MaxiPago - Magage Sellers'),
                $this->_getHelper()->__('MaxiPago - Magage Sellers')
            );

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->_title($this->__('MaxiPago'));
        $this->_title($this->__('Edit Seller'));

        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('maxipago/seller')->load($id);

        if ($model->getId()) {
            Mage::register('seller_data', $model);
        }

        $this->loadLayout();
        $this->_setActiveMenu('maxipago/seller');
        $this->_addBreadcrumb(
            $this->_getHelper()->__('MaxiPago'),
            $this->_getHelper()->__('MaxiPago')
        );
        $this->_addBreadcrumb(
            $this->_getHelper()->__('Manage Sellers'),
            $this->_getHelper()->__('Manage Sellers')
        );
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->_addContent($this->getLayout()->createBlock('maxipago/adminhtml_seller_edit'))
            ->_addLeft($this->getLayout()->createBlock('maxipago/adminhtml_seller_edit_tabs'));
        $this->renderLayout();
    }

    public function saveAction()
    {
        $data = $this->getRequest()->getPost();
        $entityId = $this->getRequest()->getParam('id');
        $redirectBack = $this->getRequest()->getParam('back', false);

        try {
            /** @var MaxiPago_Payment_Model_Seller $seller */
            $seller = Mage::getModel('maxipago/seller');

            if($entityId != '') {
                $seller->load($entityId);
            }

            $seller->addData($data);
            $seller->save();

            Mage::getSingleton('adminhtml/session')
                ->addSuccess($this->_getHelper()->__('Seller created sucessfully'));

            if ($redirectBack) {
                return $this->_redirect('*/*/edit', array('id' => $entityId, '_current' => true));
            }

        } catch(Exception $e) {
            Mage::getSingleton('adminhtml/session')
                ->addError($this->_getHelper()->__('There were an error creating seller: '. $e->getMessage()));
        }
        return $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            Mage::getSingleton('adminhtml/session')->addError('Seller doesn\'t exist!');
            $this->_redirect('*/*/');
        }

        /** @var MaxiPago_Payment_Model_Seller $seller */
        $seller = Mage::getModel('maxipago/seller')->load($id);

        try {
            $seller->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->_getHelper()->__('Seller was successfully deleted'));
            $this->_redirect('*/*/');
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*/edit', array('entity_id' => $this->getRequest()->getParam('entity_id')));
        }

        $this->_redirect('*/*/');
    }

    public function massDeleteAction()
    {
        $sellersIds = $this->getRequest()->getParam('sellers');

        if (!is_array($sellersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Selecione algum item'));
        } else {

            try {
                foreach ($sellersIds as $sellersId) {
                    /** @var MaxiPago_Payment_Model_Seller $model */
                    $model = Mage::getModel('maxipago/seller')->load($sellersId);
                    $model->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')
                    ->__('Total de %d registro(s) removidos', count($sellersIds)));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');

    }

    public function ordersAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function productsAction()
    {
        $this->loadLayout();
        $this->renderLayout();
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


}
