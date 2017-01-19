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

class MaxiPago_CheckoutApi_Model_Source_Processorid
{
    public function toOptionArray()
    {
        return array(
            array('value' => '1', 'label' => 'Simulador de Teste'),
            array('value' => '4', 'label' => 'Cielo'),
        	array('value' => '5', 'label' => 'e.Rede'),
            array('value' => '6', 'label' => 'Elavon'),
        	array('value' => '3', 'label' => 'GetNet'),
        	array('value' => '2', 'label' => 'Redecard'),
        );
    }
}