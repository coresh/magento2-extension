<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Shipping\Edit\Form;

use Ess\M2ePro\Model\Ebay\Template\Shipping;

class Data extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $regionFactory;
    protected $ebayFactory;

    protected $_template = 'ebay/template/shipping/form/data.phtml';

    public $formData = [];
    public $marketplaceData = [];
    public $attributes = [];
    public $attributesByInputTypes = [];
    public $missingAttributes = [];
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    /**
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Magento $magentoHelper
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory
     * @param \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Module\Translation $translationHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        array $data = []
    ) {
        $this->regionFactory = $regionFactory;
        $this->ebayFactory = $ebayFactory;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->supportHelper = $supportHelper;
        $this->translationHelper = $translationHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->dataHelper = $dataHelper;
        $this->magentoHelper = $magentoHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayTemplateShippingEditFormData');

        $this->css->addFile('ebay/template.css');

        $this->formData = $this->getFormData();
        $this->marketplaceData = $this->getMarketplaceData();
        $this->attributes = $this->globalDataHelper->getValue('ebay_attributes');

        $this->attributesByInputTypes = [
            'text' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->attributes, ['text']),
            'text_select' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->attributes, ['text', 'select']),
            'text_price' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->attributes, ['text', 'price']),
            'text_weight' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->attributes, ['text', 'weight']),
            'text_price_select' => $this->magentoAttributeHelper
                ->filterByInputTypes(
                    $this->attributes,
                    ['text', 'price', 'select']
                ),
        ];

        $this->missingAttributes = $this->getMissingAttributes();
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        // ---------------------------------------

        $form->addField(
            'shipping_id',
            'hidden',
            [
                'name' => 'shipping[id]',
                'value' => (!$this->isCustom() && isset($this->formData['id'])) ?
                    (int)$this->formData['id'] : '',
            ]
        );

        $form->addField(
            'shipping_title',
            'hidden',
            [
                'name' => 'shipping[title]',
                'value' => $this->getTitle(),
            ]
        );

        $form->addField(
            'hidden_marketplace_id_' . $this->marketplaceData['id'],
            'hidden',
            [
                'name' => 'shipping[marketplace_id]',
                'value' => $this->marketplaceData['id'],
            ]
        );

        $form->addField(
            'is_custom_template',
            'hidden',
            [
                'name' => 'shipping[is_custom_template]',
                'value' => $this->isCustom() ? 1 : 0,
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // Location Block
        // ---------------------------------------

        $fieldSet = $form->addFieldset(
            'shipping_location_fieldset',
            ['legend' => __('Item Location'), 'collapsable' => true]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'country_custom_value',
            'hidden',
            [
                'name' => 'shipping[country_custom_value]',
                'value' => $this->formData['country_custom_value'],
            ]
        );

        $fieldSet->addField(
            'country_custom_attribute',
            'hidden',
            [
                'name' => 'shipping[country_custom_attribute]',
                'value' => $this->formData['country_custom_attribute'],
            ]
        );

        $fieldSet->addField(
            'country_mode',
            self::SELECT,
            [
                'name' => 'shipping[country_mode]',
                'label' => $this->__('Country'),
                'title' => $this->__('Country'),
                'values' => [
                    $this->getCountryOptions(),
                    $this->getAttributesOptions(
                        Shipping::COUNTRY_MODE_CUSTOM_ATTRIBUTE,
                        function ($attribute) {
                            return $this->formData['country_mode'] == Shipping::COUNTRY_MODE_CUSTOM_ATTRIBUTE
                                && $attribute['code'] == $this->formData['country_custom_attribute'];
                        }
                    ),
                ],
                'class' => 'required-entry',
                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldSet->addField(
            'postal_code_custom_attribute',
            'hidden',
            [
                'name' => 'shipping[postal_code_custom_attribute]',
                'value' => $this->formData['postal_code_custom_attribute'],
            ]
        );

        $defaultValue = '';
        if ($this->formData['postal_code_mode'] != Shipping::POSTAL_CODE_MODE_CUSTOM_ATTRIBUTE) {
            $defaultValue = $this->formData['postal_code_mode'];
        }

        $fieldSet->addField(
            'postal_code_mode',
            self::SELECT,
            [
                'name' => 'shipping[postal_code_mode]',
                'label' => $this->__('Zip/Postal Code'),
                'title' => $this->__('Zip/Postal Code'),
                'values' => [
                    ['label' => $this->__('None'), 'value' => Shipping::POSTAL_CODE_MODE_NONE],
                    ['label' => $this->__('Custom Value'), 'value' => Shipping::POSTAL_CODE_MODE_CUSTOM_VALUE],
                    $this->getAttributesOptions(
                        Shipping::POSTAL_CODE_MODE_CUSTOM_ATTRIBUTE,
                        function ($attribute) {
                            return $this->formData['postal_code_mode'] == Shipping::POSTAL_CODE_MODE_CUSTOM_ATTRIBUTE
                                && $attribute['code'] == $this->formData['postal_code_custom_attribute'];
                        }
                    ),
                ],
                'value' => $defaultValue,
                'class' => 'M2ePro-location-or-postal-required M2ePro-required-if-calculated',
                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldSet->addField(
            'postal_code_custom_value',
            'text',
            [
                'name' => 'shipping[postal_code_custom_value]',
                'label' => $this->__('Zip/Postal Code Value'),
                'title' => $this->__('Zip/Postal Code Value'),
                'value' => $this->formData['postal_code_custom_value'],
                'class' => 'M2ePro-required-when-visible input-text',
                'required' => true,
                'field_extra_attributes' => 'id="postal_code_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'address_custom_attribute',
            'hidden',
            [
                'name' => 'shipping[address_custom_attribute]',
                'value' => $this->formData['address_custom_attribute'],
            ]
        );

        $defaultValue = '';
        if ($this->formData['address_mode'] != Shipping::ADDRESS_MODE_CUSTOM_ATTRIBUTE) {
            $defaultValue = $this->formData['address_mode'];
        }

        $fieldSet->addField(
            'address_mode',
            self::SELECT,
            [
                'name' => 'shipping[address_mode]',
                'label' => $this->__('City, State'),
                'title' => $this->__('City, State'),
                'values' => [
                    ['label' => $this->__('None'), 'value' => Shipping::ADDRESS_MODE_NONE],
                    ['label' => $this->__('Custom Value'), 'value' => Shipping::ADDRESS_MODE_CUSTOM_VALUE],
                    $this->getAttributesOptions(
                        Shipping::ADDRESS_MODE_CUSTOM_ATTRIBUTE,
                        function ($attribute) {
                            return $this->formData['address_mode'] == Shipping::ADDRESS_MODE_CUSTOM_ATTRIBUTE
                                && $attribute['code'] == $this->formData['address_custom_attribute'];
                        }
                    ),
                ],
                'value' => $defaultValue,
                'class' => 'M2ePro-location-or-postal-required M2ePro-required-if-calculated',
                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldSet->addField(
            'address_custom_value',
            'text',
            [
                'name' => 'shipping[address_custom_value]',
                'label' => $this->__('City, State Value'),
                'title' => $this->__('City, State Value'),
                'value' => $this->formData['address_custom_value'],
                'class' => 'M2ePro-required-when-visible input-text',
                'field_extra_attributes' => 'id="address_custom_value_tr" style="display: none;"',
                'required' => true,
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // Domestic Shipping
        // ---------------------------------------

        $fieldSet = $form->addFieldset(
            'domestic_shipping_fieldset',
            ['legend' => __('Domestic Shipping'), 'collapsable' => true]
        );

        $fieldSet->addField(
            'local_shipping_mode',
            self::SELECT,
            [
                'name' => 'shipping[local_shipping_mode]',
                'label' => $this->__('Type'),
                'title' => $this->__('Type'),
                'values' => $this->getDomesticShippingOptions(),
                'value' => $this->formData['local_shipping_mode'],
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'shipping_irregular',
            self::SELECT,
            [
                'name' => 'shipping[shipping_irregular]',
                'label' => __('Irregular Package'),
                'title' => __('Irregular Package'),
                'values' => [
                    ['value' => 0, 'label' => __('No')],
                    ['value' => 1, 'label' => __('Yes')],
                ],
                'value' => $this->formData['shipping_irregular'],
                'tooltip' => __(
                    'Specify whether a package is irregular and cannot go through the stamping machine at the'
                    . ' shipping service office and requires special or fragile handling.'
                    . ' For calculated shipping only.'
                ),
            ]
        );

        // ---------------------------------------

        if ($this->canDisplayLocalShippingRateTable()) {
            $shippingRateTableModeToolTipHtmlAccept = $this->__(
                <<<HTML
Choose whether you want to apply
<a href="http://pages.ebay.com/help/pay/shipping-costs.html#tables" target="_blank">eBay Shipping Rate Tables</a> to
M2E Pro Items.
HTML
            );
            // @codingStandardsIgnoreStart
            $shippingRateTableModeToolTipHtmlIdentifier = $this->__(
                <<<HTML
Select which Shipping Rate Table mode to use:<br>
<strong>Yes/No</strong> - allows you to apply or disable a
<a target="_blank"
    href="http://pages.ebay.com/help/pay/shipping-costs.html#tables">default</a> Shipping Rate Table;<br>
<strong>Rate Table</strong> -  allows you to apply a
<a target="_blank"
    href="http://pages.ebay.com/seller-center/seller-updates/2017spring/shipping-tools.html">certain</a>
Shipping Rate Table from the list you have created to the current eBay Seller Account.<br><br>
Click <strong>Refresh</strong> to download the latest Shipping Rate Tables from eBay.
HTML
            );
            // @codingStandardsIgnoreEnd

            if ($this->getAccountId() !== null) {
                $fieldSet->addField(
                    'local_shipping_rate_table_mode_' . $this->getAccountId(),
                    'hidden',
                    [
                        'name' => 'shipping[local_shipping_rate_table][' . $this->getAccountId() . '][mode]',
                        'value' => $this->formData['local_shipping_rate_table'][$this->getAccountId()]['mode'],
                    ]
                );

                $shippingRateTableModeToolTipStyleAccept = '';
                if (
                    $this->formData['local_shipping_rate_table'][$this->getAccountId()]['mode'] !=
                    \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE
                ) {
                    $shippingRateTableModeToolTipStyleAccept = "display: none;";
                }

                $shippingRateTableModeToolTipStyleIdentifier = '';
                if (
                    $this->formData['local_shipping_rate_table'][$this->getAccountId()]['mode'] !=
                    \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE
                ) {
                    $shippingRateTableModeToolTipStyleIdentifier = "display: none;";
                }

                $rateTableValue = $this->formData['local_shipping_rate_table'][$this->getAccountId()]['value'];
                $isSellApiEnabled = (bool)$this->getAccount()->getChildObject()->getSellApiTokenSession();
                $rateTableValueJson = \Ess\M2ePro\Helper\Json::encode($rateTableValue);
                $fieldSet->addField(
                    'local_shipping_rate_table_value_' . $this->getAccountId(),
                    self::SELECT,
                    [
                        'name' => 'shipping[local_shipping_rate_table][' . $this->getAccountId() . '][value]',
                        'label' => $this->__('Use eBay Shipping Rate Table'),
                        'title' => $this->__('Use eBay Shipping Rate Table'),
                        'class' => 'M2ePro-validate-rate-table',
                        'field_extra_attributes' => 'id="local_shipping_rate_table_mode_tr"',
                        'style' => 'margin-right: 18px',
                        'tooltip' => $this->__(
                            <<<HTML
    <span class="shipping_rate_table_note_accepted" style="{$shippingRateTableModeToolTipStyleAccept}">
        {$shippingRateTableModeToolTipHtmlAccept}
    </span>
    <span class="shipping_rate_table_note_identifier" style="{$shippingRateTableModeToolTipStyleIdentifier}">
         {$shippingRateTableModeToolTipHtmlIdentifier}
    </span>
HTML
                        ),
                    ]
                )->addCustomAttribute(
                    'data-current-mode',
                    $this->formData['local_shipping_rate_table'][$this->getAccountId()]['mode']
                )->setAfterElementHtml(
                    <<<HTML
    <a href="javascript:void(0);" class="update_rate_table_button"
       onclick='EbayTemplateShippingObj.updateRateTablesData({
        accountId: {$this->getAccountId()},
        marketplaceId: {$this->getMarketplace()->getId()},
        elementId: "local_shipping_rate_table_value_{$this->getAccountId()}",
        value: {$rateTableValueJson},
        type: "local"
    })'>{$this->__($isSellApiEnabled ? 'Refresh Rate Tables' : 'Download Rate Tables')}</a>
HTML
                );
            } else {
                $shippingRateTableModeAccountsHtml = '';
                if ($this->getAccounts()->getSize()) {
                    foreach ($this->getAccounts() as $account) {
                        $shippingRateTableModeToolTipStyleAccept = '';
                        if (
                            $this->formData['local_shipping_rate_table'][$account->getId()]['mode'] !=
                            \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE
                        ) {
                            $shippingRateTableModeToolTipStyleAccept = "display: none;";
                        }

                        $shippingRateTableModeToolTipStyleIdentifier = '';
                        if (
                            $this->formData['local_shipping_rate_table'][$account->getId()]['mode'] !=
                            \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE
                        ) {
                            $shippingRateTableModeToolTipStyleIdentifier = "display: none;";
                        }

                        $rateTableValue = $this->formData['local_shipping_rate_table'][$account->getId()]['value'];
                        $rateTableValueJson = \Ess\M2ePro\Helper\Json::encode($rateTableValue);
                        $isSellApiEnabled = (bool)$account->getChildObject()->getSellApiTokenSession();
                        $toolTip = $this->getTooltipHtml(
                            <<<HTML
    <span class="shipping_rate_table_note_accepted" style="{$shippingRateTableModeToolTipStyleAccept}">
         {$shippingRateTableModeToolTipHtmlAccept}
    </span>
    <span class="shipping_rate_table_note_identifier" style="{$shippingRateTableModeToolTipStyleIdentifier}">
         {$shippingRateTableModeToolTipHtmlIdentifier}
    </span>
HTML
                        );

                        $shippingRateTableModeAccountsHtml .= <<<HTML
    <tr class="local-shipping-rate-table-account-tr">
        <td class="label">
            <label for="local_shipping_rate_table_value_{$account->getId()}">{$account->getTitle()}</label>
        </td>

        <td class="value" style="border-right: none;">
            <input type="hidden" id="local_shipping_rate_table_mode_{$account->getId()}"
                name="shipping[local_shipping_rate_table][{$account->getId()}][mode]"
                value="{$this->formData['local_shipping_rate_table'][$account->getId()]['mode']}">
            <select name="shipping[local_shipping_rate_table][{$account->getId()}][value]"
                id="local_shipping_rate_table_value_{$account->getId()}"
                data-current-mode="{$this->formData['local_shipping_rate_table'][$account->getId()]['mode']}"
                style="min-width: 250px;"
                class="m2epro-field-with-tooltip select admin__control-select M2ePro-validate-rate-table"></select>
            {$toolTip}
        </td>
        <td class="value v-middle" style="border-left: none;">
            <a href="javascript:void(0);" class="update_rate_table_button"
               onclick='EbayTemplateShippingObj.updateRateTablesData({
                accountId: {$account->getId()},
                marketplaceId: {$this->getMarketplace()->getId()},
                elementId: "local_shipping_rate_table_value_{$account->getId()}",
                value: {$rateTableValueJson},
                type: "local"
            })'>{$this->__($isSellApiEnabled ? 'Refresh Rate Tables' : 'Download Rate Tables')}</a>
        </td>
    </tr>
HTML;
                    }
                } else {
                    $shippingRateTableModeAccountsHtml .= <<<HTML
    <tr>
        <td colspan="4" style="text-align: center">
            {$this->__('You do not have eBay Accounts added to M2E Pro.')}
        </td>
    </tr>
HTML;
                }

                $shippingRateTableModeHtml = <<<HTML
    <table class="border data-grid data-grid-not-hovered shipping_rate_table" cellpadding="0" cellspacing="0">
        <thead>
            <tr class="headings">
                <th class="data-grid-th v-middle" style="width: 30%;">{$this->__('Account')}</th>
                <th class="data-grid-th v-middle" colspan="3">{$this->__('eBay Shipping Rate Table')}</th>
            </tr>
        </thead>
        {$shippingRateTableModeAccountsHtml}
    </table>

HTML;

                $fieldSet->addField(
                    'local_shipping_rate_table_mode_tr_wrapper',
                    self::CUSTOM_CONTAINER,
                    [
                        'text' => $shippingRateTableModeHtml,
                        'css_class' => 'm2epro-fieldset-table',
                        'field_extra_attributes' => 'id="local_shipping_rate_table_mode_tr"',
                    ]
                );
            }
        }

        // ---------------------------------------

        $fieldSet->addField(
            'shipping_local_table_messages',
            self::CUSTOM_CONTAINER,
            [
                'text' => '',
                'css_class' => 'm2epro-fieldset-table no-margin-bottom',
            ]
        );

        $fieldSet->addField(
            'local_shipping_methods_tr_wrapper',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getShippingLocalTable(),
                'css_class' => 'm2epro-fieldset-table',
                'field_extra_attributes' => 'id="local_shipping_methods_tr"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'dispatch_time_value',
            'hidden',
            [
                'name' => 'shipping[dispatch_time_value]',
                'value' => $this->formData['dispatch_time_value'],
            ]
        );

        $fieldSet->addField(
            'dispatch_time_attribute',
            'hidden',
            [
                'name' => 'shipping[dispatch_time_attribute]',
                'value' => $this->formData['dispatch_time_attribute'],
            ]
        );

        $dispatchModeOptions = $this->getDispatchTimeOptions();
        $dispatchModeOptions[] = $this->getAttributesOptions(
            Shipping::DISPATCH_TIME_MODE_ATTRIBUTE,
            function ($attribute) {
                return $this->formData['dispatch_time_mode'] == Shipping::DISPATCH_TIME_MODE_ATTRIBUTE
                    && $attribute['code'] == $this->formData['dispatch_time_attribute'];
            }
        );

        $fieldSet->addField(
            'dispatch_time_mode',
            self::SELECT,
            [
                'name' => 'shipping[dispatch_time_mode]',
                'label' => $this->__('Dispatch Time'),
                'title' => $this->__('Dispatch Time'),
                'values' => $dispatchModeOptions,
                'class' => 'M2ePro-required-when-visible M2ePro-custom-attribute-can-be-created',
                'css_class' => 'local-shipping-tr local-shipping-always-visible-tr',
                'tooltip' => $this->__(
                    'The dispatch (or handling) time is the number of working days during which seller will take the
                    item to carrier after buyer\'s payment is credited to seller\'s account.'
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldSet->addField(
            'local_handling_cost',
            'text',
            [
                'name' => 'shipping[local_handling_cost]',
                'label' => $this->__('Handling Cost'),
                'title' => $this->__('Handling Cost'),
                'value' => $this->formData['local_handling_cost'],
                'class' => 'input-text M2ePro-validation-float',
                'css_class' => 'local-shipping-tr',
                'field_extra_attributes' => 'id="local_handling_cost_cv_tr"',
                'tooltip' => $this->__('Addition of handling cost to the shipping costs.'),
            ]
        );

        // ---------------------------------------

        if ($this->getAccountId() !== null) {
            $fieldsetCombined = $fieldSet->addFieldset(
                'combined_shipping_profile',
                [
                    'legend' => __('Combined Shipping Profile'),
                    'collapsable' => false,
                    'class' => 'local-shipping-tr',
                ]
            );

            $fieldsetCombined->addField(
                'local_shipping_discount_combined_profile_id_' . $this->getAccountId(),
                self::SELECT,
                [
                    'name' => 'shipping[local_shipping_discount_combined_profile_id][' . $this->getAccountId() . ']',
                    'label' => $this->__('Combined Shipping Profile'),
                    'title' => $this->__('Combined Shipping Profile'),
                    'values' => [
                        ['label' => $this->__('None'), 'value' => ''],
                    ],
                    'class' => 'local-discount-profile-account-tr',
                    'value' => '',
                    'style' => 'margin-right: 18px',
                    'tooltip' => $this->__(
                        'If you have Flat Shipping Rules or Calculated Shipping Rules set up in eBay,
                        you can choose to use them here.<br/><br/>
                        Click <b>Refresh Profiles</b> to get your latest shipping profiles from eBay.'
                    ),
                ]
            )->addCustomAttribute('account_id', $this->getAccountId())
                             ->setData(
                                 'after_element_html',
                                 "<a href=\"javascript:void(0);\"
                    onclick=\"EbayTemplateShippingObj.updateDiscountProfiles(" . $this->getAccountId() . ");\">"
                                 . $this->__('Refresh Profiles')
                                 . "</a>"
                             );
        } else {
            $fieldSet->addField(
                'account_combined_shipping_profile_local',
                self::CUSTOM_CONTAINER,
                [
                    'text' => $this->getAccountCombinedShippingProfile('local'),
                    'css_class' => 'local-shipping-tr',
                ]
            );
        }

        // ---------------------------------------

        $fieldSet->addField(
            'local_shipping_discount_promotional_mode',
            self::SELECT,
            [
                'name' => 'shipping[local_shipping_discount_promotional_mode]',
                'label' => $this->__('Promotional Shipping Rule'),
                'title' => $this->__('Promotional Shipping Rule'),
                'values' => [
                    ['value' => 0, 'label' => __('No')],
                    ['value' => 1, 'label' => __('Yes')],
                ],
                'value' => $this->formData['local_shipping_discount_promotional_mode'],
                'css_class' => 'local-shipping-tr',
                'tooltip' => $this->__(
                    'Offers the Shipping Discounts according to the \'Promotional shipping Rule
                    (applies to all Listings)\' Settings in your eBay Account.
                    Shipping Discounts are set up directly on eBay, not in M2E Pro.
                    To set up or edit Shipping Discounts, Log in to your Seller Account on eBay.'
                ),
            ]
        );

        // ---------------------------------------

        $fieldSet = $form->addFieldset(
            'magento_block_ebay_template_shipping_form_data_international',
            [
                'legend' => __('International Shipping'),
                'collapsable' => true,
            ]
        );

        if (
            $this->canDisplayNorthAmericaCrossBorderTradeOption()
            || $this->canDisplayUnitedKingdomCrossBorderTradeOption()
        ) {
            $fieldSet->addField(
                'cross_border_trade',
                self::SELECT,
                [
                    'name' => 'shipping[cross_border_trade]',
                    'label' => $this->__('Cross Border Trade'),
                    'title' => $this->__('Cross Border Trade'),
                    'values' => $this->getSiteVisibilityOptions(),
                    'value' => $this->formData['cross_border_trade'],
                    'field_extra_attributes' => 'id="cross_border_trade_container"',
                    'tooltip' => $this->__(
                        'The international Site visibility feature allows qualifying Listings to be posted on
                        international Marketplaces.
                        <br/>Buyers on these Marketplaces will see the Listings exactly as you originally post them.
                        <br/><br/><b>Note:</b> There may be additional eBay charges for this option.'
                    ),
                ]
            );
        }

        if ($this->canDisplayGlobalShippingProgram()) {
            $fieldSet->addField(
                'global_shipping_program',
                self::SELECT,
                [
                    'name' => 'shipping[global_shipping_program]',
                    'label' => $this->__('Offer Global Shipping Program'),
                    'title' => $this->__('Offer Global Shipping Program'),
                    'values' => [
                        ['value' => 0, 'label' => __('No')],
                        ['value' => 1, 'label' => __('Yes')],
                    ],
                    'value' => $this->formData['global_shipping_program'],
                    'tooltip' => $this->__(
                        'Simplifies selling an Item to an international Buyer. Click
                        <a href="http://pages.ebay.com/help/sell/shipping-globally.html"
                           target="_blank" class="external-link">here</a> to find out more.
                        <br/><br/><b>Note:</b> This option is available for eBay Motors only
                        under "Parts & Accessories" Category.'
                    ),
                ]
            );
        }

        // ---------------------------------------

        $fieldSet->addField(
            'international_shipping_mode',
            self::SELECT,
            [
                'name' => 'shipping[international_shipping_mode]',
                'label' => $this->__('Type'),
                'title' => $this->__('Type'),
                'values' => $this->getInternationalShippingOptions(),
                'value' => $this->formData['international_shipping_mode'],
            ]
        );

        // ---------------------------------------

        if ($this->canDisplayInternationalShippingRateTable()) {
            $shippingRateTableModeToolTipHtmlAccept = $this->__(
                <<<HTML
Choose whether you want to apply
<a href="http://pages.ebay.com/help/pay/shipping-costs.html#tables" target="_blank">eBay Shipping Rate Tables</a> to
M2E Pro Items.
HTML
            );
            // @codingStandardsIgnoreStart
            $shippingRateTableModeToolTipHtmlIdentifier = $this->__(
                <<<HTML
Select which Shipping Rate Table mode to use:<br>
<strong>Yes/No</strong> - allows you to apply or disable a
<a target="_blank"
    href="http://pages.ebay.com/help/pay/shipping-costs.html#tables">default</a> Shipping Rate Table;<br>
<strong>Rate Table</strong> -  allows you to apply a
<a target="_blank"
    href="http://pages.ebay.com/seller-center/seller-updates/2017spring/shipping-tools.html">certain</a>
Shipping Rate Table from the list you have created to the current eBay Seller Account.<br><br>
Click <strong>Refresh</strong> to download the latest Shipping Rate Tables from eBay.
HTML
            );
            // @codingStandardsIgnoreEnd

            if ($this->getAccountId() !== null) {
                $fieldSet->addField(
                    'international_shipping_rate_table_mode_' . $this->getAccountId(),
                    'hidden',
                    [
                        'name' => 'shipping[international_shipping_rate_table][' . $this->getAccountId() . '][mode]',
                        'value' => $this->formData['international_shipping_rate_table'][$this->getAccountId()]['mode'],
                    ]
                );

                $shippingRateTableModeToolTipStyleAccept = '';
                if (
                    $this->formData['international_shipping_rate_table'][$this->getAccountId()]['mode'] !=
                    \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE
                ) {
                    $shippingRateTableModeToolTipStyleAccept = "display: none;";
                }

                $shippingRateTableModeToolTipStyleIdentifier = '';
                if (
                    $this->formData['international_shipping_rate_table'][$this->getAccountId()]['mode'] !=
                    \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE
                ) {
                    $shippingRateTableModeToolTipStyleIdentifier = "display: none;";
                }

                $rateTableValue = $this->formData['international_shipping_rate_table'][$this->getAccountId()]['value'];
                $isSellApiEnabled = (bool)$this->getAccount()->getChildObject()->getSellApiTokenSession();
                $rateTableValueJson = \Ess\M2ePro\Helper\Json::encode($rateTableValue);

                $fieldSet->addField(
                    'international_shipping_rate_table_value_' . $this->getAccountId(),
                    self::SELECT,
                    [
                        'name' => 'shipping[international_shipping_rate_table][' . $this->getAccountId() . '][value]',
                        'label' => $this->__('Use eBay Shipping Rate Table'),
                        'title' => $this->__('Use eBay Shipping Rate Table'),
                        'css_class' => 'international-shipping-tr M2ePro-validate-rate-table',
                        'field_extra_attributes' => 'id="international_shipping_rate_table_mode_tr"',
                        'style' => 'margin-right: 18px',
                        'tooltip' => $this->__(
                            <<<HTML
    <span class="shipping_rate_table_note_accepted" style="{$shippingRateTableModeToolTipStyleAccept}">
        {$shippingRateTableModeToolTipHtmlAccept}
    </span>
    <span class="shipping_rate_table_note_identifier" style="{$shippingRateTableModeToolTipStyleIdentifier}">
         {$shippingRateTableModeToolTipHtmlIdentifier}
    </span>
HTML
                        ),
                    ]
                )->addCustomAttribute(
                    'data-current-mode',
                    $this->formData['international_shipping_rate_table'][$this->getAccountId()]['mode']
                )->setAfterElementHtml(
                    <<<HTML
    <a href="javascript:void(0);" class="update_rate_table_button"
       onclick='EbayTemplateShippingObj.updateRateTablesData({
        accountId: {$this->getAccountId()},
        marketplaceId: {$this->getMarketplace()->getId()},
        elementId: "international_shipping_rate_table_value_{$this->getAccountId()}",
        value: {$rateTableValueJson},
        type: "international"
    })'>{$this->__($isSellApiEnabled ? 'Refresh Rate Tables' : 'Download Rate Tables')}</a>
HTML
                );
            } else {
                $shippingRateTableModeAccountsHtml = '';
                if ($this->getAccounts()->getSize()) {
                    foreach ($this->getAccounts() as $account) {
                        $shippingRateTableModeToolTipStyleAccept = '';
                        if (
                            $this->formData['international_shipping_rate_table'][$account->getId()]['mode'] !=
                            \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE
                        ) {
                            $shippingRateTableModeToolTipStyleAccept = "display: none;";
                        }

                        $shippingRateTableModeToolTipStyleIdentifier = '';
                        if (
                            $this->formData['international_shipping_rate_table'][$account->getId()]['mode'] !=
                            \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_RATE_TABLE_IDENTIFIER_MODE
                        ) {
                            $shippingRateTableModeToolTipStyleIdentifier = "display: none;";
                        }

                        $rateTableValue =
                            $this->formData['international_shipping_rate_table'][$account->getId()]['value'];
                        $rateTableValueJson = \Ess\M2ePro\Helper\Json::encode($rateTableValue);
                        $isSellApiEnabled = (bool)$account->getChildObject()->getSellApiTokenSession();

                        $toolTip = $this->getTooltipHtml(
                            <<<HTML
    <span class="shipping_rate_table_note_accepted" style="{$shippingRateTableModeToolTipStyleAccept}">
         {$shippingRateTableModeToolTipHtmlAccept}
    </span>
    <span class="shipping_rate_table_note_identifier" style="{$shippingRateTableModeToolTipStyleIdentifier}">
         {$shippingRateTableModeToolTipHtmlIdentifier}
    </span>
HTML
                        );

                        $shippingRateTableModeAccountsHtml .= <<<HTML
    <tr class="international-shipping-rate-table-account-tr">
        <td class="label">
            <label for="international_shipping_rate_table_value_{$account->getId()}">{$account->getTitle()}</label>
        </td>

        <td class="value" style="border-right: none;">
            <input type="hidden" id="international_shipping_rate_table_mode_{$account->getId()}"
                name="shipping[international_shipping_rate_table][{$account->getId()}][mode]"
                value="{$this->formData['international_shipping_rate_table'][$account->getId()]['mode']}">
            <select name="shipping[international_shipping_rate_table][{$account->getId()}][value]"
                id="international_shipping_rate_table_value_{$account->getId()}"
                data-current-mode="{$this->formData['international_shipping_rate_table'][$account->getId()]['mode']}"
                style="min-width: 250px;"
                class="m2epro-field-with-tooltip select admin__control-select M2ePro-validate-rate-table"></select>
            {$toolTip}
        </td>
        <td class="value v-middle" style="border-left: none;">
            <a href="javascript:void(0);" class="update_rate_table_button"
               onclick='EbayTemplateShippingObj.updateRateTablesData({
                accountId: {$account->getId()},
                marketplaceId: {$this->getMarketplace()->getId()},
                elementId: "international_shipping_rate_table_value_{$account->getId()}",
                value: {$rateTableValueJson},
                type: "international"
            })'>{$this->__($isSellApiEnabled ? 'Refresh Rate Tables' : 'Download Rate Tables')}</a>
        </td>
    </tr>
HTML;
                    }
                } else {
                    $shippingRateTableModeAccountsHtml .= <<<HTML
    <tr>
        <td colspan="4" style="text-align: center">
            {$this->__('You do not have eBay Accounts added to M2E Pro.')}
        </td>
    </tr>
HTML;
                }

                $shippingRateTableModeHtml = <<<HTML
    <table class="border data-grid data-grid-not-hovered shipping_rate_table international-shipping-tr"
           cellpadding="0" cellspacing="0">
        <thead>
            <tr class="headings">
                <th class="data-grid-th" style="width: 30%;">{$this->__('Account')}</th>
                <th class="data-grid-th" colspan="3">{$this->__('eBay Shipping Rate Table')}</th>
            </tr>
        </thead>
        {$shippingRateTableModeAccountsHtml}
    </table>

HTML;

                $fieldSet->addField(
                    'international_shipping_rate_table_mode_tr_wrapper',
                    self::CUSTOM_CONTAINER,
                    [
                        'text' => $shippingRateTableModeHtml,
                        'css_class' => 'm2epro-fieldset-table',
                        'field_extra_attributes' => 'id="international_shipping_rate_table_mode_tr"',
                    ]
                );
            }
        }

        // ---------------------------------------

        $fieldSet->addField(
            'shipping_international_table_messages',
            self::CUSTOM_CONTAINER,
            [
                'text' => '',
                'css_class' => 'm2epro-fieldset-table no-margin-bottom',
            ]
        );

        $fieldSet->addField(
            'international_shipping_methods_tr_wrapper',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getShippingInternationalTable(),
                'css_class' => 'm2epro-fieldset-table',
                'container_class' => 'international-shipping-tr international-shipping-always-visible-tr',
                'field_extra_attributes' => 'id="international_shipping_methods_tr"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'international_handling_cost',
            'text',
            [
                'name' => 'shipping[international_handling_cost]',
                'label' => $this->__('Handling Cost'),
                'title' => $this->__('Handling Cost'),
                'value' => $this->formData['international_handling_cost'],
                'css_class' => 'international-shipping-tr',
                'field_extra_attributes' => 'id="international_handling_cost_cv_tr"',
                'tooltip' => $this->__('Addition of handling cost to the shipping costs.'),
            ]
        );

        // ---------------------------------------

        if ($this->getAccountId() !== null) {
            $fieldsetCombined = $fieldSet->addFieldset(
                'international_shipping_profile',
                [
                    'legend' => __('Combined Shipping Profile'),
                    'collapsable' => false,
                    'class' => 'international-shipping-tr',
                ]
            );

            $fieldsetCombined->addField(
                'international_shipping_discount_combined_profile_id_' . $this->getAccountId(),
                self::SELECT,
                [
                    'name' =>
                        'shipping[international_shipping_discount_combined_profile_id][' . $this->getAccountId() . ']',
                    'label' => $this->__('Combined Shipping Profile'),
                    'title' => $this->__('Combined Shipping Profile'),
                    'class' => 'international-discount-profile-account-tr',
                    'values' => [
                        ['label' => $this->__('None'), 'value' => ''],
                    ],
                    'value' => '',
                    'style' => 'margin-right: 18px',
                    'tooltip' => $this->__(
                        'Use the Flat Shipping Rule and Calculated Shipping Rule Profiles, which were created on eBay.
                        <br/><br/><b>Note:</b> Press "Refresh Profiles" Button for upload new or refreshes
                        eBay Shipping Profiles.'
                    ),
                    'field_extra_attributes' => 'account_id="' . $this->getAccountId() . '"',
                ]
            )->addCustomAttribute('account_id', $this->getAccountId())
                             ->setData(
                                 'after_element_html',
                                 "<a href=\"javascript:void(0);\"
                    onclick=\"EbayTemplateShippingObj.updateDiscountProfiles(" . $this->getAccountId() . ");\">"
                                 . $this->__('Refresh Profiles')
                                 . "</a>"
                             );
        } else {
            $fieldSet->addField(
                'account_international_shipping_profile_international',
                self::CUSTOM_CONTAINER,
                [
                    'text' => $this->getAccountCombinedShippingProfile('international'),
                    'css_class' => 'international-shipping-tr',
                ]
            );
        }

        // ---------------------------------------

        $fieldSet->addField(
            'international_shipping_discount_promotional_mode',
            self::SELECT,
            [
                'name' => 'shipping[international_shipping_discount_promotional_mode]',
                'label' => $this->__('Promotional Shipping Rule'),
                'title' => $this->__('Promotional Shipping Rule'),
                'values' => [
                    ['value' => 0, 'label' => __('No')],
                    ['value' => 1, 'label' => __('Yes')],
                ],
                'value' => $this->formData['international_shipping_discount_promotional_mode'],
                'class' => 'M2ePro-required-when-visible',
                'css_class' => 'international-shipping-tr',
                'tooltip' => $this->__('Add Shipping Discounts according to Rules that are set in your eBay Account.'),
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // Package details
        // ---------------------------------------

        $fieldSet = $form->addFieldset(
            'magento_block_ebay_template_shipping_form_data_calculated',
            ['legend' => __('Package details'), 'collapsable' => true]
        );

        $fieldSet->addField(
            'measurement_system',
            self::SELECT,
            [
                'name' => 'shipping[measurement_system]',
                'label' => $this->__('Measurement System'),
                'title' => $this->__('Measurement System'),
                'values' => $this->getMeasurementSystemOptions(),
                'value' => $this->formData['measurement_system'],
                'class' => 'select',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'package_size_mode',
            'hidden',
            [
                'name' => 'shipping[package_size_mode]',
                'value' => $this->formData['package_size_mode'],
            ]
        );

        $fieldSet->addField(
            'package_size_value',
            'hidden',
            [
                'name' => 'shipping[package_size_value]',
                'value' => $this->formData['package_size_value'],
            ]
        );

        $fieldSet->addField(
            'package_size_attribute',
            'hidden',
            [
                'name' => 'shipping[package_size_attribute]',
                'value' => $this->formData['package_size_attribute'],
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'package_size',
            self::SELECT,
            [
                'label' => $this->__('Package Size Source'),
                'title' => $this->__('Package Size Source'),
                'values' => $this->getPackageSizeSourceOptions(),
                'field_extra_attributes' => 'id="package_size_tr"',
                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldSet->addField(
            'dimension_mode',
            self::SELECT,
            [
                'name' => 'shipping[dimension_mode]',
                'label' => $this->__('Dimension Source'),
                'title' => $this->__('Dimension Source'),
                'values' => [
                    [
                        'value' => Shipping\Calculated::DIMENSION_NONE,
                        'label' => $this->__('None'),
                        'attrs' => ['id' => 'dimension_mode_none'],
                    ],
                    [
                        'value' => Shipping\Calculated::DIMENSION_CUSTOM_VALUE,
                        'label' => $this->__('Custom Value'),
                    ],
                    [
                        'value' => Shipping\Calculated::DIMENSION_CUSTOM_ATTRIBUTE,
                        'label' => $this->__('Custom Attribute'),
                        'attrs' => ['id' => 'dimension_mode_none'],
                    ],
                ],
                'value' => $this->formData['dimension_mode'],
                'class' => 'select',
                'field_extra_attributes' => 'id="dimensions_tr"',
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // Dimensions
        // ---------------------------------------

        $heightAttrBlock = $this->elementFactory->create(
            self::SELECT,
            [
                'data' => [
                    'html_id' => 'shipping_dimension_length_attribute',
                    'name' => 'shipping[dimension_length_attribute]',
                    'values' => $this->getDimensionsOptions('dimension_length_attribute'),
                    'value' => $this->formData['dimension_length_attribute'],
                    'class' => 'M2ePro-required-when-visible dimension-custom-input',
                    'create_magento_attribute' => true,
                ],
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');
        $heightAttrBlock->setForm($form);

        $depthAttrBlock = $this->elementFactory->create(
            self::SELECT,
            [
                'data' => [
                    'html_id' => 'shipping_dimension_depth_attribute',
                    'name' => 'shipping[dimension_depth_attribute]',
                    'values' => $this->getDimensionsOptions('dimension_depth_attribute'),
                    'value' => $this->formData['dimension_depth_attribute'],
                    'class' => 'M2ePro-required-when-visible dimension-custom-input',
                    'create_magento_attribute' => true,
                ],
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');
        $depthAttrBlock->setForm($form);

        $fieldSet->addField(
            'dimension',
            self::SELECT,
            [
                'css_class' => 'dimensions_ca_tr',
                'name' => 'shipping[dimension_width_attribute]',
                'label' => $this->__('Dimensions (Width×Height×Depth)'),
                'values' => $this->getDimensionsOptions('dimension_width_attribute'),
                'value' => $this->formData['dimension_width_attribute'],
                'class' => 'M2ePro-required-when-visible dimension-custom-input',
                'required' => true,
                'note' => ' ',
                'create_magento_attribute' => true,
                'after_element_html' => ' <span style="color: #303030">&times;</span> '
                    . $heightAttrBlock->toHtml()
                    . ' <span style="color: #303030">&times;</span> '
                    . $depthAttrBlock->toHtml(),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $heightValBlock = $this->elementFactory->create(
            'text',
            [
                'data' => [
                    'name' => 'shipping[dimension_length_value]',
                    'value' => $this->formData['dimension_length_value'],
                    'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float dimension-custom-input',
                    'style' => 'width: 125px;',
                ],
            ]
        );
        $heightValBlock->setForm($form);

        $depthValBlock = $this->elementFactory->create(
            'text',
            [
                'data' => [
                    'name' => 'shipping[dimension_depth_value]',
                    'value' => $this->formData['dimension_depth_value'],
                    'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float dimension-custom-input',
                ],
            ]
        );
        $depthValBlock->setForm($form);

        $fieldSet->addField(
            'dimension_width_attribute_text',
            'text',
            [
                'css_class' => 'dimensions_cv_tr',
                'name' => 'shipping[dimension_width_value]',
                'label' => $this->__('Dimensions (Width×Height×Depth)'),
                'value' => $this->formData['dimension_width_value'],
                'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float dimension-custom-input',
                'required' => true,
                'note' => ' ',
                'after_element_html' => ' <span style="color: #303030">&times;</span> '
                    . $heightValBlock->toHtml()
                    . ' <span style="color: #303030">&times;</span> '
                    . $depthValBlock->toHtml(),
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'weight_mode',
            'hidden',
            [
                'name' => 'shipping[weight_mode]',
                'value' => $this->formData['weight_mode'],
            ]
        );

        $fieldSet->addField(
            'weight_attribute',
            'hidden',
            [
                'name' => 'shipping[weight_attribute]',
                'value' => $this->formData['weight_attribute'],
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'weight',
            self::SELECT,
            [
                'name' => 'shipping[test]',
                'label' => $this->__('Weight Source'),
                'title' => $this->__('Weight Source'),
                'values' => $this->getWeightSourceOptions(),
                'value' => $this->formData['weight_mode'] != Shipping\Calculated::WEIGHT_CUSTOM_ATTRIBUTE
                    ? $this->formData['weight_mode'] : '',
                'class' => 'select',
                'field_extra_attributes' => 'id="weight_tr"',
                'note' => ' ',
                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $weightMinorBlock = $this->elementFactory->create(
            'text',
            [
                'data' => [
                    'name' => 'shipping[weight_minor]',
                    'value' => $this->formData['weight_minor'],
                    'class' => 'M2ePro-required-when-visible M2ePro-validation-float input-text admin__control-text
                            shipping_weight_minor',
                ],
            ]
        );
        $weightMinorBlock->setForm($form);

        $fieldSet->addField(
            'weight_mode_container',
            'text',
            [
                'container_id' => 'weight_cv',
                'label' => $this->__('Weight'),
                'name' => 'shipping[weight_major]',
                'value' => $this->formData['weight_major'],
                'class' => 'M2ePro-required-when-visible M2ePro-validation-float input-text',
                'style' => 'width: 30%',
                'required' => true,
                'note' => ' ',
                'after_element_html' => '<span style="color: black;"> &times; </span>' . $weightMinorBlock->toHtml(),
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // Excluded Locations
        // ---------------------------------------

        $fieldSet = $form->addFieldset(
            'magento_block_ebay_template_shipping_form_data_excluded_locations',
            [
                'legend' => $this->__('Excluded Locations'),
                'collapsable' => true,
                'tooltip' => $this->__(
                    'To exclude Buyers in certain Locations from purchasing your Item,
                    create a Shipping Exclusion List.'
                ),
            ]
        );

        $fieldSet->addField(
            'excluded_locations_hidden',
            'hidden',
            [
                'name' => 'shipping[excluded_locations]',
                'value' => '',
            ]
        );

        $fieldSet->addField(
            'excluded_locations',
            self::CUSTOM_CONTAINER,
            [
                'label' => __('Locations'),
                'text' => '<p><span id="excluded_locations_titles"></span></p>
                <a href="javascript:void(0)" onclick="EbayTemplateShippingExcludedLocationsObj.showPopup();">'
                    . $this->__('Edit Exclusion List') .
                    '</a>',
            ]
        );

        // ---------------------------------------

        $this->setForm($form);

        return $this;
    }

    // ---------------------------------------

    public function getShippingLocalTable()
    {
        $localShippingMethodButton = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                                          ->setData(
                                              [
                                                  'onclick' => 'EbayTemplateShippingObj.addRow(\'local\');',
                                                  'class' => 'add add_local_shipping_method_button primary',
                                              ]
                                          );

        return <<<HTML

                <table id="shipping_local_table"
                       class="border data-grid data-grid-not-hovered"
                       cellpadding="0"
                       cellspacing="0">
                    <thead>
                        <tr class="headings">
                            <th class="data-grid-th" style="width: 35%;">{$this->__('Service')}
                                <span class="required">*</span>
                            </th>
                            <th class="data-grid-th" style="width: 14%;">{$this->__('Mode')}</th>
                            <th class="data-grid-th" style="width: 14%;">{$this->__('Cost')}
                                <span class="required">*</span>
                            </th>
                            <th class="data-grid-th" style="width: 14%;">{$this->__('Additional Cost')}
                            </th>
                            <th class="data-grid-th" style="width: 7%;">{$this->__('Currency')}</th>
                            <th class="data-grid-th" style="width: 7%; min-width: 80px;">{$this->__('Priority')}</th>
                            <th class="type-butt last data-grid-th" style="width: 10%; min-width: 80px;">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody id="shipping_local_tbody">
                        <!-- #shipping_table_row_template inserts here -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="20" class="a-right">
                                {$localShippingMethodButton->setData('label', $this->__('Add Method'))->toHtml()}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <div id="add_local_shipping_method_button" style="display: none;">

                    <table style="border: none" cellpadding="0" cellspacing="0">
                        <tfoot>
                            <tr>
                                <td valign="middle" id="add_local_shipping_method" align="center" style="vertical-align: middle; height: 40px">
                                    {$localShippingMethodButton->setData(
            'label',
            $this->__('Add Shipping Method')
        )->toHtml()}
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                </div>

                <input type="text"
                       name="local_shipping_methods_validator"
                       id="local_shipping_methods_validator"
                       class="M2ePro-validate-shipping-methods"
                       style="visibility: hidden; width: 100%; margin-top: -25px; display: block;" />
HTML;
    }

    public function getShippingInternationalTable()
    {
        $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                            ->setData(
                                [
                                    'onclick' => 'EbayTemplateShippingObj.addRow(\'international\');',
                                    'class' => 'add add_international_shipping_method_button primary',
                                ]
                            );

        return <<<HTML
        <table id="shipping_international_table"
               class="border data-grid data-grid-not-hovered"
               cellpadding="0"
               cellspacing="0"
               style="display: none">
            <thead>
                <tr class="headings">
                    <th class="data-grid-th" style="width: 35%;">{$this->__('Service')}
                        <span class="required">*</span>
                    </th>
                    <th class="data-grid-th" style="width: 14%;">{$this->__('Mode')}</th>
                    <th class="data-grid-th" style="width: 14%;">
                        {$this->__('Cost')} <span class="required">*</span>
                    </th>
                    <th class="data-grid-th" style="width: 14%;">
                        {$this->__('Additional Cost')}
                    </th>
                    <th class="data-grid-th" style="width: 7%;">{$this->__('Currency')}</th>
                    <th class="data-grid-th" style="width: 7%;">{$this->__('Priority')}</th>
                    <th class="type-butt last data-grid-th" style="width: 10%;">&nbsp;</th>
                </tr>
            </thead>
            <tbody id="shipping_international_tbody">
                <!-- #shipping_table_row_template inserts here -->
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="20" class="a-right">
                        {$buttonBlock->setData('label', $this->__('Add Method'))->toHtml()}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div id="add_international_shipping_method_button" style="display: none;">

            <table style="border: none" cellpadding="0" cellspacing="0">
                <tfoot>
                    <tr>
                        <td valign="middle" align="center" style="vertical-align: middle; height: 40px">
                            {$buttonBlock->setData('label', $this->__('Add Shipping Method'))->toHtml()}
                        </td>
                    </tr>
                </tfoot>
            </table>

        </div>

        <input type="text"
               name="international_shipping_methods_validator"
               id="international_shipping_methods_validator"
               class="M2ePro-validate-shipping-methods"
               style="visibility: hidden; width: 100%; margin-top: -25px; display: block;">
HTML;
    }

    public function getAccountCombinedShippingProfile($locationType)
    {
        $html = '';
        $discountProfiles = $this->getDiscountProfiles();

        if (!empty($discountProfiles)) {
            foreach ($discountProfiles as $accountId => $value) {
                $html .= <<<HTML
                    <tr class="{$locationType}-discount-profile-account-tr label v-middle" account_id="{$accountId}">
                        <td class="label v-middle">
                            <label for="{$locationType}_shipping_discount_combined_profile_id_{$accountId}">
                                {$value['account_name']}
                            </label>
                        </td>

                        <td class="value" style="border-right: none;">
                            <select class="select admin__control-select"
                                name="shipping[{$locationType}_shipping_discount_combined_profile_id][{$accountId}]"
                                id="{$locationType}_shipping_discount_combined_profile_id_{$accountId}">

                            </select>
                            {$this->getTooltipHtml(
                    $this->__(
                        'If you have Flat Shipping Rules or Calculated Shipping Rules set up in eBay,
                                 you can choose to use them here.<br/><br/>
                                 Click <b>Refresh Profiles</b> to get your latest shipping profiles from eBay.'
                    )
                )}
                        </td>
                        <td class="value v-middle" style="border-left: none;">
                            <a href="javascript:void(0);"
                               onclick="EbayTemplateShippingObj.updateDiscountProfiles({$accountId});">
                                  {$this->__('Refresh Profiles')}
                            </a>
                        </td>
                    </tr>
HTML;
            }
        } else {
            $html .= "<tr><td colspan=\"4\" style=\"text-align: center\">
                        {$this->__('You do not have eBay Accounts added to M2E Pro.')}
                     </td></tr>";
        }

        return <<<HTML
        <table id="{$locationType}_shipping_discount_profile_table"
               class="shipping-discount-profile-table data-grid data-grid-not-hovered data-grid-striped">
            <thead>
                <tr class="headings">
                    <th class="data-grid-th v-middle" style="width: 30%">
                        {$this->__('Account')}
                    </th>
                    <th class="data-grid-th v-middle" colspan="3">
                        {$this->__('Combined Shipping Profile')}
                    </th>
                </tr>
            </thead>

            {$html}
        </table>
HTML;
    }

    // ---------------------------------------

    public function getAttributesOptions($attributeValue, $conditionCallback = false)
    {
        $options = [
            'value' => [],
            'label' => $this->__('Magento Attribute'),
            'attrs' => ['is_magento_attribute' => true],
        ];
        $helper = $this->dataHelper;

        foreach ($this->attributes as $attribute) {
            $tmpOption = [
                'value' => $attributeValue,
                'label' => $helper->escapeHtml($attribute['label']),
                'attrs' => ['attribute_code' => $attribute['code']],
            ];

            if (is_callable($conditionCallback) && $conditionCallback($attribute)) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $options['value'][] = $tmpOption;
        }

        return $options;
    }

    public function getCountryOptions()
    {
        $countryOptions = ['value' => [], 'label' => $this->__('Custom Value')];
        $helper = $this->dataHelper;

        foreach ($this->magentoHelper->getCountries() as $country) {
            if (empty($country['value'])) {
                continue;
            }

            $tmpOption = [
                'value' => Shipping::COUNTRY_MODE_CUSTOM_VALUE,
                'label' => $helper->escapeHtml($country['label']),
                'attrs' => ['attribute_code' => $country['value']],
            ];

            if (
                $this->formData['country_mode'] == Shipping::COUNTRY_MODE_CUSTOM_VALUE
                && $country['value'] == $this->formData['country_custom_value']
            ) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $countryOptions['value'][] = $tmpOption;
        }

        return $countryOptions;
    }

    public function getDomesticShippingOptions()
    {
        $options = [
            [
                'value' => Shipping::SHIPPING_TYPE_FLAT,
                'label' => $this->__('Flat: same cost to all Buyers'),
                'attrs' => ['id' => 'local_shipping_mode_flat'],
            ],
        ];

        if ($this->canDisplayLocalCalculatedShippingType()) {
            $options[] = [
                'value' => Shipping::SHIPPING_TYPE_CALCULATED,
                'label' => $this->__('Calculated: cost varies by Buyer Location'),
                'attrs' => ['id' => 'local_shipping_mode_calculated'],
            ];
        }

        if ($this->canDisplayFreightShippingType()) {
            $options[] = [
                'value' => Shipping::SHIPPING_TYPE_FREIGHT,
                'label' => $this->__('Freight: large Items'),
                'attrs' => ['id' => 'local_shipping_mode_freight'],
            ];
        }

        $options[] = [
            'value' => Shipping::SHIPPING_TYPE_LOCAL,
            'label' => $this->__('No Shipping: local pickup only'),
            'attrs' => ['id' => 'local_shipping_mode_local'],
        ];

        return $options;
    }

    public function getDispatchTimeOptions()
    {
        $options = [
            ['value' => '', 'label' => '', 'attrs' => ['class' => 'empty']],
        ];

        $isExceptionHandlingTimes = false;
        foreach ($this->marketplaceData['dispatch'] as $index => $dispatchOption) {
            if ($dispatchOption['ebay_id'] > 3 && !$isExceptionHandlingTimes) {
                $options['opt_group'] = ['value' => [], 'label' => $this->__('Exception Handling Times')];
                $isExceptionHandlingTimes = true;
            }

            if ($dispatchOption['ebay_id'] == 0) {
                $label = $this->__('Same Business Day');
            } else {
                $label = $this->__(str_replace('Day', 'Business Day', $dispatchOption['title']));
            }

            $tmpOption = [
                'value' => Shipping::DISPATCH_TIME_MODE_VALUE,
                'label' => $label,
                'attrs' => ['attribute_code' => $dispatchOption['ebay_id']],
            ];

            if (
                $this->formData['dispatch_time_mode'] == Shipping::DISPATCH_TIME_MODE_VALUE &&
                $dispatchOption['ebay_id'] == $this->formData['dispatch_time_value']
            ) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            if ($isExceptionHandlingTimes) {
                $options['opt_group']['value'][] = $tmpOption;
            } else {
                $options[] = $tmpOption;
            }
        }

        return $options;
    }

    public function getSiteVisibilityOptions()
    {
        $options = [
            [
                'value' => Shipping::CROSS_BORDER_TRADE_NONE,
                'label' => $this->__('None'),
            ],
        ];

        if ($this->canDisplayNorthAmericaCrossBorderTradeOption()) {
            $options[] = [
                'value' => Shipping::CROSS_BORDER_TRADE_NORTH_AMERICA,
                'label' => $this->__('USA / Canada'),
            ];
        }

        if ($this->canDisplayUnitedKingdomCrossBorderTradeOption()) {
            $options[] = [
                'value' => Shipping::CROSS_BORDER_TRADE_UNITED_KINGDOM,
                'label' => $this->__('United Kingdom'),
            ];
        }

        return $options;
    }

    public function getInternationalShippingOptions()
    {
        $options = [
            [
                'value' => Shipping::SHIPPING_TYPE_NO_INTERNATIONAL,
                'label' => $this->__('No International Shipping'),
                'attrs' => ['id' => 'international_shipping_none'],
            ],
            [
                'value' => Shipping::SHIPPING_TYPE_FLAT,
                'label' => $this->__('Flat: same cost to all Buyers'),
            ],
        ];

        if ($this->canDisplayInternationalCalculatedShippingType()) {
            $options[] = [
                'value' => Shipping::SHIPPING_TYPE_CALCULATED,
                'label' => $this->__('Calculated: cost varies by Buyer Location'),
            ];
        }

        return $options;
    }

    public function getMeasurementSystemOptions()
    {
        $options = [];

        if ($this->canDisplayEnglishMeasurementSystemOption()) {
            $options[] = [
                'value' => Shipping\Calculated::MEASUREMENT_SYSTEM_ENGLISH,
                'label' => $this->__('English (lbs, oz, in)'),
            ];
        }

        if ($this->canDisplayMetricMeasurementSystemOption()) {
            $options[] = [
                'value' => Shipping\Calculated::MEASUREMENT_SYSTEM_METRIC,
                'label' => $this->__('Metric (kg, g, cm)'),
            ];
        }

        return $options;
    }

    public function getPackageSizeSourceOptions()
    {
        $helper = $this->dataHelper;

        $options = [
            [
                'value' => Shipping\Calculated::PACKAGE_SIZE_NONE,
                'label' => 'None',
                'attrs' => ['id' => 'package_size_none'],
            ],
        ];

        $ebayValues = ['value' => [], 'label' => $this->__('eBay Values')];
        foreach ($this->marketplaceData['packages'] as $package) {
            $tmp = [
                'value' => Shipping\Calculated::PACKAGE_SIZE_CUSTOM_VALUE,
                'label' => $package['title'],
                'attrs' => [
                    'attribute_code' => $helper->escapeHtml($package['ebay_id']),
                    'dimensions_supported' => $package['dimensions_supported'],
                ],
            ];

            if (
                $this->formData['package_size_value'] == $package['ebay_id']
                && $this->formData['package_size_mode'] == Shipping\Calculated::PACKAGE_SIZE_CUSTOM_VALUE
            ) {
                $tmp['attrs']['selected'] = 'selected';
            }

            $ebayValues['value'][] = $tmp;
        }

        if ($ebayValues['value'] !== []) {
            $options[] = $ebayValues;
        }

        $attributesOptions = [
            'value' => [],
            'label' => $this->__('Magento Attributes'),
            'attrs' => ['is_magento_attribute' => true],
        ];
        if (isset($this->missingAttributes['package_size_attribute'])) {
            $attributesOptions['value'][] = [
                'value' => Shipping\Calculated::PACKAGE_SIZE_CUSTOM_ATTRIBUTE,
                'label' => $helper->escapeHtml($this->missingAttributes['package_size_attribute']),
                'attrs' => [
                    'attribute_code' => $this->formData['package_size_attribute'],
                ],
            ];
        }

        foreach ($this->attributesByInputTypes['text_select'] as $attribute) {
            $tmp = [
                'value' => Shipping\Calculated::PACKAGE_SIZE_CUSTOM_ATTRIBUTE,
                'label' => $helper->escapeHtml($attribute['label']),
                'attrs' => [
                    'attribute_code' => $attribute['code'],
                ],
            ];

            if (
                $this->formData['package_size_attribute'] == $attribute['code']
                && $this->formData['package_size_mode'] == Shipping\Calculated::PACKAGE_SIZE_CUSTOM_ATTRIBUTE
            ) {
                $tmp['attrs']['selected'] = 'selected';
            }

            $attributesOptions['value'][] = $tmp;
        }

        $options[] = $attributesOptions;

        return $options;
    }

    public function getDimensionsOptions($attributeCode)
    {
        $options = [
            ['value' => '', 'label' => '', 'attrs' => ['class' => 'empty-option']],
        ];
        $helper = $this->dataHelper;

        if (isset($this->missingAttributes[$attributeCode])) {
            $options[] = [
                'value' => $this->formData[$attributeCode],
                'label' => $helper->escapeHtml($this->missingAttributes[$attributeCode]),
            ];
        }

        foreach ($this->attributesByInputTypes['text'] as $attribute) {
            $options[] = [
                'value' => $attribute['code'],
                'label' => $helper->escapeHtml($attribute['label']),
            ];
        }

        return $options;
    }

    public function getWeightSourceOptions()
    {
        $options = [
            [
                'value' => Shipping\Calculated::WEIGHT_NONE,
                'label' => $this->__('None'),
                'attrs' => ['id' => 'weight_mode_none'],
            ],
            [
                'value' => Shipping\Calculated::WEIGHT_CUSTOM_VALUE,
                'label' => $this->__('Custom Value'),
            ],
            'option_group' => [
                'value' => [],
                'label' => $this->__('Magento Attributes'),
                'attrs' => ['is_magento_attribute' => true],
            ],
        ];

        $helper = $this->dataHelper;
        if (isset($this->missingAttributes['weight_attribute'])) {
            $tmpOption = [
                'value' => Shipping\Calculated::WEIGHT_CUSTOM_ATTRIBUTE,
                'label' => $helper->escapeHtml($this->missingAttributes['weight_attribute']),
                'attrs' => ['attribute_code' => $this->formData['weight_attribute']],
            ];

            if ($this->formData['weight_mode'] == Shipping\Calculated::WEIGHT_CUSTOM_ATTRIBUTE) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $options['option_group']['value'][] = $tmpOption;
        }

        foreach ($this->attributesByInputTypes['text_weight'] as $attribute) {
            $tmpOption = [
                'value' => Shipping\Calculated::WEIGHT_CUSTOM_ATTRIBUTE,
                'label' => $helper->escapeHtml($attribute['label']),
                'attrs' => ['attribute_code' => $attribute['code']],
            ];

            if (
                $this->formData['weight_mode'] == Shipping\Calculated::WEIGHT_CUSTOM_ATTRIBUTE
                && $this->formData['weight_attribute'] == $attribute['code']
            ) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $options['option_group']['value'][] = $tmpOption;
        }

        return $options;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMarketplace()
    {
        $marketplace = $this->globalDataHelper->getValue('ebay_marketplace');

        if (!$marketplace instanceof \Ess\M2ePro\Model\Marketplace) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace is required for editing Shipping Policy.');
        }

        return $marketplace;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        $account = $this->globalDataHelper->getValue('ebay_account');

        if (!$account instanceof \Ess\M2ePro\Model\Account) {
            return null;
        }

        return $account;
    }

    public function getAccountId()
    {
        return $this->getAccount() ? $this->getAccount()->getId() : null;
    }

    public function getAccounts()
    {
        return $this->ebayFactory->getObject('Account')->getCollection();
    }

    //########################################

    public function getDiscountProfiles(): array
    {
        $template = $this->globalDataHelper->getValue('ebay_template_shipping');

        $localDiscount = $template->getData('local_shipping_discount_combined_profile_id');
        $internationalDiscount = $template->getData('international_shipping_discount_combined_profile_id');

        if ($localDiscount !== null) {
            $localDiscount = \Ess\M2ePro\Helper\Json::decode($localDiscount);
        }

        if ($internationalDiscount !== null) {
            $internationalDiscount = \Ess\M2ePro\Helper\Json::decode($internationalDiscount);
        }

        $accountCollection = $this->ebayFactory->getObject('Account')->getCollection();

        $profiles = [];

        foreach ($accountCollection as $account) {
            $accountId = $account->getId();

            $temp = [];
            $temp['account_name'] = $account->getTitle();
            $temp['selected']['local'] = isset($localDiscount[$accountId]) ? $localDiscount[$accountId] : '';
            $temp['selected']['international'] = isset($internationalDiscount[$accountId]) ?
                $internationalDiscount[$accountId] : '';

            $accountProfiles = $account->getChildObject()->getData('ebay_shipping_discount_profiles');
            $temp['profiles'] = [];

            if ($accountProfiles === null) {
                $profiles[$accountId] = $temp;
                continue;
            }

            $accountProfiles = \Ess\M2ePro\Helper\Json::decode($accountProfiles);
            $marketplaceId = $this->getMarketplace()->getId();

            if (is_array($accountProfiles) && isset($accountProfiles[$marketplaceId]['profiles'])) {
                foreach ($accountProfiles[$marketplaceId]['profiles'] as $profile) {
                    $temp['profiles'][] = [
                        'type' => $this->dataHelper->escapeHtml($profile['type']),
                        'profile_id' => $this->dataHelper->escapeHtml($profile['profile_id']),
                        'profile_name' => $this->dataHelper->escapeHtml($profile['profile_name']),
                    ];
                }
            }

            $profiles[$accountId] = $temp;
        }

        return $profiles;
    }

    //########################################

    public function isCustom()
    {
        if (isset($this->_data['is_custom'])) {
            return (bool)$this->_data['is_custom'];
        }

        return false;
    }

    public function getTitle()
    {
        if ($this->isCustom()) {
            return isset($this->_data['custom_title']) ? $this->_data['custom_title'] : '';
        }

        $template = $this->globalDataHelper->getValue('ebay_template_shipping');

        if ($template === null) {
            return '';
        }

        return $template->getTitle();
    }

    public function getFormData()
    {
        if (!empty($this->formData)) {
            return $this->formData;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Template\Shipping $template */
        $template = $this->globalDataHelper->getValue('ebay_template_shipping');

        $default = $this->getDefault();
        if ($template === null || $template->getId() === null) {
            return $default;
        }

        $this->formData = $template->getData();
        $this->formData['services'] = $template->getServices();

        $calculated = $template->getCalculatedShipping();

        if ($calculated !== null) {
            $this->formData = array_merge($this->formData, $calculated->getData());
        }

        if (is_string($this->formData['excluded_locations'])) {
            $excludedLocations = \Ess\M2ePro\Helper\Json::decode($this->formData['excluded_locations']);
            $this->formData['excluded_locations'] = is_array($excludedLocations) ? $excludedLocations : [];
        } else {
            unset($this->formData['excluded_locations']);
        }

        if (is_string($this->formData['local_shipping_rate_table'])) {
            $this->formData['local_shipping_rate_table'] = \Ess\M2ePro\Helper\Json::decode(
                $this->formData['local_shipping_rate_table']
            );

            $this->formData['local_shipping_rate_table'] = array_replace_recursive(
                $default['local_shipping_rate_table'],
                $this->formData['local_shipping_rate_table']
            );
        }

        if (is_string($this->formData['international_shipping_rate_table'])) {
            $this->formData['international_shipping_rate_table'] = \Ess\M2ePro\Helper\Json::decode(
                $this->formData['international_shipping_rate_table']
            );

            $this->formData['international_shipping_rate_table'] = array_replace_recursive(
                $default['international_shipping_rate_table'],
                $this->formData['international_shipping_rate_table']
            );
        }

        return array_merge($default, $this->formData);
    }

    public function getDefault()
    {
        $default = $this->modelFactory->getObject('Ebay_Template_Shipping_Builder')->getDefaultData();
        $default['excluded_locations'] = \Ess\M2ePro\Helper\Json::decode($default['excluded_locations']);

        // populate address fields with the data from magento configuration
        // ---------------------------------------
        $store = $this->globalDataHelper->getValue('ebay_store');

        $city = $store->getConfig('shipping/origin/city');
        $regionId = $store->getConfig('shipping/origin/region_id');
        $countryId = $store->getConfig('shipping/origin/country_id');
        $postalCode = $store->getConfig('shipping/origin/postcode');

        $address = ($city !== null) ? [trim($city)] : [];

        if ($regionId) {
            $region = $this->regionFactory->create()->load($regionId);

            if ($region->getId()) {
                $address[] = trim($region->getName());
            }
        }

        $address = implode(', ', array_filter($address));

        if ($countryId) {
            $default['country_mode'] = \Ess\M2ePro\Model\Ebay\Template\Shipping::ADDRESS_MODE_CUSTOM_VALUE;
            $default['country_custom_value'] = $countryId;
        }

        if ($postalCode) {
            $default['postal_code_mode'] = \Ess\M2ePro\Model\Ebay\Template\Shipping::POSTAL_CODE_MODE_CUSTOM_VALUE;
            $default['postal_code_custom_value'] = $postalCode;
        }

        if ($address) {
            $default['address_mode'] = \Ess\M2ePro\Model\Ebay\Template\Shipping::ADDRESS_MODE_CUSTOM_VALUE;
            $default['address_custom_value'] = $address;
        }

        // ---------------------------------------

        // ---------------------------------------
        foreach (['local', 'international'] as $type) {
            if ($default[$type . '_shipping_rate_table'] === null) {
                if ($this->getAccountId() !== null) {
                    $default[$type . '_shipping_rate_table'][$this->getAccountId()] = [
                        'mode' => \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE,
                        'value' => 0,
                    ];
                } else {
                    foreach ($this->getAccounts() as $account) {
                        $default[$type . '_shipping_rate_table'][$account->getId()] = [
                            'mode' => \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_RATE_TABLE_ACCEPT_MODE,
                            'value' => 0,
                        ];
                    }
                }
            }
        }

        // ---------------------------------------

        return $default;
    }

    public function getMarketplaceData()
    {
        $data = [
            'id' => $this->getMarketplace()->getId(),
            'currency' => $this->getMarketplace()->getChildObject()->getCurrency(),
            'services' => $this->getMarketplace()->getChildObject()->getShippingInfo(),
            'packages' => $this->getMarketplace()->getChildObject()->getPackageInfo(),
            'dispatch' => $this->getSortedDispatchInfo(),
            'locations' => $this->getMarketplace()->getChildObject()->getShippingLocationInfo(),
            'locations_exclude' => $this->getSortedLocationExcludeInfo(),
            'origin_country' => $this->getMarketplace()->getChildObject()->getOriginCountry(),
        ];

        $data['services'] = $this->modifyNonUniqueShippingServicesTitles($data['services']);

        $policyLocalization = $this->getData('policy_localization');

        if (!empty($policyLocalization)) {
            $translator = $this->translationHelper;

            foreach ($data['services'] as $serviceKey => $service) {
                if (!empty($data['services'][$serviceKey]['title'])) {
                    $data['services'][$serviceKey]['title'] = $translator->__($service['title']);
                }

                foreach ($service['methods'] as $methodKey => $method) {
                    $data['services'][$serviceKey]['methods'][$methodKey]['title'] = $translator->__($method['title']);
                }
            }

            foreach ($data['locations'] as $key => $item) {
                $data['locations'][$key]['title'] = $translator->__($item['title']);
            }

            foreach ($data['locations_exclude'] as $regionKey => $region) {
                foreach ($region as $locationKey => $location) {
                    $data['locations_exclude'][$regionKey][$locationKey] = $translator->__($location);
                }
            }
        }

        return $data;
    }

    // ---------------------------------------

    private function getSortedDispatchInfo()
    {
        $dispatchInfo = $this->getMarketplace()->getChildObject()->getDispatchInfo();

        $ebayIds = [];
        foreach ($dispatchInfo as $dispatchRecord) {
            $ebayIds[] = $dispatchRecord['ebay_id'];
        }

        array_multisort($ebayIds, SORT_ASC, $dispatchInfo);

        return $dispatchInfo;
    }

    private function getSortedLocationExcludeInfo()
    {
        $sortedInfo = [
            'international' => [],
            'domestic' => [],
            'additional' => [],
        ];

        foreach ($this->getMarketplace()->getChildObject()->getShippingLocationExcludeInfo() as $item) {
            $region = $item['region'];

            strpos(strtolower($item['region']), 'worldwide') !== false && $region = 'international';
            strpos(strtolower($item['region']), 'domestic') !== false && $region = 'domestic';
            strpos(strtolower($item['region']), 'additional') !== false && $region = 'additional';

            $sortedInfo[$region][$item['ebay_id']] = $item['title'];
        }

        foreach ($sortedInfo as $code => &$info) {
            if ($code === 'domestic' || $code === 'international' || $code === 'additional') {
                continue;
            }

            $isInternational = array_key_exists($code, $sortedInfo['international']);
            $isDomestic = array_key_exists($code, $sortedInfo['domestic']);
            $isAdditional = array_key_exists($code, $sortedInfo['additional']);

            if (!$isInternational && !$isDomestic && !$isAdditional) {
                $foundedItem = [];
                foreach ($this->getMarketplace()->getChildObject()->getShippingLocationExcludeInfo() as $item) {
                    $item['ebay_id'] == $code && $foundedItem = $item;
                }

                if (empty($foundedItem)) {
                    continue;
                }

                unset($sortedInfo[$foundedItem['region']][$code]);
                $sortedInfo['international'][$code] = $foundedItem['title'];
            }

            natsort($info);
        }

        unset($info);

        return $sortedInfo;
    }

    //########################################

    private function modifyNonUniqueShippingServicesTitles($services)
    {
        foreach ($services as &$category) {
            $nonUniqueTitles = [];
            foreach ($category['methods'] as $key => $method) {
                $nonUniqueTitles[$method['title']][] = $key;
            }

            foreach ($nonUniqueTitles as $methodsKeys) {
                if (count($methodsKeys) > 1) {
                    foreach ($methodsKeys as $key) {
                        $ebayId = $category['methods'][$key]['ebay_id'];
                        $title = $category['methods'][$key]['title'];

                        $duplicatedPart = str_replace(' ', '', preg_quote($title, '/'));
                        $uniqPart = preg_replace('/\w*' . $duplicatedPart . '/i', '', $ebayId);
                        $uniqPart = preg_replace('/([A-Z]+[a-z]*)/', '${1} ', $uniqPart);

                        $category['methods'][$key]['title'] = trim($title) . ' ' . str_replace('_', '', $uniqPart);
                    }
                }
            }
        }

        return $services;
    }

    //########################################

    public function getAttributesJsHtml()
    {
        $html = '';

        $attributes = $this->magentoAttributeHelper->filterByInputTypes(
            $this->attributes,
            ['text', 'price', 'select']
        );

        foreach ($attributes as $attribute) {
            $code = $this->dataHelper->escapeHtml($attribute['code']);
            $html .= sprintf('<option value="%s">%s</option>', $code, $attribute['label']);
        }

        return $this->dataHelper->escapeJs($html);
    }

    public function getMissingAttributes()
    {
        $formData = $this->getFormData();

        if (empty($formData)) {
            return [];
        }

        $attributes = [];

        // m2epro_ebay_template_shipping_service
        // ---------------------------------------
        $attributes['services'] = [];

        foreach ($formData['services'] as $i => $service) {
            $mode = 'cost_mode';
            $code = 'cost_value';

            if ($service[$mode] == \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_ATTRIBUTE) {
                if (!$this->isExistInAttributesArray($service[$code])) {
                    $label = $this->magentoAttributeHelper->getAttributeLabel($service[$code]);
                    $attributes['services'][$i][$code] = $label;
                }
            }

            $mode = 'cost_mode';
            $code = 'cost_additional_value';

            if ($service[$mode] == \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_ATTRIBUTE) {
                if (!$this->isExistInAttributesArray($service[$code])) {
                    $label = $this->magentoAttributeHelper->getAttributeLabel($service[$code]);
                    $attributes['services'][$i][$code] = $label;
                }
            }
        }

        // ---------------------------------------

        // m2epro_ebay_template_shipping_calculated
        // ---------------------------------------
        if (!empty($formData['calculated'])) {
            $code = 'package_size_attribute';
            if (!$this->isExistInAttributesArray($formData['calculated'][$code])) {
                $label = $this->magentoAttributeHelper->getAttributeLabel($formData['calculated'][$code]);
                $attributes['calculated'][$code] = $label;
            }

            $code = 'dimension_width_attribute';
            if (!$this->isExistInAttributesArray($formData['calculated'][$code])) {
                $label = $this->magentoAttributeHelper->getAttributeLabel($formData['calculated'][$code]);
                $attributes['calculated'][$code] = $label;
            }

            $code = 'dimension_length_attribute';
            if (!$this->isExistInAttributesArray($formData['calculated'][$code])) {
                $label = $this->magentoAttributeHelper->getAttributeLabel($formData['calculated'][$code]);
                $attributes['calculated'][$code] = $label;
            }

            $code = 'dimension_depth_attribute';
            if (!$this->isExistInAttributesArray($formData['calculated'][$code])) {
                $label = $this->magentoAttributeHelper->getAttributeLabel($formData['calculated'][$code]);
                $attributes['calculated'][$code] = $label;
            }

            $code = 'weight_attribute';
            if (!$this->isExistInAttributesArray($formData['calculated'][$code])) {
                $label = $this->magentoAttributeHelper->getAttributeLabel($formData['calculated'][$code]);
                $attributes['calculated'][$code] = $label;
            }
        }

        // ---------------------------------------

        return $attributes;
    }

    //########################################

    public function isExistInAttributesArray($code)
    {
        if (!$code) {
            return true;
        }

        return $this->magentoAttributeHelper->isExistInAttributesArray($code, $this->attributes);
    }

    //########################################

    public function canDisplayLocalShippingRateTable()
    {
        return $this->getMarketplace()->getChildObject()->isLocalShippingRateTableEnabled();
    }

    public function canDisplayFreightShippingType()
    {
        return $this->getMarketplace()->getChildObject()->isFreightShippingEnabled();
    }

    public function canDisplayCalculatedShippingType()
    {
        return $this->getMarketplace()->getChildObject()->isCalculatedShippingEnabled();
    }

    public function canDisplayLocalCalculatedShippingType()
    {
        if (!$this->canDisplayCalculatedShippingType()) {
            return false;
        }

        return true;
    }

    public function canDisplayInternationalCalculatedShippingType()
    {
        if (!$this->canDisplayCalculatedShippingType()) {
            return false;
        }

        return true;
    }

    public function canDisplayInternationalShippingRateTable()
    {
        return $this->getMarketplace()->getChildObject()->isInternationalShippingRateTableEnabled();
    }

    public function canDisplayNorthAmericaCrossBorderTradeOption()
    {
        $marketplace = $this->getMarketplace();

        return $marketplace->getId() == 3   // UK
            || $marketplace->getId() == 17; // Ireland
    }

    public function canDisplayUnitedKingdomCrossBorderTradeOption()
    {
        $marketplace = $this->getMarketplace();

        return $marketplace->getId() == 1   // US
            || $marketplace->getId() == 2;  // Canada
    }

    public function canDisplayEnglishMeasurementSystemOption()
    {
        return $this->getMarketplace()->getChildObject()->isEnglishMeasurementSystemEnabled();
    }

    public function canDisplayMetricMeasurementSystemOption()
    {
        return $this->getMarketplace()->getChildObject()->isMetricMeasurementSystemEnabled();
    }

    public function canDisplayGlobalShippingProgram()
    {
        return $this->getMarketplace()->getChildObject()->isGlobalShippingProgramEnabled();
    }

    //########################################

    public function isLocalShippingModeCalculated()
    {
        $formData = $this->getFormData();

        if (!isset($formData['local_shipping_mode'])) {
            return false;
        }

        $mode = $formData['local_shipping_mode'];

        return $mode == \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_TYPE_CALCULATED;
    }

    public function isInternationalShippingModeCalculated()
    {
        $formData = $this->getFormData();

        if (!isset($formData['international_shipping_mode'])) {
            return false;
        }

        $mode = $formData['international_shipping_mode'];

        return $mode == \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_TYPE_CALCULATED;
    }

    //########################################

    public function getLocalShippingRateTables(\Ess\M2ePro\Model\Account $account)
    {
        return $this->getShippingRateTables('domestic', $account);
    }

    public function getInternationalShippingRateTables(\Ess\M2ePro\Model\Account $account)
    {
        return $this->getShippingRateTables('international', $account);
    }

    protected function getShippingRateTables($type, \Ess\M2ePro\Model\Account $account)
    {
        $rateTables = $account->getChildObject()->getRateTables();

        if (empty($rateTables) || !is_array($rateTables)) {
            return [];
        }

        $rateTablesData = [];
        $countryCode = $this->getMarketplace()->getChildObject()->getOriginCountry();

        foreach ($rateTables as $rateTable) {
            if (
                empty($rateTable['countryCode']) ||
                strtolower($rateTable['countryCode']) != $countryCode ||
                strtolower($rateTable['locality']) != $type
            ) {
                continue;
            }

            if (empty($rateTable['rateTableId'])) {
                continue;
            }

            $rateTablesData[$rateTable['rateTableId']] = isset($rateTable['name']) ? $rateTable['name'] :
                $rateTable['rateTableId'];
        }

        return $rateTablesData;
    }

    //########################################

    public function getCurrencyAvailabilityMessage()
    {
        $marketplace = $this->globalDataHelper->getValue('ebay_marketplace');
        $store = $this->globalDataHelper->getValue('ebay_store');
        $template = $this->globalDataHelper->getValue('ebay_template_selling_format');

        if ($template === null || $template->getId() === null) {
            $templateData = $this->getDefault();
            $templateData['component_mode'] = \Ess\M2ePro\Helper\Component\Ebay::NICK;
        } else {
            $templateData = $template->getData();
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Shipping\Messages $messagesBlock */
        $messagesBlock = $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Template\Shipping\Messages::class);
        $messagesBlock->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $messagesBlock->setTemplateNick(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING);

        $messagesBlock->setData('template_data', $templateData);
        $messagesBlock->setData('marketplace_id', $marketplace ? $marketplace->getId() : null);
        $messagesBlock->setData('store_id', $store ? $store->getId() : null);

        $messages = $messagesBlock->getMessages();
        if (empty($messages)) {
            return '';
        }

        return $messagesBlock->getMessagesHtml($messages);
    }

    //########################################

    protected function _beforeToHtml()
    {
        $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                            ->setData(
                                [
                                    'label' => $this->__('Remove'),
                                    'class' => 'delete icon-btn remove_shipping_method_button',
                                ]
                            );
        $this->setChild('remove_shipping_method_button', $buttonBlock);

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $this->jsTranslator->addTranslations(
            [
                'Location or Zip/Postal Code should be specified.' => $this->__(
                    'Location or Zip/Postal Code should be specified.'
                ),
                'Select one or more international ship-to Locations.' => $this->__(
                    'Select one or more international ship-to Locations.'
                ),
                'PayPal payment method should be specified for Cross Border trade.' => $this->__(
                    'PayPal payment method should be specified for Cross Border trade.'
                ),
                'You should specify at least one Shipping Method.' => $this->__(
                    'You should specify at least one Shipping Method.'
                ),
                'None' => $this->__('None'),
                'Select Shipping Service' => $this->__(
                    'Select Shipping Service'
                ),

                'Excluded Shipping Locations' => $this->__('Excluded Shipping Locations'),
                'No Locations are currently excluded.' => $this->__('No Locations are currently excluded.'),
                'selected' => $this->__('selected'),

                'Refresh Rate Tables' => $this->__('Refresh Rate Tables'),

                'Download Shipping Rate Tables' => $this->__(
                    'Download Shipping Rate Tables'
                ),
                'lbs.oz' => $this->__('lbs.oz'),
                'kg, g' => $this->__('kg, g'),
                'inches' => $this->__('inches'),
                'cm' => $this->__('cm'),
                'sell_api_popup_text' => $this->__(
                    <<<HTML
    To download the Shipping Rate Tables, you should grant M2E Pro access to your eBay data.
If you consent, click <strong>Confirm</strong>. You will be redirected to M2E Pro eBay Account page. Under the
<strong>Sell API Details</strong> section, click <strong>Get Token</strong> (the instructions can be found
<a href="%url%" target="_blank">here</a>). After an eBay token is obtained, <strong>Save</strong> the changes to
Account configuration.<br><br>
The Rate Tables will be downloaded to M2E Pro Shipping Policy automatically. Select
one which should be applied to your Items.<br><br>
<strong>Note</strong>, you need to repeat the procedure above for each eBay Account separately.
HTML
                    ,
                    $this->supportHelper->getDocumentationArticleUrl('display/eBayMagentoV6X/eBay+Guaranteed+Delivery')
                ),
                'You are submitting different Shipping Rate Table modes for the domestic and international shipping. ' .
                'It contradicts eBay requirements. Please edit the settings.' => $this->__(
                    'You are submitting different Shipping Rate Table modes for the domestic and international shipping.
                It contradicts eBay requirements. Please edit the settings.'
                ),
            ]
        );

        $this->jsUrl->addUrls(
            [
                'ebay_template_shipping/updateDiscountProfiles' => $this->getUrl(
                    '*/ebay_template_shipping/updateDiscountProfiles',
                    [
                        'marketplace_id' => $this->marketplaceData['id'],
                        'account_id' => $this->getAccountId(),
                    ]
                ),
                'ebay_template_shipping/getRateTableData' => $this->getUrl(
                    '*/ebay_template_shipping/getRateTableData'
                ),
                'ebay_account/edit' => $this->getUrl(
                    '*/ebay_account/edit',
                    $this->getRequest()->getParam('wizard') ?
                        [
                            'wizard' => 1,
                            'close_on_save' => true,
                        ] : []
                ),
            ]
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Template\Shipping::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Template\Shipping\Service::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated::class)
        );

        $missingAttributes = \Ess\M2ePro\Helper\Json::encode($this->missingAttributes);
        $services = \Ess\M2ePro\Helper\Json::encode($this->marketplaceData['services']);
        $locations = \Ess\M2ePro\Helper\Json::encode($this->marketplaceData['locations']);
        $discountProfiles = \Ess\M2ePro\Helper\Json::encode($this->getDiscountProfiles());
        $originCountry = \Ess\M2ePro\Helper\Json::encode($this->marketplaceData['origin_country']);

        $formDataServices = \Ess\M2ePro\Helper\Json::encode($this->formData['services']);

        $rateTablesHtml = <<<JS
