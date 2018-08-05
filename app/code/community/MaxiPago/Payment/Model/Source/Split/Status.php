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
class MaxiPago_Payment_Model_Source_Split_Status
{

    public function toOptionArray()
    {
        $options = array();
        foreach ($this->toArray() as $value => $label) {
            array_push(
                $options,
                array(
                    'value' => $value,
                    'label' => $label
                )
            );
        }
        return $options;
    }

    public function toArray()
    {
        /** @var MaxiPago_Payment_Helper_Data $_helper */
        $_helper = Mage::helper('maxipago');

        $options = array(
            'approved' =>  $_helper->__('Approved'),
            'denied' =>  $_helper->__('Denied'),
            'pending' =>  $_helper->__('Pending'),
        );

        return $options;
    }
}

