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

class MaxiPago_CheckoutApi_Model_Logger
{	
	/**
	 * log facility
	 *
	 * @param string $message
	 * @param integer $level
	 * @param string $file
	 */
	public static function log($message, $level = null, $file = '')
	{
		static $loggers = array();
	
		$level  = is_null($level) ? Zend_Log::INFO : $level;
		$file = empty($file) ? 'maxipago.log' : $file;
	
		try {
			if (!isset($loggers[$file])) {
				$logDir  = Mage::getBaseDir('var') . DIRECTORY_SEPARATOR . 'log';
				$logFile = $logDir . DIRECTORY_SEPARATOR . $file;
	
				if (!is_dir($logDir)) {
					mkdir($logDir);
					chmod($logDir, 0750);
				}
	
				if (!file_exists($logFile)) {
					file_put_contents($logFile, '');
					chmod($logFile, 0640);
				}
	
				$format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
				$formatter = new Zend_Log_Formatter_Simple($format);
				$writer = new Zend_Log_Writer_Stream($logFile);
				$writer->setFormatter($formatter);
				$loggers[$file] = new Zend_Log($writer);
			}
	
			if (is_array($message) || is_object($message)) {
				$message = print_r($message, true);
			}
	
			$loggers[$file]->log($message, $level);
		}
		catch (Exception $e) {
		}
	}
}