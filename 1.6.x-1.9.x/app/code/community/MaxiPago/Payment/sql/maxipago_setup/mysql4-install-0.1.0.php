<?php
/** @var Mage_Eav_Model_Entity_Setup $this */
$installer = $this;
$installer->startSetup();

//General Settings
$sellerId = Mage::getStoreConfig('payment/maxipagocheckoutapi_global_config/sellerId');
$sellerKey = Mage::getStoreConfig('payment/maxipagocheckoutapi_global_config/sellerKey');
$secretKey = Mage::getStoreConfig('payment/maxipagocheckoutapi_global_config/secretKey');
$sandbox = Mage::getStoreConfig('payment/maxipagocheckoutapi_global_config/sandbox');
$log = Mage::getStoreConfig('payment/maxipagocheckoutapi_global_config/log_active');
Mage::getConfig()->saveConfig('payment/maxipago_settings/merchant_id', $sellerId);
Mage::getConfig()->saveConfig('payment/maxipago_settings/merchant_key', $sellerKey);
Mage::getConfig()->saveConfig('payment/maxipago_settings/merchant_secret_key', $secretKey);
Mage::getConfig()->saveConfig('payment/maxipago_settings/sandbox', $sandbox);
Mage::getConfig()->saveConfig('payment/maxipago_settings/debug', $log);

//CC
$ccActive = Mage::getStoreConfig('payment/maxipagocheckoutapi_creditcard/active');
$ccTitle = Mage::getStoreConfig('payment/maxipagocheckoutapi_creditcard/title');
$ccSoftDescriptor = Mage::getStoreConfig('payment/maxipagocheckoutapi_creditcard/softDescriptor');
$ccSortOrder = Mage::getStoreConfig('payment/maxipagocheckoutapi_creditcard/sort_order');
$isCcToken = Mage::getStoreConfig('payment/maxipagocheckoutapi_creditcard/isCCtoken');
$ccFraudCheck = Mage::getStoreConfig('payment/maxipagocheckoutapi_creditcard/fraudCheck');
$ccMultiprocessor = Mage::getStoreConfig('payment/maxipagocheckoutapi_creditcard/multiprocessor');
$ccChargeInterest = Mage::getStoreConfig('payment/maxipagocheckoutapi_creditcard/chargeInterest');
$ccProcessorType = Mage::getStoreConfig('payment/maxipagocheckoutapi_creditcard/processorType');
$ccProcessorType = ($ccProcessorType == 'authM') ? 'auth' : $ccProcessorType;
Mage::getConfig()->saveConfig('payment/maxipago_cc/active', $ccActive);
Mage::getConfig()->saveConfig('payment/maxipago_cc/can_save_cc', $isCcToken);
Mage::getConfig()->saveConfig('payment/maxipago_cc/fraud_check', $ccFraudCheck);
Mage::getConfig()->saveConfig('payment/maxipago_cc/cc_payment_action', $ccProcessorType);
Mage::getConfig()->saveConfig('payment/maxipago_cc/installments', $ccChargeInterest);
Mage::getConfig()->saveConfig('payment/maxipago_cc/processors', $ccMultiprocessor);
if ($ccTitle)
    Mage::getConfig()->saveConfig('payment/maxipago_cc/title', $ccTitle);
if ($ccSortOrder)
    Mage::getConfig()->saveConfig('payment/maxipago_cc/sort_order', $ccSortOrder);

//Ticket
$ticketActive = Mage::getStoreConfig('payment/maxipagocheckoutapi_bankslip/active');
$ticketBankSlip = Mage::getStoreConfig('payment/maxipagocheckoutapi_bankslip/bankSlip');
$ticketDueDays = Mage::getStoreConfig('payment/maxipagocheckoutapi_bankslip/daypayment');
$ticketTitle = Mage::getStoreConfig('payment/maxipagocheckoutapi_bankslip/title');
$ticketSortOrder = Mage::getStoreConfig('payment/maxipagocheckoutapi_bankslip/sort_order');
Mage::getConfig()->saveConfig('payment/maxipago_ticket/active', $ticketActive);
Mage::getConfig()->saveConfig('payment/maxipago_ticket/bank', $ticketBankSlip);
Mage::getConfig()->saveConfig('payment/maxipago_ticket/due_days', $ticketDueDays);
if ($ticketTitle)
    Mage::getConfig()->saveConfig('payment/maxipago_ticket/title', $ticketTitle);
