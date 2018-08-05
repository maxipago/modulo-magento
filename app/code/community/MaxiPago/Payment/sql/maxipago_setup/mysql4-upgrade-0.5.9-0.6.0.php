<?php
/** @var Mage_Eav_Model_Entity_Setup $this */
$installer = $this;
$installer->startSetup();

try {

    $table = $this->getTable('maxipago/seller');
    $code = 'status';
    if(!$installer->getConnection()->tableColumnExists($table, $code)) {
        $installer->getConnection()->addColumn($table, $code, "VARCHAR(255) NOT NULL DEFAULT 'approved'");
    }

} catch (Exception $e) {
    Mage::logException($e);
}


$installer->endSetup();

