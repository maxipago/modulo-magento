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
class MaxiPago_Payment_Block_Adminhtml_System_Config_Form_Field_Dc_Processors
	extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	protected $_rendererDcType;
	protected $_redererProcessor;

	public function __construct()
    {
    	parent::__construct();
    	$this->setTemplate('maxipago/form/field/dc/processors.phtml');
	}
	
	public function getDcTypes()
	{
	    /** @var MaxiPago_Payment_Model_Source_Dctype $dcType */
	    $dcType = Mage::getSingleton('maxipago/source_dctype');
		return $dcType->toOptionArray();
	}
	
	public function getProcessors()
	{
        /** @var MaxiPago_Payment_Model_Source_Processor $processor */
        $processor = Mage::getSingleton('maxipago/source_dc_processor');
		return $processor->toOptionArray();
	}
	
	public function getDcTypeProcessors()
	{
		$dcTypeProcessors = array();
		
		$dcTypes = $this->getDcTypes();

        /** @var MaxiPago_Payment_Model_Source_Dctype $sourceDcType */
        $sourceDcType = Mage::getSingleton('maxipago/source_dctype');

		foreach ($dcTypes as $dcType)
		{
			$dcTypeProcessors[] = array(
                'dcType' => $dcType['value'],
                'processors' => $sourceDcType->getProcessors($dcType['value'])
			);
		}
		
		return $dcTypeProcessors;
	}

	protected function _prepareToRender()
	{
		$this->addColumn('dc_type', array(
            'label' => Mage::helper('maxipago')->__('Brand'),
            'renderer' => $this->_getDcTypeRenderer(),
		));
		$this->addColumn('processor', array(
            'label' => Mage::helper('maxipago')->__('Processor'),
            'renderer' => $this->_getProcessorRenderer(),
		));

		$this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('maxipago')->__('Add new');
	}

    /**
     * @return MaxiPago_Payment_Block_Adminhtml_System_Config_Form_Field_Dctype|Mage_Core_Block_Abstract
     */
	protected function  _getDcTypeRenderer()
	{
		if (!$this->_rendererDcType) {
			$this->_rendererDcType = $this->getLayout()->createBlock(
				'maxipago/adminhtml_system_config_form_field_dctype', '',
				array('is_render_to_js_template' => true)
			);
		}
		return $this->_rendererDcType;
	}

    /**
     * @return MaxiPago_Payment_Block_Adminhtml_System_Config_Form_Field_Processor|Mage_Core_Block_Abstract
     */
	protected function  _getProcessorRenderer()
	{
		if (!$this->_redererProcessor) {
			$this->_redererProcessor = $this->getLayout()->createBlock(
				'maxipago/adminhtml_system_config_form_field_dc_processor', '',
				array('is_render_to_js_template' => true)
			);
		}
		return $this->_redererProcessor;
	}

	protected function _prepareArrayRow(Varien_Object $row)
	{
		$row->setData(
			'option_extra_attr_' . $this->_getDcTypeRenderer()->calcOptionHash($row->getData('dc_type')),
			'selected="selected"'
		);

		$row->setData(
			'option_extra_attr_' . $this->_getProcessorRenderer()->calcOptionHash($row->getData('processor')),
			'selected="selected"'
		);
	}
}