if ($ticketSortOrder)
    Mage::getConfig()->saveConfig('payment/maxipago_ticket/sort_order', $ticketSortOrder);

////EFT
$eftActive = Mage::getStoreConfig('payment/maxipagocheckoutapi_tef/active');
$eftTitle = Mage::getStoreConfig('payment/maxipagocheckoutapi_tef/title');
$eftPaymentTef = Mage::getStoreConfig('payment/maxipagocheckoutapi_tef/paymentTef');
$eftSortOrder = Mage::getStoreConfig('payment/maxipagocheckoutapi_tef/sort_order');
Mage::getConfig()->saveConfig('payment/maxipago_eft/active', $eftActive);
Mage::getConfig()->saveConfig('payment/maxipago_eft/bank', $eftPaymentTef);
if ($eftTitle)
    Mage::getConfig()->saveConfig('payment/maxipago_eft/title', $eftTitle);
if ($eftSortOrder)
    Mage::getConfig()->saveConfig('payment/maxipago_eft/sort_order', $eftSortOrder);


////RedePay
$redePayActive = Mage::getStoreConfig('payment/maxipagocheckoutapi_redepay/active');
$redePayTitle = Mage::getStoreConfig('payment/maxipagocheckoutapi_redepay/title');
$redePaySortOrder = Mage::getStoreConfig('payment/maxipagocheckoutapi_redepay/sort_order');
Mage::getConfig()->saveConfig('payment/maxipago_redepay/active', $redePayActive);
if ($redePayTitle)
    Mage::getConfig()->saveConfig('payment/maxipago_redepay/title', $redePayTitle);
if ($redePaySortOrder)
    Mage::getConfig()->saveConfig('payment/maxipago_redepay/sort_order', $redePaySortOrder);

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('maxipago/card')}` (
  `entity_id` INT(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` INT(10) NOT NULL,
  `customer_id_maxipago` INT(10) NOT NULL,
  `token` VARCHAR(100) NOT NULL,
  `description` VARCHAR(60) NOT NULL,
  `brand` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
");

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('maxipago/customer')}` (
  `entity_id` INT(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` INT(10) NOT NULL,
  `customer_id_maxipago` INT(10) NOT NULL,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
");

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('maxipago/transaction')}` (
  `entity_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `method` VARCHAR(255) NOT NULL,
  `order_id` VARCHAR(255) NULL,
  `maxipago_order_id` VARCHAR(255) NULL,
  `ticket_url` TEXT,
  `eft_url` TEXT,
  `redepay_url` TEXT,
  `checkout_url` TEXT,
  `request` TEXT NOT NULL,
  `response` TEXT NOT NULL,
  `response_code` INT(10) UNSIGNED NOT NULL,
  `response_message` VARCHAR(100) NOT NULL,
  `created_at` DATETIME,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
");

/**
 * Add 'custom_attribute' attribute for entities
 */
$tables = array(
    $installer->getTable('sales/quote_address'),
    $installer->getTable('sales/order_address'),
    $installer->getTable('sales/quote'),
    $installer->getTable('sales/order'),
    $installer->getTable('sales/invoice'),
    $installer->getTable('sales/creditmemo')
);

$code = 'interest_amount';
foreach ($tables as $table) {
    if(!$installer->getConnection()->tableColumnExists($table, $code)){
        $installer->run("ALTER TABLE `".$table."` ADD `" . $code . "` DECIMAL( 10, 2 ) NOT NULL;");
        $installer->run("ALTER TABLE `".$table."` ADD `base_" . $code . "` DECIMAL( 10, 2 ) NOT NULL;");
    }
}

$installer->endSetup();

