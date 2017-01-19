<?php

class MaxiPago_CheckoutApi_Model_Source_Cctype
{
	public function toOptionArray()
	{
		return array(
				array('value' => 'VI', 'label' => 'Visa'),
				array('value' => 'MC', 'label' => 'Mastercard'),
				array('value' => 'AM', 'label' => 'Amex'),
				array('value' => 'DC', 'label' => 'Diners Club'),
				array('value' => 'EL', 'label' => 'Elo'),
				array('value' => 'DI', 'label' => 'Discovery'),
				array('value' => 'HC', 'label' => 'Hipercard'),
		);
	}
	
	public function getValueArray()
	{
		$arr = [];
		foreach ($this->toOptionArray() as $v => $l) {
			$arr[] = $v;
		}
		
		return $arr;
	}
	
	public function getProcessors($ccType)
	{
		$processors = null;
		
		switch ($ccType)
		{
			case 'VI':
				$processors = array(
					array('value' => '1', 'label' => 'Simulador de Teste'),
					array('value' => '4', 'label' => 'Cielo'),
					array('value' => '5', 'label' => 'e.Rede'),
					array('value' => '6', 'label' => 'Elavon'),
					array('value' => '3', 'label' => 'GetNet'),
					array('value' => '2', 'label' => 'Redecard'),
				);
				break;
			case 'MC':
				$processors = array(
					array('value' => '1', 'label' => 'Simulador de Teste'),
					array('value' => '4', 'label' => 'Cielo'),
					array('value' => '5', 'label' => 'e.Rede'),
					array('value' => '6', 'label' => 'Elavon'),
					array('value' => '3', 'label' => 'GetNet'),
					array('value' => '2', 'label' => 'Redecard'),
				);
				break;
			case 'AM':
				$processors = array(
					array('value' => '1', 'label' => 'Simulador de Teste'),
					array('value' => '4', 'label' => 'Cielo'),
				);
				break;
			case 'DC':
				$processors = array(
					array('value' => '1', 'label' => 'Simulador de Teste'),
					array('value' => '4', 'label' => 'Cielo'),
					array('value' => '5', 'label' => 'e.Rede'),
					array('value' => '6', 'label' => 'Elavon'),
					array('value' => '2', 'label' => 'Redecard'),
				);
				break;
			case 'EL':
				$processors = array(
					array('value' => '1', 'label' => 'Simulador de Teste'),
					array('value' => '4', 'label' => 'Cielo'),
					array('value' => '3', 'label' => 'GetNet'),
				);
				break;
			case 'DI':
				$processors = array(
					array('value' => '1', 'label' => 'Simulador de Teste'),
					array('value' => '4', 'label' => 'Cielo'),
					array('value' => '6', 'label' => 'Elavon'),
					array('value' => '2', 'label' => 'Redecard'),
				);
				break;
			case 'HC':
				$processors = array(
					array('value' => '1', 'label' => 'Simulador de Teste'),
					array('value' => '5', 'label' => 'e.Rede'),
					array('value' => '2', 'label' => 'Redecard'),
				);
				break;
			default:
				$processors = array();
				break;
		}
		
		return $processors;
	}
}