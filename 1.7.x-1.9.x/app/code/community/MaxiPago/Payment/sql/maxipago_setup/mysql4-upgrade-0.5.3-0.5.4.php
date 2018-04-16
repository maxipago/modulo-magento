<?php
/** @var Mage_Eav_Model_Entity_Setup $this */
$installer = $this;
$installer->startSetup();

try {

    $table = $this->getTable('maxipago/seller');
    $code = 'installments';
    if(!$installer->getConnection()->tableColumnExists($table, $code)) {
        $installer->getConnection()->addColumn($table, $code, "TINYINT(4) NOT NULL AFTER `seller_mdr`");
    }

} catch (Exception $e) {
    Mage::logException($e);
}


$installer->endSetup();

