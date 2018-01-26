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
class MaxiPago_Payment_Model_Source_Fraud_Processor
{
	public function toOptionArray()
	{
		return array(
			array('value' => '', 'label' => Mage::helper('maxipago')->__('Default')),
			array('value' => 98, 'label' => Mage::helper('maxipago')->__('ClearSale Total')),
			array('value' => 99, 'label' => Mage::helper('maxipago')->__('Kount'))
		);
	}
}