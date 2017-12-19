<?php
/** @var Mage_Eav_Model_Entity_Setup $this */
$installer = $this;
$installer->startSetup();

Mage::getConfig()->saveConfig('payment/maxipago_cc/payment_action', 'order');

$installer->endSetup();

