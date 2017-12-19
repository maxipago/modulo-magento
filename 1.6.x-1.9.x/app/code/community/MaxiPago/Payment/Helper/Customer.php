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

class MaxiPago_Payment_Helper_Customer extends Mage_Core_Helper_Data
{
    protected $_helper;

    protected $_regions = array(
        'ACRE' => 'AC',
        'ALAGOAS' => 'AL',
        'AMAPÁ' => 'AP',
        'AMAZONAS' => 'AM',
        'BAHIA' => 'BA',
        'CEARÁ' => 'CE',
        'DISTRITO FEDERAL' => 'DF',
        'ESPÍRITO SANTO' => 'ES',
        'GOIÁS' => 'GO',
        'MARANHÃO' => 'MA',
        'MATO GROSSO' => 'MT',
        'MATO GROSSO DO SUL' => 'MS',
        'MINAS GERAIS' => 'MG',
        'PARÁ' => 'PA',
        'PARAÍBA' => 'PB',
        'PARANÁ' => 'PR',
        'PERNAMBUCO' => 'PE',
        'PIAUÍ' => 'PI',
        'RIO DE JANEIRO' => 'RJ',
        'RIO GRANDE DO NORTE' => 'RN',
        'RIO GRANDE DO SUL' => 'RS',
        'RONDÔNIA' => 'RO',
        'RORAIMA' => 'RR',
        'SANTA CATARINA' => 'SC',
        'SÃO PAULO' => 'SP',
        'SERGIPE' => 'SE',
        'TOCATINS' => 'TO'
    );

    /**
     * @param Mage_Sales_Model_Order|Mage_Sales_Model_Quote $order
     * @param Mage_Sales_Model_Order_Payment|Mage_Payment_Model_Info $payment
     * @param string $type
     * @return array
     */
    public function getAddressData($order, $payment, $type = 'billing')
    {
        $customerId = $order->getCustomerId();
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);

        $data = array();
        $data[$type . 'Id'] = $customer->getId();
        $data[$type . 'Name'] = $customer->getFirstname() . ' ' . $customer->getLastname();
        $data[$type . 'Email'] = $customer->getEmail();
        $data[$type . 'BirthDate'] = $this->_getHelper()->getDate($customer->getDob());
        $data[$type . 'Gender'] = $customer->getGender() ? 'M' : 'F';
        $documentNumber = $this->digits($payment->getAdditionalInformation('cpf_cnpj'));
        $customerType = 'Individual';
        $documentType = 'CPF';

        if (!$this->getConfigData('show_taxvat_field')) {
            if ($this->_getHelper()->isCorporate()) {
                $customerType = 'Legal entity';
                $documentType = 'CNPJ';
            }
            $documentNumber = $this->_getHelper()->getTaxvatValue();
        } else {
            if (strlen($documentNumber) == '14') {
                $customerType = 'Legal entity';
                $documentType = 'CNPJ';
            }
        }

        $data[$type . 'Type'] = $customerType;//'Legal entity'
        $data[$type . 'DocumentType'] = $documentType;
        $data[$type . 'DocumentValue'] = $documentNumber;

        if ($type == 'shipping') {
            /** @var Mage_Sales_Model_Order_Address|Mage_Sales_Model_Quote_Address $address */
            $address = $order->getBillingAddress();
        } else {
            $isOrderVirtual = $order->getIsVirtual();
            /** @var Mage_Sales_Model_Order_Address|Mage_Sales_Model_Quote_Address $address */
            $address = $isOrderVirtual ? $order->getBillingAddress() : $order->getShippingAddress();
        }

        if (!$address) {
            $address = $customer->getAddresses()[0];
        }

        if ($address) {
            $regionCode = $this->getRegionCode($address);
            $street = $this->getStreet($address->getStreetFull());
            $telephone = $this->digits($address->getTelephone());
            $data[$type . 'PhoneType'] = 'Mobile';
            $data[$type . 'CountryCode'] = $this->_getHelper()->getCountryCode($address->getCountry());
            $data[$type . 'PhoneAreaCode'] = $this->_getHelper()->getAreaNumber($telephone);
            $data[$type . 'PhoneNumber'] = $this->_getHelper()->getPhoneNumber($telephone);
            $data[$type . 'Address'] = $street['address1'];
            $data[$type . 'Address2'] = $street['address2'];
            $data[$type . 'District'] = $street['district'];
            $data[$type . 'City'] = $address->getCity();
            $data[$type . 'State'] = $regionCode;
            $data[$type . 'Zip'] = $address->getPostcode();
            $data[$type . 'PostalCode'] = $address->getPostcode();
            $data[$type . 'Country'] = $address->getCountry();
        }

