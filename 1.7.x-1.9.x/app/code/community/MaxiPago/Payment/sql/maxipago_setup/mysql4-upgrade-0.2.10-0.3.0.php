<?php
/** @var Mage_Eav_Model_Entity_Setup $this */
$installer = $this;
$installer->startSetup();


try {
    $installer->run("ALTER TABLE  `{$this->getTable('sales/order')}` ADD  `maxipago_fraud_code` VARCHAR(255) NOT NULL");
    $installer->run("ALTER TABLE  `{$this->getTable('sales/order')}` ADD  `maxipago_fraud_status` VARCHAR(255) NOT NULL");
} catch (Exception $e) {
    Mage::logException($e);
}


$installer->endSetup();

