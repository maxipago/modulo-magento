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

class MaxiPago_Payment_Helper_Payment extends MaxiPago_Payment_Helper_Data
{
    /**
     * @param Mage_Payment_Model_Info $info
     * @param array $paymentData
     * @return array
     */
    public function getPaymentData(Mage_Payment_Model_Info $info)
    {
        $paymentData = array();

        /** @var  $_order */
        $amount = $info->getOrder()->getBaseGrandTotal();

        switch ($info->getMethod()) {
            case 'maxipago_cc':
                //If the store will be responsible for the installments, send the full amount to gateway
                if ($this->getConfig('installment_type', $info->getMethod()) == 'N') {
                    $amount = $info->getAdditionalInformation('cc_installment_value') * $info->getAdditionalInformation('cc_installments');
                }

                $paymentData['expiration_month'] = $info->getData('cc_exp_month');
                $paymentData['expiration_year'] = $info->getData('cc_exp_year');
                $paymentData['number'] = $info->getData('cc_number');
                $paymentData['cid'] = $info->getData('cc_cid');
                $paymentData['installment_count'] = $info->getAdditionalInformation('cc_installments');
                $paymentData['owner'] = $info->getData('cc_owner');
                $paymentData['save_card'] = $info->getAdditionalInformation('save_card');
                break;
            case 'maxipago_dc':
                $paymentData['expiration_month'] = $info->getData('dc_exp_month');
                $paymentData['expiration_year'] = $info->getData('dc_exp_year');
                $paymentData['number'] = $info->getData('dc_number');
                $paymentData['cid'] = $info->getData('dc_cid');
                $paymentData['owner'] = $info->getData('cc_owner');
                break;
            case 'maxipago_eft':
            case 'maxipago_ticket':
            case 'maxipago_redepay':
            case 'maxipago_checkout2':
                $paymentData['transaction_url'] = $info->getAdditionalInformation('transaction_url');
                break;
        }

        $paymentData['amount'] = number_format($amount, 2, '', '');
        return $paymentData;
    }
}