        return $data;
    }


    /**
     * @param $customer
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getCustomerData($customerId, $format = 'd/m/Y')
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);

        $data = array(
            'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
            'email' => $customer->getEmail(),
            'firstName' => $customer->getFirstname(),
            'lastName' => $customer->getLastname(),
            'sex' => $customer->getGender() ? 'M' : 'F',
            'dob' => $this->_getHelper()->getDate($customer->getDob(), $format)
        );

        $address = $customer->getDefaultBillingAddress();
        if (!$address) {
            $address = $customer->getAddresses()[0];
        }

        if ($address) {
            $regionCode = $this->getRegionCode($address);

            $street = $this->getStreet($address->getStreet());
            $telephone = $this->digits($address->getTelephone());
            $mobile = $this->digits($address->getFax());
            $data['phone'] = $telephone;
            $data['mobile'] = $mobile;
            $data['address1'] = $street['address1'];
            $data['address2'] = $street['address2'];
            $data['district'] = $street['district'];
            $data['city'] = $address->getCity();
            $data['state'] = $regionCode;
            $data['postalcode'] = $this->digits($address->getPostcode());
            $data['zip'] = $this->digits($address->getPostcode());
            $data['country'] = $address->getCountry();
        }

        return $data;
    }

    /**
     * @param $email
     * @return false|Mage_Core_Model_Abstract
     */
    public function getCustomerByEmail($email)
    {
        $customer = Mage::getModel("customer/customer");
        $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        $customer->loadByEmail($email);

        return $customer;
    }

    /**
     * @param $string
     * @return mixed
     */
    protected function digits($string)
    {
        return preg_replace('/[^0-9]/', '', $string);
    }

    protected function getConfigData($config)
    {
        return Mage::getStoreConfig('payment/maxipago_settings/' . $config);
    }

    /**
     * @param array $street
     * @return array
     */
    protected function getStreet($street)
    {
        $address = array();
        $address['address1'] = $street[0];
        $address['address2'] = $street[1];
        $address['district'] = 'N/A';

        if (count($street) == 3) {
            $address['district'] = $street[2];
        } else if (count($street) == 4) {
            $address['address2'] = $street[1] . ' ' . $street[2];
            $address['district'] = $street[3];
        }

        return $address;
    }


    /**
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return array
     */
    protected function getRegionCode($address)
    {
        $regionCode = $address->getRegionCode();
        if (!$regionCode || strlen($regionCode) != 2) {
            $regionName = trim($address->getRegion());
            $regionCode = $regionName;
            if (strlen($regionName) != 2) {
                $regionName = strtoupper($regionName);
                $regionCode = isset($this->_regions[$regionName])
                    ? $this->_regions[$regionName]
                    : $this->decipherRegion($regionName);
            }
        }

        return $regionCode;
    }

    /**
     * In Brazil there's a possibility to record states un string
     * This method try to find the correct state
     * @param $region
     * @return string
     */
    public function decipherRegion($region)
    {
        $code = '';
        $region = strtoupper(Mage::helper('core')->removeAccents($region));

        switch ($region){
            case 'ACRE':
                $code = 'AC';
                break;
            case 'ALAGOAS':
                $code = 'AL';
                break;
            case 'AMAPA':
            case 'AMAP':
                $code = 'AP';
                break;
            case 'AMAZONAS':
            case 'AMAZONA':
                $code = 'AM';
                break;
            case 'BAHIA':
            case 'BAIA':
                $code = 'BA';
                break;
            case 'CEARA':
                $code = 'CE';
                break;
            case 'DISTRITO FEDERAL':
                $code = 'DF';
                break;
            case 'ESPIRITO SANTO':
                $code = 'ES';
                break;
            case 'GOIAS':
                $code = 'GO';
                break;
            case 'MARANHAO':
                $code = 'MA';
                break;
            case 'MATO GROSSO':
                $code = 'MT';
                break;
            case 'MATO GROSSO DO SUL':
                $code = 'MS';
                break;
            case 'MINAS GERAIS':
            case 'MINAS':
                $code = 'MG';
                break;
            case 'PARA':
                $code = 'PA';
                break;
            case 'PARAIBA':
                $code = 'PB';
                break;
            case 'PARANA':
                $code = 'PR';
                break;
            case 'PERNAMBUCO':
            case 'PERNANBUCO':
                $code = 'PE';
                break;
            case 'PIAUI':
                $code = 'PI';
                break;
            case 'RIO DE JANEIRO':
            case 'RIO JANEIRO':
            case 'RIO':
                $code = 'RJ';
                break;
            case 'RIO GRANDE DO NORTE':
                $code = 'RN';
                break;
            case 'RIO GRANDE DO SUL':
                $code = 'RS';
                break;
            case 'RONDONIA':
            case 'RONDONA':
                $code = 'RO';
                break;
            case 'RORAIMA':
                $code = 'RR';
                break;
            case 'SANTA CATARINA':
                $code = 'SC';
                break;
            case 'SAO PAULO':
                $code = 'SP';
                break;
            case 'SERGIPE':
                $code = 'SE';
                break;
            case 'TOCANTINS':
                $code = 'TO';
                break;
        }

        return $code;
    }

    /**
     * @return MaxiPago_Payment_Helper_Data|Mage_Core_Helper_Abstract
     */
    public function _getHelper()
    {
        if (!$this->_helper) {
            /** @var MaxiPago_Payment_Helper_Data _helper */
            $this->_helper = Mage::helper('maxipago');
        }

        return $this->_helper;
    }
}
