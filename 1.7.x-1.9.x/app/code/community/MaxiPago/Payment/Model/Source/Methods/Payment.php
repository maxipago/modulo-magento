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
 * @author        Thiago Contardi <thiago@contardi.com.br>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MaxiPago_Payment_Model_Source_Methods_Payment
{
    protected $_paymentMethods = null;

    public function toOptionArray()
    {
        return $this->getPaymentMethodList(array(
            'asLabelValue' => true,
            'withGroups' => false,
            'withBlankLine' => true,
        ));
    }

    /**
     * Retrieve all payment methods list as an array
     *
     * Possible output:
     * 1) assoc array as <code> => <title>
     * 2) array of array('label' => <title>, 'value' => <code>)
     * 3) array of array(
     *                 array('value' => <code>, 'label' => <title>),
     *                 array('value' => array(
     *                     'value' => array(array(<code1> => <title1>, <code2> =>...),
     *                     'label' => <group name>
     *                 )),
     *                 array('value' => <code>, 'label' => <title>),
     *                 ...
     *             )
     *
     * @param array $params
     * @return array
     */
    public function getPaymentMethodList($params = array())
    {
        if (!isset($params['sorted']))
            $params['sorted'] = true;
        if (!isset($params['asLabelValue']))
            $params['asLabelValue'] = false;
        if (!isset($params['withGroups']))
            $params['withGroups'] = true;
        if (!isset($params['all']))
            $params['all'] = false;
        if (!isset($params['withLabel']))
            $params['withLabel'] = true;
        if (!isset($params['store']))
            $params['store'] = null;
        if (!isset($params['withBlankLine']))
            $params['withBlankLine'] = false;

        /** @var $paymentHelper Mage_Payment_Helper_Data */
        $paymentHelper = Mage::helper('payment');

        $methods = array();
        $groups = array();
        $groupRelations = array();

        if ($params['withBlankLine']) {
            if (!$params['asLabelValue']) {
                $methods[''] = Mage::helper('adminhtml')->__('-- Please Select --');
            }
        }

        foreach ($paymentHelper->getPaymentMethods($params['store']) as $code => $data) {

            if (strpos($code, 'maxipago') !== false || !isset($data['active']))
                continue;

            if (!$params['all'] && isset($data['active']) && !$data['active'])
                continue;

            $paymentMethodLabel = $this->getPaymentMethodLabel($code);
            if ((isset($data['title']))) {
                $methods[$code] = $data['title'];
            } else {
                if ($paymentHelper->getMethodInstance($code)) {
                    $methods[$code] = $paymentHelper->getMethodInstance($code)->getConfigData('title', $params['store']);
                }
            }

            if (isset($methods[$code]) && $params['withLabel'] && $paymentMethodLabel && $paymentMethodLabel != $methods[$code]) $methods[$code] .= ' - ' . $paymentMethodLabel;

            if ($params['asLabelValue'] && $params['withGroups'] && isset($data['group'])) {
                $groupRelations[$code] = $data['group'];
            }

        }

        if ($params['asLabelValue'] && $params['withGroups']) {
            $groups = Mage::app()->getConfig()->getNode(Mage_Payment_Helper_Data::XML_PATH_PAYMENT_GROUPS)->asCanonicalArray();

            foreach ($groups as $code => $title) {
                if (!in_array($code, $groupRelations)) {
                    unset($groups[$code]);
                }
            }

            foreach ($groups as $code => $title) {
                $methods[$code] = $title; // for sorting, see below
            }
        }

        if ($params['sorted']) {
            asort($methods);
        }

        if ($params['asLabelValue']) {
            $labelValues = array();
            if ($params['withBlankLine']) {
                $labelValues[] = array(
                    'value' => '',
                    'label' => Mage::helper('adminhtml')->__('-- Please Select --')
                );
            }
            foreach ($methods as $code => $title) {
                $labelValues[$code] = array();
            }

            foreach ($methods as $code => $title) {
                $title = ($title) ? $title : $code;
                if (isset($groups[$code])) {
                    $labelValues[$code]['label'] = $title;
                } elseif (isset($groupRelations[$code])) {
                    unset($labelValues[$code]);
                    $labelValues[$groupRelations[$code]]['value'][$code] = array('value' => $code, 'label' => $title);
                } else {
                    $labelValues[$code] = array('value' => $code, 'label' => $title);
                }
            }
            return $labelValues;
        }

        return $methods;
    }

    public function getPaymentMethodLabel($code)
    {
        if (!$this->_paymentMethods) {
            /** @var Mage_Adminhtml_Model_Config $model */
            $model = Mage::getSingleton('adminhtml/config');
            $sectionName = 'payment';
            $sections = $model->getSections($sectionName);
            $this->_paymentMethods = $sections->{$sectionName}->{"groups"};
        }
        return $this->_paymentMethods->$code->label;
    }
}


