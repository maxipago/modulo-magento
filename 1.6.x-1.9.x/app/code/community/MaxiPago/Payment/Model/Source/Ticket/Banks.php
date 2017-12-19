<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contato@maxipago.com.br so we can send you a copy immediately.
 *
 * @category   maxiPago!
 * @package    MaxiPago_Payment
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MaxiPago_Payment_Model_Source_Ticket_Banks
    extends Mage_Core_Model_Abstract
{
    public function toOptionArray()
    {
        $_helper = Mage::helper('maxipago');

        return array(
            array('value' => '', 'label' => $_helper->__('Select the Bank')),
            array('value' => '11', 'label' => $_helper->__('Ticket') . ' - Itaú'),
            array('value' => '12', 'label' => $_helper->__('Ticket') . ' - Bradesco'),
            array('value' => '13', 'label' => $_helper->__('Ticket') . ' - Banco do Brasil'),
            array('value' => '14', 'label' => $_helper->__('Ticket') . ' - HSBC'),
            array('value' => '15', 'label' => $_helper->__('Ticket') . ' - Santander'),
            array('value' => '16', 'label' => $_helper->__('Ticket') . ' - Caixa Econômica Federal')
        );
    }
}