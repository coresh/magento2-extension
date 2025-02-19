<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Account as AmazonAccount;

/**
 * Class Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\InvoicesAndShipments
 */
class InvoicesAndShipments extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');

        $formData = $this->getFormData();

        $form = $this->_formFactory->create();

        $helpText = __(
            <<<HTML
    <p>Under this tab, you can enable Magento <i>Invoice/Shipment Creation</i> if you want M2E Pro to automatically
    create invoices and shipments in your Magento.</p>
HTML
        );

        if ($account->getChildObject()->getMarketplace()->getChildObject()->isVatCalculationServiceAvailable()) {
            $helpText .= $this->__(
                <<<HTML
    <p>Also, you can set up an <i>Automatic Invoice Uploading</i> to Amazon. Read the <a href="%url%"
    target="_blank">article</a> for more details.</p>
HTML
                ,
                $this->supportHelper->getSupportUrl('/support/solutions/articles/9000219394')
            );
        }

        $form->addField(
            'invoices_and_shipments',
            self::HELP_BLOCK,
            [
                'content' => $helpText,
            ]
        );

        $fieldset = $form->addFieldset(
            'invoices',
            [
                'legend' => __('Invoices'),
                'collapsable' => false,
            ]
        );

        if ($account->getChildObject()->getMarketplace()->getChildObject()->isVatCalculationServiceAvailable()) {
            $fieldset->addField(
                'auto_invoicing',
                'select',
                [
                    'label' => __('Invoice Uploading to Amazon'),
                    'title' => __('Invoice Uploading to Amazon'),
                    'name' => 'auto_invoicing',
                    'options' => [
                        AmazonAccount::AUTO_INVOICING_DISABLED => __('Disabled'),
                        AmazonAccount::AUTO_INVOICING_UPLOAD_MAGENTO_INVOICES =>
                            __('Upload Magento Invoices'),
                        AmazonAccount::AUTO_INVOICING_VAT_CALCULATION_SERVICE =>
                            __('Use VAT Calculation Service'),
                    ],
                    'value' => $formData['auto_invoicing'],
                ]
            );

            $fieldset->addField(
                'invoice_generation',
                'select',
                [
                    'container_id' => 'invoice_generation_container',
                    'label' => __('VAT Invoice Creation'),
                    'title' => __('VAT Invoice Creation'),
                    'name' => 'invoice_generation',
                    'class' => 'M2ePro-required-when-visible M2ePro-is-ready-for-document-generation',
                    'required' => true,
                    'values' => [
                        '' => '',
                        AmazonAccount::INVOICE_GENERATION_BY_AMAZON =>
                            __('I want Amazon to generate VAT Invoices'),
                        AmazonAccount::INVOICE_GENERATION_BY_EXTENSION =>
                            __('M2E Pro will generate and upload invoices'),
                    ],
                    'value' => '',
                    'tooltip' => __(
                        'Learn how to set up automatic invoice uploading in this
                               <a href="%1"  target="_blank">article</a>.',
                        $this->supportHelper->getSupportUrl('/support/solutions/articles/9000219394')
                    ),
                ]
            );

            $fieldset->addField(
                'invoicing_applied_value_line_tr',
                self::SEPARATOR,
                []
            );
        }

        $fieldset->addField(
            'create_magento_invoice',
            'select',
            [
                'label' => __('Magento Invoice Creation'),
                'title' => __('Magento Invoice Creation'),
                'name' => 'create_magento_invoice',
                'options' => [
                    0 => __('Disabled'),
                    1 => __('Enabled'),
                ],
                'tooltip' => __(
                    'Enable to automatically create Magento Invoices when order status is Unshipped/Partially Shipped.'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'shipments',
            [
                'legend' => __('Shipments'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'create_magento_shipment',
            'select',
            [
                'label' => __('Magento Shipment Creation'),
                'title' => __('Magento Shipment Creation'),
                'name' => 'create_magento_shipment',
                'options' => [
                    0 => __('Disabled'),
                    1 => __('Enabled'),
                ],
                'tooltip' => __(
                    'Enable to automatically create shipment for the Magento order when the associated order
                    on Channel is shipped.'
                ),
            ]
        );

        $form->setValues($formData);

        $form->setUseContainer(false);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    // ----------------------------------------

    protected function _prepareLayout()
    {
        $formData = $this->getFormData();

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Amazon/Account',
    ], function(){
        $('create_magento_invoice').value = {$formData['create_magento_invoice']};
    });
JS
        );

        return parent::_prepareLayout();
    }

    // ----------------------------------------

    protected function getFormData()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');

        $formData = $account ? array_merge($account->toArray(), $account->getChildObject()->toArray()) : [];
        $defaults = $this->modelFactory->getObject('Amazon_Account_Builder')->getDefaultData();

        return array_merge($defaults, $formData);
    }
}
