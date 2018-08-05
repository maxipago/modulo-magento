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
 * @author        Thiago Contardi <thiago@contardi.com.br>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MaxiPago_Payment_Model_Source_Cctype
    extends Mage_Core_Model_Abstract
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'VI', 'label' => 'Visa'),
            array('value' => 'MC', 'label' => 'Mastercard'),
            array('value' => 'AM', 'label' => 'Amex'),
            array('value' => 'DC', 'label' => 'Diners Club'),
            array('value' => 'EL', 'label' => 'Elo'),
            array('value' => 'DI', 'label' => 'Discover'),
            array('value' => 'HC', 'label' => 'Hipercard'),
            array('value' => 'HI', 'label' => 'Hiper'),
            array('value' => 'JC', 'label' => 'JCB'),
            array('value' => 'AU', 'label' => 'Aura'),
            array('value' => 'CR', 'label' => 'Credz')
        );
    }

    public function getValueArray()
    {
        $arr = [];
        foreach ($this->toOptionArray() as $v => $l) {
            $arr[] = $v;
        }

        return $arr;
    }

    public function getProcessors($ccType)
    {
        /** @var MaxiPago_Payment_Helper_Data $helper */
        $helper = Mage::helper('maxipago');
        $processors = null;

        switch ($ccType) {
            case 'VI':
            case 'MC':
                $processors = array(
                    array('value' => '1', 'label' => $helper->__('Simulador de Teste')),
                    array('value' => '2', 'label' => $helper->__('Redecard')),
                    array('value' => '3', 'label' => $helper->__('GetNet')),
                    array('value' => '4', 'label' => $helper->__('Cielo')),
                    array('value' => '5', 'label' => $helper->__('e.Rede')),
                    array('value' => '6', 'label' => $helper->__('Elavon')),
                    array('value' => '9', 'label' => $helper->__('Stone')),
                    array('value' => '10', 'label' => $helper->__('bin')),
                );
                break;
            case 'AM':
                $processors = array(
                    array('value' => '1', 'label' => $helper->__('Simulador de Teste')),
                    array('value' => '4', 'label' => $helper->__('Cielo')),
                    array('value' => '5', 'label' => $helper->__('e.Rede')),
                );
                break;
            case 'DC':
                $processors = array(
                    array('value' => '1', 'label' => $helper->__('Simulador de Teste')),
                    array('value' => '2', 'label' => $helper->__('Redecard')),
                    array('value' => '4', 'label' => $helper->__('Cielo')),
                    array('value' => '5', 'label' => $helper->__('e.Rede')),
                    array('value' => '6', 'label' => $helper->__('Elavon')),

                );
                break;
            case 'EL':
                $processors = array(
                    array('value' => '1', 'label' => $helper->__('Simulador de Teste')),
                    array('value' => '3', 'label' => $helper->__('GetNet')),
                    array('value' => '4', 'label' => $helper->__('Cielo')),
                    array('value' => '5', 'label' => $helper->__('e.Rede')),
                );
                break;
            case 'DI':
                $processors = array(
                    array('value' => '1', 'label' => $helper->__('Simulador de Teste')),
                    array('value' => '2', 'label' => $helper->__('Redecard')),
                    array('value' => '4', 'label' => $helper->__('Cielo')),
                    array('value' => '5', 'label' => $helper->__('e.Rede')),
                    array('value' => '6', 'label' => $helper->__('Elavon')),
                );
                break;
            case 'HC':
            case 'HI':
                $processors = array(
                    array('value' => '1', 'label' => $helper->__('Simulador de Teste')),
                    array('value' => '2', 'label' => $helper->__('Redecard')),
                    array('value' => '5', 'label' => $helper->__('e.Rede')),
                );
                break;
            case 'JC':
                $processors = array(
                    array('value' => '1', 'label' => $helper->__('Simulador de Teste')),
                    array('value' => '4', 'label' => $helper->__('Cielo')),
                    array('value' => '2', 'label' => $helper->__('Redecard')),
                    array('value' => '5', 'label' => $helper->__('e.Rede')),
                );
                break;
            case 'AU':
                $processors = array(
                    array('value' => '1', 'label' => $helper->__('Simulador de Teste')),
                    array('value' => '4', 'label' => $helper->__('Cielo')),
                );
                break;
            case 'CR':
                $processors = array(
                    array('value' => '1', 'label' => $helper->__('Simulador de Teste')),
                    array('value' => '5', 'label' => $helper->__('e.Rede')),
                );
                break;
            default:
                $processors = array();
                break;
        }

        return $processors;
    }
}