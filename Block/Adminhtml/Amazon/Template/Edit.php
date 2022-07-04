<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

abstract class Edit extends AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data */
    protected $dataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->addTranslations([
            'Do not show any more' => $this->__('Do not show this message anymore'),
            'Save Policy' => $this->__('Save Policy')
        ]);

        return parent::_beforeToHtml();
    }

    protected function getSaveConfirmationText($id = null)
    {
        $saveConfirmation = '';
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');

        if ($id === null && $template !== null) {
            $id = $template->getId();
        }

        if ($id) {
            $saveConfirmation = $this->dataHelper->escapeJs(
                $this->__('<br/>
<b>Note:</b> All changes you have made will be automatically applied to all M2E Pro Listings where this Policy is used.
                ')
            );
        }

        return $saveConfirmation;
    }
}
