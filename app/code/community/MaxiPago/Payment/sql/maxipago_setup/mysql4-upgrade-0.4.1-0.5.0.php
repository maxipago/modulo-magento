<?php
/** @var Mage_Eav_Model_Entity_Setup $this */
$installer = $this;
$installer->startSetup();

try {

    $orderItemTable = $this->getTable('sales/order_item');
    $code = 'maxipago_seller_id';
    if(!$installer->getConnection()->tableColumnExists($orderItemTable, $code)) {
        $installer->getConnection()->addColumn($orderItemTable, $code, "VARCHAR(255) NOT NULL");
    }

    $code = 'maxipago_seller_mdr';
    if(!$installer->getConnection()->tableColumnExists($orderItemTable, $code)) {
        $installer->getConnection()->addColumn($orderItemTable, $code, "DOUBLE(15,4) NOT NULL");
    }

    $code = 'maxipago_seller_installments';
    if(!$installer->getConnection()->tableColumnExists($orderItemTable, $code)) {
        $installer->getConnection()->addColumn($orderItemTable, $code, "INT(11) NOT NULL");
    }

    $sellersTable = 'maxipago_sellers';
    if(!$installer->tableExists($sellersTable)) {

        $table = $installer->getConnection()
            ->newTable($installer->getTable($sellersTable))
            ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'identity'  => true,
                'unsigned'  => true,
                'nullable'  => false,
                'primary'   => true,
            ), 'Id')
            ->addColumn('seller_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, '255', array(
                'nullable'  => false,
            ), 'MaxiPago Seller ID')
            ->addColumn('name', Varien_Db_Ddl_Table::TYPE_VARCHAR, '255', array(
                'nullable'  => false,
            ), 'Seller Name.')
            ->addColumn('seller_mdr', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
                'nullable'  => false,
            ), 'Seller MDR.')
            ->addColumn('days_to_pay', Varien_Db_Ddl_Table::TYPE_INTEGER, '11', array(
                'nullable'  => false,
            ), 'Seller Days to Pay.')
            ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
                'nullable'  => false,
            ), 'Created')
            ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
                'nullable'  => false,
            ), 'Updated');

        $installer->getConnection()->createTable($table);
    }


    $productAttribute = 'maxipago_seller';
    if (!$installer->getAttribute(Mage_Catalog_Model_Product::ENTITY, $productAttribute, 'attribute_id')) {
        //I'm not using source model because if you disable the module magento throw an error
        $installer->addAttribute(
            Mage_Catalog_Model_Product::ENTITY,
            $productAttribute,
            array(
                'input'             => 'hidden',
                'type'              => 'text',
                'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
                'visible'           => false,
                'required'          => false,
                'user_defined'      => false,
                'default'           => '',
                'used_in_product_listing'    => true
            )
        );
    }

} catch (Exception $e) {
    Mage::logException($e);
}


$installer->endSetup();

