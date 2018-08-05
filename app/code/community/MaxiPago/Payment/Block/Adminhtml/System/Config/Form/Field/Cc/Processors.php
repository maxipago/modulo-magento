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
class MaxiPago_Payment_Block_Adminhtml_System_Config_Form_Field_Cc_Processors
	extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	protected $_rendererCcType;
	protected $_redererProcessor;

	public function __construct()
    {
    	parent::__construct();
    	$this->setTemplate('maxipago/form/field/cc/processors.phtml');
	}
	
	public function getCcTypes()
	{
	    /** @var MaxiPago_Payment_Model_Source_Cctype $ccType */
	    $ccType = Mage::getSingleton('maxipago/source_cctype');
		return $ccType->toOptionArray();
	}
	
	public function getProcessors()
	{
        /** @var MaxiPago_Payment_Model_Source_Cc_Processor $processor */
        $processor = Mage::getSingleton('maxipago/source_cc_processor');
		return $processor->toOptionArray();
	}
	
	public function getCcTypeProcessors()
	{
		$ccTypeProcessors = array();
		
		$ccTypes = $this->getCcTypes();

        /** @var MaxiPago_Payment_Model_Source_Cctype $sourceCcType */
        $sourceCcType = Mage::getSingleton('maxipago/source_cctype');

		foreach ($ccTypes as $ccType)
		{
			$ccTypeProcessors[] = array(
                'ccType' => $ccType['value'],
                'processors' => $sourceCcType->getProcessors($ccType['value'])
			);
		}
		
		return $ccTypeProcessors;
	}

	protected function _prepareToRender()
	{
		$this->addColumn('cc_type', array(
            'label' => Mage::helper('maxipago')->__('Brand'),
            'renderer' => $this->_getCcTypeRenderer(),
		));
		$this->addColumn('processor', array(
            'label' => Mage::helper('maxipago')->__('Processor'),
            'renderer' => $this->_getProcessorRenderer(),
		));

		$this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('maxipago')->__('Add new');
	}

    /**
     * @return MaxiPago_Payment_Block_Adminhtml_System_Config_Form_Field_Cctype|Mage_Core_Block_Abstract
     */
	protected function  _getCcTypeRenderer()
	{
		if (!$this->_rendererCcType) {
			$this->_rendererCcType = $this->getLayout()->createBlock(
				'maxipago/adminhtml_system_config_form_field_cctype', '',
				array('is_render_to_js_template' => true)
			);
		}
		return $this->_rendererCcType;
	}

    /**
     * @return MaxiPago_Payment_Block_Adminhtml_System_Config_Form_Field_Cc_Processor|Mage_Core_Block_Abstract
     */
	protected function  _getProcessorRenderer()
	{
		if (!$this->_redererProcessor) {
			$this->_redererProcessor = $this->getLayout()->createBlock(
				'maxipago/adminhtml_system_config_form_field_cc_processor', '',
				array('is_render_to_js_template' => true)
			);
		}
		return $this->_redererProcessor;
	}

	protected function _prepareArrayRow(Varien_Object $row)
	{
		$row->setData(
			'option_extra_attr_' . $this->_getCcTypeRenderer()->calcOptionHash($row->getData('cc_type')),
			'selected="selected"'
		);

		$row->setData(
			'option_extra_attr_' . $this->_getProcessorRenderer()->calcOptionHash($row->getData('processor')),
			'selected="selected"'
		);
	}
}