JS;

        if ($this->getAccountId() !== null) {
            if ($this->canDisplayLocalShippingRateTable()) {
                $rateTablesValue = \Ess\M2ePro\Helper\Json::encode(
                    $this->formData['local_shipping_rate_table'][$this->getAccountId()]['value']
                );

                $localShippingRateTablesJson = \Ess\M2ePro\Helper\Json::encode(
                    $this->getLocalShippingRateTables($this->getAccount())
                );
                $rateTablesHtml .= <<<JS
    EbayTemplateShippingObj.renderRateTables({
        accountId: {$this->getAccountId()},
        marketplaceId: {$this->getMarketplace()->getId()},
        elementId: "local_shipping_rate_table_value_{$this->getAccountId()}",
        data: {$localShippingRateTablesJson},
        value: {$rateTablesValue},
        type: "local"
    });
    EbayTemplateShippingObj.rateTablesIds.push("local_shipping_rate_table_value_{$this->getAccountId()}");
JS;
            }

            if ($this->canDisplayInternationalShippingRateTable()) {
                $rateTablesValue = \Ess\M2ePro\Helper\Json::encode(
                    $this->formData['international_shipping_rate_table'][$this->getAccountId()]['value']
                );
                $internationalShippingRateTablesJson = \Ess\M2ePro\Helper\Json::encode(
                    $this->getInternationalShippingRateTables($this->getAccount())
                );

                $rateTablesHtml .= <<<JS
    EbayTemplateShippingObj.renderRateTables({
        accountId: {$this->getAccountId()},
        marketplaceId: {$this->getMarketplace()->getId()},
        elementId: "international_shipping_rate_table_value_{$this->getAccountId()}",
        data: {$internationalShippingRateTablesJson},
        value: {$rateTablesValue},
        type: "international"
    });
    EbayTemplateShippingObj.rateTablesIds.push("international_shipping_rate_table_value_{$this->getAccountId()}");
JS;
            }
        } else {
            if ($this->getAccounts()->getSize()) {
                foreach ($this->getAccounts() as $account) {
                    if ($this->canDisplayLocalShippingRateTable()) {
                        $rateTablesValue = \Ess\M2ePro\Helper\Json::encode(
                            $this->formData['local_shipping_rate_table'][$account->getId()]['value']
                        );
                        $localShippingRateTablesJson = \Ess\M2ePro\Helper\Json::encode(
                            $this->getLocalShippingRateTables($account)
                        );

                        $rateTablesHtml .= <<<JS
    EbayTemplateShippingObj.renderRateTables({
        accountId: {$account->getId()},
        marketplaceId: {$this->getMarketplace()->getId()},
        elementId: "local_shipping_rate_table_value_{$account->getId()}",
        data: {$localShippingRateTablesJson},
        value: {$rateTablesValue},
        type: "local"
    });
    EbayTemplateShippingObj.rateTablesIds.push("local_shipping_rate_table_value_{$account->getId()}");
JS;
                    }

                    if ($this->canDisplayInternationalShippingRateTable()) {
                        $rateTablesValue = \Ess\M2ePro\Helper\Json::encode(
                            $this->formData['international_shipping_rate_table'][$account->getId()]['value']
                        );
                        $internationalShippingRateTablesJson = \Ess\M2ePro\Helper\Json::encode(
                            $this->getInternationalShippingRateTables($account)
                        );

                        $rateTablesHtml .= <<<JS
    EbayTemplateShippingObj.renderRateTables({
        accountId: {$account->getId()},
        marketplaceId: {$this->getMarketplace()->getId()},
        elementId: "international_shipping_rate_table_value_{$account->getId()}",
        data: {$internationalShippingRateTablesJson},
        value: {$rateTablesValue},
        type: "international"
    });
    EbayTemplateShippingObj.rateTablesIds.push("international_shipping_rate_table_value_{$account->getId()}");
JS;
                    }
                }
            }
        }

        $selectedLocations = \Ess\M2ePro\Helper\Json::encode($this->formData['excluded_locations']);

        $this->js->addRequireJs(
            [
                'attr' => 'M2ePro/Attribute',
                'form' => 'M2ePro/Ebay/Template/Shipping',
                'etsel' => 'M2ePro/Ebay/Template/Shipping/ExcludedLocations',
            ],
            <<<JS

        if (typeof AttributeObj === 'undefined') {
            window.AttributeObj = new Attribute();
        }
        window.AttributeObj.attrData = '{$this->getAttributesJsHtml()}';

        window.EbayTemplateShippingObj = new EbayTemplateShipping();

        var shippingMethods = {$formDataServices};
        _.forEach(shippingMethods, function(shipping, i) {
            shippingMethods[i].locations = shipping.locations.evalJSON();
        });

        EbayTemplateShippingObj.shippingMethods = shippingMethods;

        window.EbayTemplateShippingExcludedLocationsObj = new EbayTemplateShippingExcludedLocations();
        EbayTemplateShippingExcludedLocationsObj.setSelectedLocations({$selectedLocations});

        {$rateTablesHtml}

        EbayTemplateShippingObj.counter = {
            local: 0,
            international: 0,
            total: 0
        };

        EbayTemplateShippingObj.missingAttributes = {$missingAttributes};
        EbayTemplateShippingObj.shippingServices = {$services};
        EbayTemplateShippingObj.shippingLocations = {$locations};
        EbayTemplateShippingObj.discountProfiles = {$discountProfiles};
        EbayTemplateShippingObj.originCountry = {$originCountry};

        EbayTemplateShippingObj.initObservers();
JS
        );

        return parent::_toHtml();
    }

    //########################################
}
