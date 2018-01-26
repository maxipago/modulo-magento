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
class MaxiPago_Payment_Helper_Data extends Mage_Core_Helper_Data
{
    const LOG_FILE = 'maxipago.log';

    protected $_api = null;

    protected $_interestMethods = array(
        'maxipago_cc'
    );

    protected $_availableMethods = array(
        'maxipago_cc',
        'maxipago_dc',
        'maxipago_ticket',
        'maxipago_eft',
        'maxipago_redepay',
        'maxipago_checkout2'
    );

    protected $_brands = array(
        'VI' => 'Visa',
        'MC' => 'Mastercard',
        'AM' => 'Amex',
        'DC' => 'Diners Club',
        'EL' => 'Elo',
        'DI' => 'Discover',
        'HC' => 'Hipercard',
        'AU' => 'Aura',
        'JC' => 'JCB',
        'CR' => 'Credz'
    );

    protected $_banks = array(
        '11' => 'Itaú',
        '12' => 'Bradesco',
        '13' => 'Banco do Brasil',
        '14' => 'HSBC',
        '15' => 'Santander',
        '16' => 'Caixa Econômica Federal',
        '17' => 'Bradesco',
        '18' => 'Itaú',
    );

    protected $_responseCodes = array(
        '0' => 'Pagamento Aprovado',
        '1' => 'Pagamento Reprovado',
        '2' => 'Pagamento Reprovado',
        '5' => 'Pagamento em análise',
        '1022' => 'Ocorreu um erro com a finalizadora, entre em contato com nossa equipe',
        '1024' => 'Erros, dados enviados inválidos, entre em contato com nossa equipe',
        '1025' => 'Erro nas credenciais de envio, entre em contato com nossa equipe',
        '2048' => 'Erro interno, entre em contato com nossa equipe',
        '4097' => 'Erro de tempo de execução, entre em contato com nossa equipe'
    );

    protected $_transactionStates = array(
        '1' => 'Em Progresso',
        '3' => 'Capturado',
        '6' => 'Autorizado',
        '7' => 'Recuzado',
        '9' => 'Anulado',
        '10' => 'Pago',
        '22' => 'Boleto Emitido',
        '34' => 'Boleto Visualizado',
        '35' => 'Boleto Pago com valor abaixo',
        '36' => 'Boleto Pago com valor acima',

        '4' => 'Pendente de Captura',
        '5' => 'Pendente de Autorização',
        '8' => 'Recusado',
        '11' => 'Pendente de Confirmação',
        '12' => 'Pendente de Revisão (verificar com o suporte)',
        '13' => 'Pendente de Revisão',
        '14' => 'Pendente de Captura (re-tentativa)',
        '16' => 'Pendente de Estorno',
        '18' => 'Pendente Anulação',
        '19' => 'Pendente Anulação (re-tentativa)',
        '29' => 'Pendente Autenticação',
        '30' => 'Autenticado',
        '31' => 'Pendente Estorno (retentativa)',
        '32' => 'Autenticação em Progresso',
        '33' => 'Autenticação Enviada',
        '38' => 'File submission Pendente Reversal',
        '44' => 'Análise de Fraude Aprovada',
        '45' => 'Análise de Fraude Recusada',
        '46' => 'Análise de Fraude Revisão'
    );

