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
class MaxiPago_Payment_Model_Source_Cc_Processor
    extends Mage_Core_Model_Abstract
{
    public function toOptionArray()
    {
        /** @var MaxiPago_Payment_Helper_Data $helper */
        $helper = Mage::helper('maxipago');

        return array(
            array('value' => '1', 'label' => $helper->__('Test Processor')),
            array('value' => '2', 'label' => $helper->__('Redecard')),
            array('value' => '3', 'label' => $helper->__('GetNet')),
            array('value' => '4', 'label' => $helper->__('Cielo')),
            array('value' => '5', 'label' => $helper->__('e.Rede')),
            array('value' => '6', 'label' => $helper->__('Elavon')),
            array('value' => '9', 'label' => $helper->__('Stone')),
        );
    }
}