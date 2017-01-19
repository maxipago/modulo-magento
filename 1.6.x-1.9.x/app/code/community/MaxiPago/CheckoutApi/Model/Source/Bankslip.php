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
 * to atendimento@saffira.com.br so we can send you a copy immediately.
 *
 * @category   Saffira / maxiPago
 * @package    MaxiPago_CheckoutApi
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MaxiPago_CheckoutApi_Model_Source_Bankslip
{
    public function toOptionArray()
    {
        return array(
            array('value' => '', 'label'=>Mage::helper('adminhtml')->__('Selecione o Banco')),
            array('value' => '12', 'label'=>Mage::helper('adminhtml')->__('Boleto Bancário - Bradesco')),
            array('value' => '11', 'label'=>Mage::helper('adminhtml')->__('Boleto Bancário - Itaú')),
            array('value' => '13', 'label'=>Mage::helper('adminhtml')->__('Boleto Bancário - Banco do Brasil')),
            array('value' => '15', 'label'=>Mage::helper('adminhtml')->__('Boleto Bancário - Santander')),
            array('value' => '14', 'label'=>Mage::helper('adminhtml')->__('Boleto Bancário - HSBC')),
            array('value' => '16', 'label'=>Mage::helper('adminhtml')->__('Boleto Bancário - Caixa Econômica Federal')),
        );
    }
}