    protected $_countryCodes = array(
        'AD' => '376',
        'AE' => '971',
        'AF' => '93',
        'AG' => '1268',
        'AI' => '1264',
        'AL' => '355',
        'AM' => '374',
        'AN' => '599',
        'AO' => '244',
        'AQ' => '672',
        'AR' => '54',
        'AS' => '1684',
        'AT' => '43',
        'AU' => '61',
        'AW' => '297',
        'AZ' => '994',
        'BA' => '387',
        'BB' => '1246',
        'BD' => '880',
        'BE' => '32',
        'BF' => '226',
        'BG' => '359',
        'BH' => '973',
        'BI' => '257',
        'BJ' => '229',
        'BL' => '590',
        'BM' => '1441',
        'BN' => '673',
        'BO' => '591',
        'BR' => '55',
        'BS' => '1242',
        'BT' => '975',
        'BW' => '267',
        'BY' => '375',
        'BZ' => '501',
        'CA' => '1',
        'CC' => '61',
        'CD' => '243',
        'CF' => '236',
        'CG' => '242',
        'CH' => '41',
        'CI' => '225',
        'CK' => '682',
        'CL' => '56',
        'CM' => '237',
        'CN' => '86',
        'CO' => '57',
        'CR' => '506',
        'CU' => '53',
        'CV' => '238',
        'CX' => '61',
        'CY' => '357',
        'CZ' => '420',
        'DE' => '49',
        'DJ' => '253',
        'DK' => '45',
        'DM' => '1767',
        'DO' => '1809',
        'DZ' => '213',
        'EC' => '593',
        'EE' => '372',
        'EG' => '20',
        'ER' => '291',
        'ES' => '34',
        'ET' => '251',
        'FI' => '358',
        'FJ' => '679',
        'FK' => '500',
        'FM' => '691',
        'FO' => '298',
        'FR' => '33',
        'GA' => '241',
        'GB' => '44',
        'GD' => '1473',
        'GE' => '995',
        'GH' => '233',
        'GI' => '350',
        'GL' => '299',
        'GM' => '220',
        'GN' => '224',
        'GQ' => '240',
        'GR' => '30',
        'GT' => '502',
        'GU' => '1671',
        'GW' => '245',
        'GY' => '592',
        'HK' => '852',
        'HN' => '504',
        'HR' => '385',
        'HT' => '509',
        'HU' => '36',
        'ID' => '62',
        'IE' => '353',
        'IL' => '972',
        'IM' => '44',
        'IN' => '91',
        'IQ' => '964',
        'IR' => '98',
        'IS' => '354',
        'IT' => '39',
        'JM' => '1876',
        'JO' => '962',
        'JP' => '81',
        'KE' => '254',
        'KG' => '996',
        'KH' => '855',
        'KI' => '686',
        'KM' => '269',
        'KN' => '1869',
        'KP' => '850',
        'KR' => '82',
        'KW' => '965',
        'KY' => '1345',
        'KZ' => '7',
        'LA' => '856',
        'LB' => '961',
        'LC' => '1758',
        'LI' => '423',
        'LK' => '94',
        'LR' => '231',
        'LS' => '266',
        'LT' => '370',
        'LU' => '352',
        'LV' => '371',
        'LY' => '218',
        'MA' => '212',
        'MC' => '377',
        'MD' => '373',
        'ME' => '382',
        'MF' => '1599',
        'MG' => '261',
        'MH' => '692',
        'MK' => '389',
        'ML' => '223',
        'MM' => '95',
        'MN' => '976',
        'MO' => '853',
        'MP' => '1670',
        'MR' => '222',
        'MS' => '1664',
        'MT' => '356',
        'MU' => '230',
        'MV' => '960',
        'MW' => '265',
        'MX' => '52',
        'MY' => '60',
        'MZ' => '258',
        'NA' => '264',
        'NC' => '687',
        'NE' => '227',
        'NG' => '234',
        'NI' => '505',
        'NL' => '31',
        'NO' => '47',
        'NP' => '977',
        'NR' => '674',
        'NU' => '683',
        'NZ' => '64',
        'OM' => '968',
        'PA' => '507',
        'PE' => '51',
        'PF' => '689',
        'PG' => '675',
        'PH' => '63',
        'PK' => '92',
        'PL' => '48',
        'PM' => '508',
        'PN' => '870',
        'PR' => '1',
        'PT' => '351',
        'PW' => '680',
        'PY' => '595',
        'QA' => '974',
        'RO' => '40',
        'RS' => '381',
        'RU' => '7',
        'RW' => '250',
        'SA' => '966',
        'SB' => '677',
        'SC' => '248',
        'SD' => '249',
        'SE' => '46',
        'SG' => '65',
        'SH' => '290',
        'SI' => '386',
        'SK' => '421',
        'SL' => '232',
        'SM' => '378',
        'SN' => '221',
        'SO' => '252',
        'SR' => '597',
        'ST' => '239',
        'SV' => '503',
        'SY' => '963',
        'SZ' => '268',
        'TC' => '1649',
        'TD' => '235',
        'TG' => '228',
        'TH' => '66',
        'TJ' => '992',
        'TK' => '690',
        'TL' => '670',
        'TM' => '993',
        'TN' => '216',
        'TO' => '676',
        'TR' => '90',
        'TT' => '1868',
        'TV' => '688',
        'TW' => '886',
        'TZ' => '255',
        'UA' => '380',
        'UG' => '256',
        'US' => '1',
        'UY' => '598',
        'UZ' => '998',
        'VA' => '39',
        'VC' => '1784',
        'VE' => '58',
        'VG' => '1284',
        'VI' => '1340',
        'VN' => '84',
        'VU' => '678',
        'WF' => '681',
        'WS' => '685',
        'XK' => '381',
        'YE' => '967',
        'YT' => '262',
        'ZA' => '27',
        'ZM' => '260',
        'ZW' => '263'
    );

