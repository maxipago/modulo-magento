<?php

/**
 * Bizcommerce Desenvolvimento de Plataformas Digitais Ltda - Epp
 *
 * INFORMAÇÕES SOBRE LICENÇA
 *
 * Open Software License (OSL 3.0).
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Não edite este arquivo caso você pretenda atualizar este módulo futuramente
 * para novas versões.
 *
 * @category      maxiPago!
 * @package       MaxiPago_Payment
 * @author        Thiago Contardi <thiago@contardi.com.br>
 *
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class MaxiPago_Payment_Block_Adminhtml_System_Config_Settings_Comment
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $instructions = '<div class="instructions">
                            <strong>IMPORTANTE</strong>
                            <p>Para funcionar as URLs de retorno</p>
                            <p>É preciso configurar no maxiPago! as URLs de serviço que são</p>
                            <dl>
                                <dt>Serviço de Notificação</dt>
                                <dd>%s</dd>

                                <dt>URL Sucesso</dt>
                                <dd>%s</dd>

                                <dt>URL Erro</dt>
                                <dd>%s</dd>
                            </dl>
                        </div>';

        $index = Mage::getUrl('maxipago/notifications/index');
        $success = Mage::getUrl('maxipago/notifications/success');
        $error = Mage::getUrl('maxipago/notifications/error');

        return sprintf($instructions, $index, $success, $error);
    }
}
