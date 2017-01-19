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

class MaxiPago_CheckoutApi_Block_Payment extends Mage_Core_Block_Template
{
    protected $order_number;
    protected $transaction_id;
    protected $url_payment;
    protected $typeful_line;
    protected $status_id;
    protected $status_name;
    protected $payment_method_id;
    protected $payment_method_name;
    protected $payment_title;

    public function getOrderNumber()
    {
        return $this->order_number;
    }

    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    public function getUrlPayment()
    {
        return $this->url_payment;
    }

    public function getTypefulLine()
    {
        return $this->typeful_line;
    }

    public function getStatusId()
    {
        return $this->status_id;
    }

    public function getStatusName()
    {
        return $this->status_name;
    }

    public function getPaymentMethodId()
    {
        return $this->payment_method_id;
    }

    public function getPaymentMethodName()
    {
        return $this->payment_method_name;
    }

    public function getPaymentTitle()
    {
        return $this->payment_title;
    }

    protected function getPayment()
    {
        $standard = Mage::getModel('checkoutapi/'.$this->getRequest()->getParam("type"));
        $response = Mage::getSingleton('core/session')->getResponseMp();

        $xml = simplexml_load_string($response);
        $this->order_number = str_replace($standard->getConfigData('prefixo'),'',$xml->referenceNum);
        $this->transaction_id = $xml->transactionID;
        $this->url_payment = (isset($xml->onlineDebitUrl))?$xml->onlineDebitUrl:$xml->boletoUrl;
        $this->payment_title = $standard->getConfigData('title');
        $this->status_id = $xml->responseCode;
        
        switch (intval($xml->responseCode)){
            case 0:
                    if($xml->responseMessage == 'AUTHORIZED'){
                        $this->status_name = 'Pendente';
                    }else{
                        $this->status_name = 'Aprovado';
                    }
                break;
            case 1:
            case 2:
            case 1022:
            case 1024:
            case 1025:
            case 2048:
            case 4097:
                    $this->status_name = 'Cancelado';
                break;
            default:
                    $this->status_name = 'Aguardando Pagamento';
                break;
        }

        if(isset($xml->onlineDebitUrl)) {
            $this->payment_method_name = 'Transferência Online';
            $this->payment_method_id = 'TEF';
            $this->status_name = 'Aguardando Pagamento';
        }
        elseif(isset($xml->boletoUrl)) {
            $this->payment_method_name = 'Boleto Bancário' ;
            $this->payment_method_id = 'BL';
			$this->status_name = 'Aguardando Pagamento';
        }
        else {
            $this->payment_method_name = 'Cartão de Crédito';
            $this->payment_method_id = 'CC';
        }
    }

}