    public function getInterestMethods()
    {
        return $this->_interestMethods;
    }

    public function getTransactionState($code)
    {
        if (isset($this->_transactionStates[$code])) {
            return $this->_transactionStates[$code];
        }

        return null;
    }

    /**
     * @param string $code
     * @return array
     */
    public function getBanksAvailable($code = 'maxipago_eft')
    {
        $banks = array();
        $configBanks = Mage::getStoreConfig('payment/' . $code . '/' . 'banks');

        if ($configBanks) {
            $configBanks = explode(',', $configBanks);
            if (is_array($configBanks)) {
                foreach ($configBanks as $bank) {
                    $label = $this->getBank($bank);
                    array_push(
                        $banks,
                        array(
                            'value' => $bank,
                            'label' => $label,
                            'slug' => $this->slugify($label)
                        )
                    );
                }
            }
        }

        return $banks;
    }

    public function getBank($code)
    {
        if (isset($this->_banks[$code])) {
            return $this->_banks[$code];
        }

        return null;
    }

    /**
     * Slugify string
     *
     * @param string $phrase
     * @return string
     */
    public function slugify($str)
    {
        // Clean Currency Symbol
        $str = Mage::helper('core')->removeAccents($str);
        $urlKey = preg_replace('#[^0-9a-z+]+#i', '-', $str);
        $urlKey = strtolower($urlKey);
        $urlKey = trim($urlKey, '-');
        return $urlKey;
    }

    /**
     * @param $brand
     * @param string $code
     * @return int
     */
    public function getProcessor($brand, $code = 'maxipago_cc')
    {
        $multiprocessors = Mage::getStoreConfig('payment/' . $code . '/' . 'processors');
        $index = ($code == 'maxipago_dc') ? 'dc_type' : 'cc_type';
        if ($multiprocessors) {
            $processors = unserialize($multiprocessors);
            if (is_array($processors)) {
                foreach ($processors as $processor) {
                    if ($brand == $processor[$index]) {
                        return $processor['processor'];
                    }
                }
            }
        }

        return 0;
    }

    /**
     * @param string $code
     * @return array
     */
    public function getMethodsEnabled($code = 'maxipago_cc')
    {
        $methods = array();
        $multiprocessors = Mage::getStoreConfig('payment/' . $code . '/' . 'processors');

        $index = ($code == 'maxipago_dc') ? 'dc_type' : 'cc_type';
        if ($multiprocessors) {
            $processors = unserialize($multiprocessors);
            if (is_array($processors)) {
                foreach ($processors as $processor) {
                    $label = $this->getBrand($processor[$index]);
                    array_push(
                        $methods,
                        array(
                            'processor' => $processor['processor'],
                            'value' => $processor[$index],
                            'label' => $label,
                            'slug' => $this->slugify($label)
                        )
                    );
                }
            }
        }

        return $methods;

    }

    public function getBrand($code)
    {
        if (isset($this->_brands[$code])) {
            return $this->_brands[$code];
        }

        return null;
    }

