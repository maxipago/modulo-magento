<?php

class MaxiPago_CheckoutApi_Block_Adminhtml_System_Config_Form_Field_Multiprocessor
	extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	protected $_rendererCcType;
	protected $_redererProcessor;

	public function __construct()
    {
    	parent::__construct();
    	$this->setTemplate('maxipago/checkoutapi/config/form/field/multiprocessor.phtml');
	}
	
	public function getCcTypes()
	{
		return Mage::getSingleton('checkoutapi/source_cctype')->toOptionArray();
	}
	
	public function getProcessors()
	{
		return Mage::getSingleton('checkoutapi/source_processorid')->toOptionArray();
	}
	
	public function getCcTypeProcessors()
	{
		$ccTypeProcessors = array();
		
		$ccTypes = $this->getCcTypes();
		foreach ($ccTypes as $ccType)
		{
			$ccTypeProcessors[] = array(
					'ccType' => $ccType['value'], 
					'processors' => Mage::getSingleton('checkoutapi/source_cctype')->getProcessors($ccType['value'])
			);
		}
		
		return $ccTypeProcessors;
	}
	
	protected function _prepareToRender()
	{
		$this->addColumn('cc_type', array(
				'label' => Mage::helper('checkoutapi')->__('Bandeira'),
				'renderer' => $this->_getCcTypeRenderer(),
		));
		$this->addColumn('processor', array(
				'label' => Mage::helper('checkoutapi')->__('Adquirente'),
				'renderer' => $this->_getProcessorRenderer(),
		));

		$this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('checkoutapi')->__('Adicionar novo');
	}

	protected function  _getCcTypeRenderer()
	{
		if (!$this->_rendererCcType) {
			$this->_rendererCcType = $this->getLayout()->createBlock(
				'checkoutapi/adminhtml_system_config_form_field_cctype', '',
				array('is_render_to_js_template' => true)
			);
		}
		return $this->_rendererCcType;
	}
	
	protected function  _getProcessorRenderer()
	{
		if (!$this->_redererProcessor) {
			$this->_redererProcessor = $this->getLayout()->createBlock(
				'checkoutapi/adminhtml_system_config_form_field_processor', '',
				array('is_render_to_js_template' => true)
			);
		}
		return $this->_redererProcessor;
	}

	protected function _prepareArrayRow(Varien_Object $row)
	{
		$row->setData(
			'option_extra_attr_' . $this->_getCcTypeRenderer()
			->calcOptionHash($row->getData('cc_type')),
			'selected="selected"'
		);
		
		$row->setData(
			'option_extra_attr_' . $this->_getProcessorRenderer()
			->calcOptionHash($row->getData('processor')),
			'selected="selected"'
		);
	}
}