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



/** @var $installer Mage_Paypal_Model_Resource_Setup */

$installer = $this;

$setup = Mage::getModel('customer/entity_setup', 'core_setup');
$setup->addAttribute('customer', 'maxipago_customer_id', array(
    'type' => 'varchar',
    'input' => 'text',
    'label' => 'maxipago_customer_id',
    'global' => 1,
    'visible' => 0,
    'required' => 0,
    'user_defined' => 1,
    'default' => '0',
    'visible_on_front' => 0,
));

if (version_compare(Mage::getVersion(), '1.6.0', '<='))
{
    $customer = Mage::getModel('customer/customer');
    $attrSetId = $customer->getResource()->getEntityType()->getDefaultAttributeSetId();
    $setup->addAttributeToSet('customer', $attrSetId, 'General', 'maxipago_customer_id');
}
if (version_compare(Mage::getVersion(), '1.4.2', '>='))
{
    Mage::getSingleton('eav/config')
        ->getAttribute('customer', 'maxipago_customer_id')
        ->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
        ->save();
}

$installer->run("
    ALTER TABLE  {$this->getTable('sales_flat_quote_payment')} ADD  `maxipago_cc_token` VARCHAR( 255 ) NULL DEFAULT NULL;

    ALTER TABLE  {$this->getTable('sales_flat_order_payment')} ADD  `maxipago_cc_token` VARCHAR( 255 ) NULL DEFAULT NULL;
");


$installer->startSetup();

$installer->endSetup();