    /**
     * Save at the DB the data of the transaction and the Boleto URL when the payment is made with boleto
     *
     * @param string $method
     * @param array $paymentData
     * @param $request
     * @param $return
     * @param null|string $transactionUrl
     * @param boolean $hasOrder
     */
    public function saveTransaction($method, $request, $response, $orderId = null, $transactionUrl = null)
    {
        try {
            $ticketUrl = null;
            $eftUrl = null;
            $redePayUrl = null;
            $checkoutUrl = null;

            $responseMessage = null;
            $responseCode = null;
            $maxipagoOrderId = null;


            if ($transactionUrl) {
                switch ($method) {
                    case 'ticket':
                        $ticketUrl = $transactionUrl;
                        break;
                    case 'eft':
                        $eftUrl = $transactionUrl;
                        break;
                    case 'redepay':
                        $redePayUrl = $transactionUrl;
                        break;
                    case 'checkout':
                        $checkoutUrl = $transactionUrl;
                        break;
                }
            }

            if (is_array($request)) {

                if (isset($request['number'])) {
                    $request['number'] = substr($request['number'], 0, 6) . 'XXXXXX' . substr($request['number'], -4, 4);
                }

                if (isset($request['cvvNumber'])) {
                    $request['cvvNumber'] = 'XXX';
                }

                $request = json_encode($request);
            }

            if (is_array($response)) {
                $responseMessage = isset($response['responseMessage']) ? $response['responseMessage'] : null;
                $responseCode = isset($response['responseCode']) ? $response['responseCode'] : null;
                $maxipagoOrderId = isset($response['orderID']) ? $response['orderID'] : null;
                $response = json_encode($response);
            }

            /** @var MaxiPago_Payment_Model_Transaction $transaction */
            $transaction = Mage::getModel('maxipago/transaction');
            $data = array(
                'order_id' => $orderId,
                'maxipago_order_id' => $maxipagoOrderId,
                'ticket_url' => $ticketUrl,
                'eft_url' => $eftUrl,
                'redepay_url' => $redePayUrl,
                'checkout_url' => $checkoutUrl,
                'method' => $method,
                'request' => $request,
                'response' => $response,
                'response_code' => $responseCode,
                'response_message' => $responseMessage,
            );

            $transaction->setData($data);
            $transaction->save();

        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param $customerId
     * @return MaxiPago_Payment_Model_Resource_Card_Collection
     */
    public function getSavedCards($customerId)
    {
        /** @var MaxiPago_Payment_Model_Resource_Card_Collection $collection */
        $collection = Mage::getModel('maxipago/card')->getCollection();
        $collection->addFieldToFilter('customer_id', $customerId);
        return $collection;
    }

    /**
     * @param int $entity_id
     * @return MaxiPago_Payment_Model_Card
     */
    public function getSavedCard($entity_id)
    {
        /** @var MaxiPago_Payment_Model_Card $creditCard */
        $creditCard = Mage::getModel('maxipago/card')->load($entity_id);;
        return $creditCard;
    }

    /**
     * @return array
     */
    public function getInstallmentsInformation($code = 'maxipago_cc')
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $this->getSession()->getQuote();

        $installmentsInformation = array();
        $paymentAmount = $quote->getBaseGrandTotal();
        $installments = $this->getConfig('max_installments', $code);
        $installmentsWithoutInterest = $this->getConfig(
            'installments_without_interest_rate',
            $code
        );

        $irPerInstallments = false;
        if ($this->getConfig('use_interest_per_installments', $code) && $this->getConfig('interest_rate_per_installments', $code)) {
            $irPerInstallments = unserialize($this->getConfig('interest_rate_per_installments', $code));
        }

        for ($i = 1; $i <= $installments; $i++) {

            if (($installments > $installmentsWithoutInterest) && ($i > $installmentsWithoutInterest)) {
                $interestRate = $this->getConfig('interest_rate', $code);
                $value = $this->getInstallmentValue($paymentAmount, $i);
                if (!$value)
                    continue;

                if (
                    $irPerInstallments
                    && isset($irPerInstallments['value'])
                    && isset($irPerInstallments['value'][$i])
                ) {
                    $interestRate = $irPerInstallments['value'][$i];
                }
            } else {
                $interestRate = 0;
                $value = $paymentAmount / $i;
            }

            //If the instalment is lower than 5.00
            if ($value < $this->getConfig('minimum_installments_value', $code) && $i > 1) {
                continue;
            }

            $installmentsInformation[$i] = array(
                'installments' => $i,
                'value' => $value,
                'total' => $value * $i,
                'interest_rate' => $interestRate,
            );

        }

        return $installmentsInformation;
    }

    /**
     * @return Mage_Checkout_Model_Session|Mage_Adminhtml_Model_Session_Quote|Mage_Core_Model_Abstract
     */
    public function getSession()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return Mage::getSingleton('adminhtml/session_quote');
        }

        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get the maxipago Config
     * @param $config
     * @param string $path
     * @return mixed
     */
    public function getConfig($config, $path = 'maxipago_settings')
    {
        return Mage::getStoreConfig('payment/' . $path . '/' . $config);
    }

