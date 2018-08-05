<?php
/** @var Mage_Eav_Model_Entity_Setup $this */
$installer = $this;
$installer->startSetup();


try {

    $table = $this->getTable('sales/order');
    $code = 'maxipago_fraud_code';
    if(!$installer->getConnection()->tableColumnExists($table, $code)) {
        $installer->getConnection()->addColumn($table, $code, "VARCHAR(255) NOT NULL");
    }

    $code = 'maxipago_fraud_status';
    if(!$installer->getConnection()->tableColumnExists($table, $code)) {
        $installer->getConnection()->addColumn($table, $code, "VARCHAR(255) NOT NULL");
    }

} catch (Exception $e) {
    Mage::logException($e);
}


$installer->endSetup();

