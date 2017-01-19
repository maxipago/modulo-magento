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

class MaxiPago_CheckoutApi_Model_Source_Qtdsplit
{
    public function toOptionArray()
    {
        return array(
            array('value' => '1', 'label'=>"1"),
            array('value' => '2', 'label'=>"2"),
            array('value' => '3', 'label'=>"3"),
            array('value' => '4', 'label'=>"4"),
            array('value' => '5', 'label'=>"5"),
            array('value' => '6', 'label'=>"6"),
            array('value' => '7', 'label'=>"7"),
            array('value' => '8', 'label'=>"8"),
            array('value' => '9', 'label'=>"9"),
            array('value' => '10', 'label'=>"10"),
            array('value' => '11', 'label'=>"11"),
            array('value' => '12', 'label'=>"12"),
        );
    }
}