    /**
     * @param $paymentMethod
     * @param $total
     *
     * @param $installments
     * @return float|boolean
     */
    public function getInstallmentValue($total, $installments, $code = 'maxipago_cc')
    {
        $installmentsWithoutInterestRate = (int)$this->getConfig('installments_without_interest_rate', $code);
        $interestRate = $this->getConfig('interest_rate', $code);
        $interestType = $this->getConfig('interest_type', $code);

        if ($this->getConfig('use_interest_per_installments', $code) && $this->getConfig('interest_rate_per_installments', $code)) {
            $irPerInstallments = unserialize($this->getConfig('interest_rate_per_installments', $code));
            if (!isset($irPerInstallments['value']) || !isset($irPerInstallments['value'][$installments])) {
                return false;
            }
            $interestRate = ($irPerInstallments['value'][$installments] / $installments);
            $interestType = 'simple';
        }

        $interestRate = (float)(str_replace(',', '.', $interestRate)) / 100;

        if ($installments > 0) {
            $valorParcela = $total / $installments;
        } else {
            $valorParcela = $total;
        }

        try {
            if ($installments > $installmentsWithoutInterestRate && $interestRate > 0 && $installmentsWithoutInterestRate > 0) {
                switch ($interestType) {
                    case 'price':
                        $value = $total * (($interestRate * pow((1 + $interestRate), $installments)) / (pow((1 + $interestRate), $installments) - 1));
                        $valorParcela = round($value, 2);
                        break;
                    case 'compound':
                        //M = C * (1 + i)^n
                        $valorParcela = ($total * pow(1 + $interestRate, $installments)) / $installments;
                        break;
                    case 'simple':
                        //M = C * ( 1 + ( i * n ) )
                        $valorParcela = ($total * (1 + ($installments * $interestRate))) / $installments;
                        break;
                }
            }


        } catch (Exception $e) {
            $this->log($e->getMessage());
        } finally {
            return $valorParcela;
        }
    }

    /**
     * Log the message
     * @param string $message
     * @param string $file
     */
    public function log($message, $file = null)
    {
        $file = ($file) ? $file : self::LOG_FILE;
        if ($this->getConfig('debug')) {
            if (is_array($message)) {
                if (isset($message['number'])) {
                    $message['number'] = substr($message['number'], 0, 6) . 'XXXXXX' . substr($message['number'], -4, 4);
                }

                if (isset($message['cvvNumber'])) {
                    $message['cvvNumber'] = 'XXX';
                }

                if (isset($message['token'])) {
                    $message['token'] = 'XXX';
                }
            } else {
                $message = $this->formatLog($message);
                $message = $this->formatLog($message, 'cvvNumber');
                $message = $this->formatLog($message, 'token');
            }


            Mage::log($message, Zend_Log::INFO, $file);
        }
    }

    /**
     * Remove credit card and CVV Number from string
     * @param $xml
     * @param string $tag
     * @return mixed
     */
    protected function formatLog($xml, $tag = 'number')
    {
        $xml = preg_replace_callback(
            '/<' . $tag . '>(.*)<\/' . $tag . '>/m',
            function($matches) use ($tag){
                $number = $matches[1];
                if (isset($matches[1]) && !empty($matches[1])) {
                    if (strlen($number) > 10) {
                        $number = substr($number, 0, 6) . 'XXXXXX' . substr($number, -4, 4);
                    } else {
                        $number = 'XXXX';
                    }
                    return '<' . $tag . '>' . $number . '</' . $tag . '>';
                }
                return false;
            },
            $xml
        );

        return $xml;
    }

    /**
     * @return array
     */
    public function getAvailableMethods()
    {
        return $this->_availableMethods;
    }

    /**
     * @return MaxiPago_Payment_Model_Api|Mage_Core_Model_Abstract
     */
    public function getApi()
    {
        if (!$this->_api) {
            $this->_api = Mage::getModel('maxipago/api');
        }

        return $this->_api;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    public function getPullReportButton($order)
    {
        /** @var Mage_Core_Block_Template $block */
        $block = Mage::app()->getLayout()->createBlock('core/template')->setOrder($order)->setTemplate('maxipago/info/report/button.phtml');
        if ($block) {
            return $block->toHtml();
        }

        return '';
    }

    /**
     * @return null
     */
    public function getTaxvatValue($forceCustomer = false)
    {
        $quote = $this->getSession()->getQuote();
        $taxvatValue = null;
        $showTaxvatField = $this->getConfig('show_taxvat_field');

        if (!$showTaxvatField || $forceCustomer) {

            $attributeCode = $this->getConfig('cpf_customer_attribute');
            $isCorporate = $this->isCorporate();
            if ($isCorporate) {
                $attributeCode = $this->getConfig('cnpj_customer_attribute');
            }

            if ($quote->getCustomer() && $quote->getCustomer()->getId()) {
                /** @var Mage_Customer_Model_Customer $_customer */
                $_customer = Mage::getModel('customer/customer')->load($quote->getCustomer()->getId());

                //If the value is numeric, verify the text of attribute and compare
                $taxvatValue = $_customer->getResource()->getAttribute($attributeCode)->getFrontend()->getValue($_customer);
            } else {
                $taxvatValue = $quote->getData('customer_' . $attributeCode);
            }
        }

        return $taxvatValue;
    }

    /**
     * @return bool
     */
    public function isCorporate()
    {
        $isCorporate = false;
        $attributeCode = $this->getConfig('customer_attribute_type');
        $attributeCodeValue = $this->getConfig('customer_attribute_type_value_corporate');
        //Verify if the type of buyer attribute is set and if is corporate
        if (
            $attributeCode
            && $attributeCodeValue
        ) {
            $quote = $this->getSession()->getQuote();
            if ($quote->getCustomer() && $quote->getCustomer()->getId()) {
                /** @var Mage_Customer_Model_Customer $_customer */
                $_customer = Mage::getModel('customer/customer')->load($quote->getCustomer()->getId());
                $customerType = $_customer->getData($attributeCode);
                $isCorporate = ($customerType == $attributeCodeValue);

                //If the value is numeric, verify the text of attribute and compare
                if (is_numeric($customerType) && !$isCorporate) {
                    $_customerTypeValue = $_customer->getResource()->getAttribute($attributeCode)->getFrontend()->getValue($_customer);
                    $isCorporate = ($customerType == $_customerTypeValue);
                }
            } else {
                $customerType = $quote->getData('customer_' . $attributeCode);
                $isCorporate = ($customerType == $attributeCodeValue);
            }
        }
        return $isCorporate;
    }

    public function getCountryCode($country = 'BR')
    {
        return isset($this->_countryCodes[$country]) ? $this->_countryCodes[$country] : null;
    }

    public function getPhoneNumber($telefone)
    {
        if (strlen($telefone) >= 10) {
            $telefone = preg_replace('/^D/','', $telefone);
            $telefone = substr($telefone, 2, strlen($telefone) -2);

        }
        return $telefone;
    }

    public function getAreaNumber($telefone)
    {
        $telefone = preg_replace('/^D/','', $telefone);
        $telefone = substr($telefone, 0, 2);
        return $telefone;
    }

    /**
     * @param $dob
     * @param $format
     */
    public function getDate($dob, $format = 'Y-m-d')
    {
        $date = new DateTime($dob);
        return $date->format($format);
    }
}