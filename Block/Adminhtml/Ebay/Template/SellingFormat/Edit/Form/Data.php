<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Ebay\Template\SellingFormat;

class Data extends AbstractForm
{
    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;
    /** @var \Magento\Framework\Locale\Currency */
    protected $currency;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayHelper;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbStructureHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Component\Ebay $ebayHelper
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $dbStructureHelper
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Locale\Currency $currency
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory
     * @param \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbStructureHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Locale\Currency $currency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->currency = $currency;
        $this->ebayFactory = $ebayFactory;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->supportHelper = $supportHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->dataHelper = $dataHelper;
        $this->ebayHelper = $ebayHelper;
        $this->dbStructureHelper = $dbStructureHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm(): Data
    {
        $attributes = $this->globalDataHelper->getValue('ebay_attributes');

        $attributesByInputTypes = $this->getAttributesByInputTypes();

        $formData = $this->getFormData();
        $default = $this->getDefault();
        $formData = array_merge($default, $formData);

        if ($this->getMarketplace() !== null) {
            $availableMarketplaces = [$this->getMarketplace()];
        } else {
            $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection();
            $collection->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);
            $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
            $collection->setOrder('sorder', 'ASC');

            $availableMarketplaces = $collection->getItems();
        }

        $charity = \Ess\M2ePro\Helper\Json::decode($formData['charity']);

        $availableCharity = [];
        foreach ($availableMarketplaces as $marketplace) {
            if (isset($charity[$marketplace->getId()])) {
                $availableCharity[$marketplace->getId()] = $charity[$marketplace->getId()];
            }
        }
        $formData['charity'] = $availableCharity;

        $formData['fixed_price_modifier'] =
            \Ess\M2ePro\Helper\Json::decode($formData['fixed_price_modifier']) ?: [];

        $taxCategories = $this->getTaxCategoriesInfo();

        $form = $this->_formFactory->create();

        $form->addField(
            'selling_format_id',
            'hidden',
            [
                'name' => 'selling_format[id]',
                'value' => (!$this->isCustom() && isset($formData['id'])) ? (int)$formData['id'] : '',
            ]
        );

        $form->addField(
            'selling_format_title',
            'hidden',
            [
                'name' => 'selling_format[title]',
                'value' => $this->getTitle(),
            ]
        );

        $form->addField(
            'selling_format_is_custom_template',
            'hidden',
            [
                'name' => 'selling_format[is_custom_template]',
                'value' => $this->isCustom() ? 1 : 0,
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_selling_format_edit_form_general',
            [
                'legend' => $this->__('How You Want To Sell Your Item'),
                'collapsable' => true,
            ]
        );

        $preparedAttributes = [];
        if (
            $formData['listing_type'] == SellingFormat::LISTING_TYPE_ATTRIBUTE
            && !$this->magentoAttributeHelper
                ->isExistInAttributesArray($formData['listing_type_attribute'], $attributes)
            && $formData['listing_type_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['listing_type_attribute'],
                    'selected' => 'selected',
                ],
                'value' => SellingFormat::LISTING_TYPE_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper->getAttributeLabel($formData['listing_type_attribute']),
            ];
        }

        foreach ($attributesByInputTypes['text_select'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['listing_type'] == SellingFormat::LISTING_TYPE_ATTRIBUTE
                && $attribute['code'] == $formData['listing_type_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::LISTING_TYPE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'listing_type',
            self::SELECT,
            [
                'name' => 'selling_format[listing_type]',
                'label' => $this->__('Listing Type'),
                'values' => [
                    SellingFormat::LISTING_TYPE_AUCTION => $this->__('Auction'),
                    SellingFormat::LISTING_TYPE_FIXED => $this->__('Fixed Price'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['listing_type'] != SellingFormat::LISTING_TYPE_ATTRIBUTE
                    ? $formData['listing_type'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    '<b>Auction</b> - your listings will have a starting price and last for the selected listing
                    duration or until you accept a buyer bid.
                    To set Auction listing type via Magento Attribute,
                    fill Magento Product Attribute with value "Chinese".<br/>
                    <b>Fixed Price</b> - your listings will have a set price and last for the entire listing duration
                    or until you run out of stock.
                    To set Fixed Price listing type via Magento Attribute, fill Magento Product Attribute
                    with value "FixedPriceItem".<br/><br/>

                    <b>Note:</b> If selected Magento Attribute has a wrong or empty value, your items
                    will be listed as Fixed Price listings.'
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldset->addField(
            'listing_type_attribute',
            'hidden',
            [
                'name' => 'selling_format[listing_type_attribute]',
            ]
        );

        $fieldset->addField(
            'listing_is_private',
            self::SELECT,
            [
                'label' => $this->__('Private Listing'),
                'name' => 'selling_format[listing_is_private]',
                'values' => [
                    SellingFormat::LISTING_IS_PRIVATE_NO => $this->__('No'),
                    SellingFormat::LISTING_IS_PRIVATE_YES => $this->__('Yes'),
                ],
                'value' => $formData['listing_is_private'],
                'tooltip' => $this->__(
                    'Making your Listing Private means that the details of the Listing won\'t be shown on the
                    Feedback Profile Page for you or the Buyer.<br/><br/>
                    This can be useful in cases where you or the Buyer might want to be discreet about the
                    Item and/or the final Price - such as the sale of
                    high-priced Items or approved pharmaceutical Products.
                    You should only make your Listing Private for a specific reason.'
                ),
            ]
        );

        $fieldset->addField(
            'restricted_to_business',
            self::SELECT,
            [
                'label' => $this->__('For Business Users Only'),
                'name' => 'selling_format[restricted_to_business]',
                'values' => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Enabled'),
                ],
                'value' => $formData['restricted_to_business'],
                'tooltip' => $this->__(
                    'If <strong>Yes</strong>, this indicates that you elect to offer the
                     Item exclusively to business users. <br/>
                     If <strong>No</strong>, this indicates that you elect to offer the Item to all users. <br/><br/>
                     Applicable only to business Sellers residing in Germany, Austria,
                     or Switzerland who are Listing in a B2B VAT-enabled Category on the eBay Germany (DE),
                     Austria (AT), or Switzerland (CH) Marketplaces. <br/>
                     If this argument is <strong>Yes</strong>, you must have a valid VAT-ID registered with eBay,
                     and <i>BusinessSeller</i> must also be true.'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_selling_format_edit_form_qty_and_duration',
            [
                'legend' => $this->__('Quantity And Duration'),
                'collapsable' => true,
            ]
        );

        $preparedAttributes = [];
        foreach ($this->ebayHelper->getAvailableDurations() as $id => $label) {
            $preparedAttributes[] = [
                'attrs' => ['class' => 'durationId', 'id' => "durationId$id"],
                'value' => $id,
                'label' => $label,

            ];
        }

        $durationTooltip = '<span class="duration_note duration_auction_note" style="display: none;">'
            . $this->__('A length of time your auction-style listings will show on eBay.')
            . '</span><span class="duration_note duration_fixed_note" style="display: none;">' .
            $this->__(
                'Your fixed-price listings will renew automatically every 30 days until the items
                            sell out or you end the listings.<br><br>
                            <b>Note:</b> By using eBay out-of-stock feature, your item with zero quantity stays active
                            but is hidden from search results until you increase the quantity.
                            Read more <a href="%url%" target="_blank">here</a>.',
                $this->supportHelper->getSupportUrl('/support/solutions/articles/9000218905')
            )
            . '</span><span class="duration_note duration_attribute_note" style="display: none;">'
            . $this->__(
                'Attribute must contain a whole number. If you choose "Good Till Cancelled"
                            the Attribute must contain 100.'
            )
            . '</span>';

        $fieldset->addField(
            'duration_mode',
            self::SELECT,
            [
                'container_id' => 'duration_mode_container',
                'label' => $this->__('Listing Duration'),
                'name' => 'selling_format[duration_mode]',
                'values' => $preparedAttributes,
                'value' => $formData['duration_mode'],
                'tooltip' => $durationTooltip,
            ]
        );

        $preparedAttributes = [];
        if (
            $formData['listing_type'] == SellingFormat::LISTING_TYPE_ATTRIBUTE
            && !$this->magentoAttributeHelper
                ->isExistInAttributesArray($formData['duration_attribute'], $attributes)
            && $formData['duration_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['duration_attribute'],
                    'selected' => 'selected',
                ],
                'value' => SellingFormat::LISTING_TYPE_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper->getAttributeLabel($formData['duration_attribute']),
            ];
        }

        foreach ($attributesByInputTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['listing_type'] == SellingFormat::LISTING_TYPE_ATTRIBUTE
                && $formData['duration_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::LISTING_TYPE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'duration_attribute',
            self::SELECT,
            [
                'container_id' => 'duration_attribute_container',
                'label' => $this->__('Listing Duration'),
                'values' => [
                    [
                        'label' => '',
                        'value' => '',
                        'attrs' => ['style' => 'display: none'],
                    ],
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'create_magento_attribute' => true,
                'required' => true,
                'tooltip' => $durationTooltip,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'listing_duration_attribute_value',
            'hidden',
            [
                'name' => 'selling_format[duration_attribute]',
                'value' => $formData['duration_attribute'],
            ]
        );

        $preparedAttributes = [
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED,
                'label' => $this->__('QTY'),
            ],
        ];

        if (
            $formData['qty_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE
            && !$this->magentoAttributeHelper
                ->isExistInAttributesArray($formData['qty_custom_attribute'], $attributes)
            && $formData['qty_custom_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['qty_custom_attribute'],
                    'selected' => 'selected',
                ],
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper->getAttributeLabel($formData['qty_custom_attribute']),
            ];
        }

        foreach ($attributesByInputTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['qty_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE
                && $formData['qty_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'qty_mode',
            self::SELECT,
            [
                'container_id' => 'qty_mode_tr',
                'name' => 'selling_format[qty_mode]',
                'label' => $this->__('Quantity'),
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT => $this->__('Product Quantity'),
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER => $this->__('Custom Value'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                            'new_option_value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE,
                        ],
                    ],
                ],
                'value' => $formData['qty_mode'] != \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE
                    ? $formData['qty_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'The number of Items you want to sell on eBay.<br/><br/>
                    <b>Product Quantity:</b> the number of Items on eBay will be the same as in Magento.<br/>
                    <b>Custom Value:</b> set a Quantity in the Policy here.<br/>
                    <b>Magento Attribute:</b> takes the number from the Attribute you specify.'
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'qty_custom_attribute',
            'hidden',
            [
                'name' => 'selling_format[qty_custom_attribute]',
            ]
        );

        $fieldset->addField(
            'qty_custom_value',
            'text',
            [
                'container_id' => 'qty_mode_cv_tr',
                'label' => $this->__('Quantity Value'),
                'name' => 'selling_format[qty_custom_value]',
                'value' => $formData['qty_custom_value'],
                'class' => 'validate-digits',
                'required' => true,
            ]
        );

        $preparedAttributes = [];
        for ($i = 100; $i >= 5; $i -= 5) {
            $preparedAttributes[] = [
                'value' => $i,
                'label' => $i . ' %',
            ];
        }

        $fieldset->addField(
            'qty_percentage',
            self::SELECT,
            [
                'container_id' => 'qty_percentage_tr',
                'label' => $this->__('Quantity Percentage'),
                'name' => 'selling_format[qty_percentage]',
                'values' => $preparedAttributes,
                'value' => $formData['qty_percentage'],
                'tooltip' => $this->__(
                    'Sets the percentage for calculation of Items number to be Listed on eBay basing on
                    Product Quantity or Magento Attribute. E.g., if Quantity Percentage is set to 10% and
                    Product Quantity is 100, the Quantity to be Listed on
                    eBay will be calculated as <br/>100 *10%  = 10.<br/>'
                ),
            ]
        );

        $fieldset->addField(
            'qty_modification_mode',
            self::SELECT,
            [
                'container_id' => 'qty_modification_mode_tr',
                'label' => $this->__('Conditional Quantity'),
                'name' => 'selling_format[qty_modification_mode]',
                'values' => [
                    SellingFormat::QTY_MODIFICATION_MODE_OFF => $this->__('Disabled'),
                    SellingFormat::QTY_MODIFICATION_MODE_ON => $this->__('Enabled'),
                ],
                'value' => $formData['qty_modification_mode'],
                'tooltip' => $this->__(
                    'Choose whether to limit the amount of Stock you list on eBay, eg because you want to set
                    some Stock aside for sales off eBay.<br/><br/>
                    If this Setting is <b>Enabled</b> you can specify the maximum Quantity to be Listed.
                    If this Setting is <b>Disabled</b> all Stock for the Product will be Listed as available on eBay.'
                ),
            ]
        );

        $fieldset->addField(
            'qty_min_posted_value',
            'text',
            [
                'container_id' => 'qty_min_posted_value_tr',
                'label' => $this->__('Minimum Quantity to Be Listed'),
                'name' => 'selling_format[qty_min_posted_value]',
                'value' => $formData['qty_min_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'tooltip' => $this->__(
                    'If you have 2 pieces in Stock but set a Minimum Quantity to Be Listed of 5,
                    Item will not be Listed on eBay.<br/>
                    Otherwise, the Item will be Listed with Quantity according to the Settings in the Selling Policy'
                ),
            ]
        );

        $fieldset->addField(
            'qty_max_posted_value',
            'text',
            [
                'container_id' => 'qty_max_posted_value_tr',
                'label' => $this->__('Maximum Quantity to Be Listed'),
                'name' => 'selling_format[qty_max_posted_value]',
                'value' => $formData['qty_max_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'tooltip' => $this->__(
                    'Set a maximum number to sell on eBay, e.g. if you have 10 Items in Stock but want
                    to keep 2 Items back, set a Maximum Quantity of 8.'
                ),
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['lot_size_mode'] == SellingFormat::LOT_SIZE_MODE_ATTRIBUTE
                && $formData['lot_size_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::LOT_SIZE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'lot_size_mode',
            self::SELECT,
            [
                'container_id' => 'lot_size_mode_tr',
                'label' => $this->__('Specify Lot Size'),
                'name' => 'selling_format[lot_size_mode]',
                'values' => [
                    SellingFormat::LOT_SIZE_MODE_DISABLED => $this->__('No'),
                    SellingFormat::LOT_SIZE_MODE_CUSTOM_VALUE => $this->__('Custom Value'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                            'new_option_value' => SellingFormat::LOT_SIZE_MODE_ATTRIBUTE,
                        ],
                    ],
                ],
                'value' => $formData['lot_size_mode'] != SellingFormat::LOT_SIZE_MODE_ATTRIBUTE
                    ? $formData['lot_size_mode'] : '',
                'tooltip' => $this->__(
                    'Select <b>Custom Value</b> to specify the number
                    of identical Items you are selling together as Lot.'
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'lot_size_attribute',
            'hidden',
            [
                'name' => 'selling_format[lot_size_attribute]',
            ]
        );

        $fieldset->addField(
            'lot_size_custom_value',
            'text',
            [
                'container_id' => 'lot_size_cv_tr',
                'label' => $this->__('Items in Lot'),
                'name' => 'selling_format[lot_size_custom_value]',
                'value' => $formData['lot_size_custom_value'],
                'class' => 'M2ePro-lot-size',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'ignore_variations_value',
            self::SELECT,
            [
                'container_id' => 'ignore_variations_value_tr',
                'label' => $this->__('Ignore Variations'),
                'name' => 'selling_format[ignore_variations]',
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $formData['ignore_variations'],
                'tooltip' => $this->__(
                    'Choose how you want to list Configurable, Grouped, Bundle, Simple With Custom Options
                    and Downloadable with Separated Links Products.
                    Choosing <b>Yes</b> will list these types of Products as
                    if they are Simple Products without Variations.'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_selling_format_edit_form_taxation',
            [
                'legend' => $this->__('Taxation'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'vat_mode',
            self::SELECT,
            [
                'container_id' => 'vat_mode_tr',
                'label' => __('Use VAT'),
                'name' => 'selling_format[vat_mode]',
                'value' => $formData['vat_mode'],
                'values' => [
                    SellingFormat::VAT_MODE_NO => __('No'),
                    SellingFormat::VAT_MODE_INCLUDING_IN_PRICE => __('Including in price'),
                    SellingFormat::VAT_MODE_ON_TOP_OF_PRICE => __('On top of price'),
                ],
                'tooltip' => __(
                    <<<TEXT
Choose if you’d like to add VAT rate to the Price of eBay Items:
<br/>
<b>Including in price</b> - Price of an Item will <b>not</b> be increased, VAT rate will be included in it.
<br/>
<b>On top of price</b> - Price of an Item will be increased by a specified VAT percentage.
<br/>
To remove the specified VAT rate from the Item Price on the channel and let eBay treat it as a net Price, set 0%.
<br/>
For more information, please check this <a href="%1" target='_blank'>article</a>
TEXT
                    ,
                    $this->supportHelper->getDocumentationArticleUrl(
                        'help/m2/ebay-integration/m2e-pro-listing/create-new-listing/step-2-policies/selling#d3f67ad57d374431b86d1810223fe3ce'
                    )
                ),
            ]
        );

        $fieldset->addField(
            'vat_percent',
            'text',
            [
                'container_id' => 'vat_percent_tr',
                'label' => __('VAT Rate, %'),
                'name' => 'selling_format[vat_percent]',
                'value' => $formData['vat_percent'],
                'class' => 'M2ePro-validate-vat',
            ]
        );

        $fieldset->addField(
            'tax_table_mode',
            self::SELECT,
            [
                'container_id' => 'tax_table_mode_tr',
                'label' => __('Use eBay Tax Table'),
                'name' => 'selling_format[tax_table_mode]',
                'value' => $formData['tax_table_mode'],
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'tooltip' => __(
                    'Tax Tables are set up directly in your eBay Seller Central and are available only for Canada,
                    Canada (Fr), USA, and eBay Motors.'
                ),
            ]
        );

        if ($this->ebayHelper->isShowTaxCategory()) {
            $preparedValues = [];

            if (!empty($taxCategories)) {
                $preparedAttributesCategories = [];

                foreach ($taxCategories as $taxCategory) {
                    $attrs = ['attribute_code' => $taxCategory['ebay_id']];
                    if (
                        $formData['tax_category_mode'] == SellingFormat::TAX_CATEGORY_MODE_VALUE
                        && $formData['tax_category_value'] == $taxCategory['ebay_id']
                    ) {
                        $attrs['selected'] = 'selected';
                    }
                    $preparedAttributesCategories[] = [
                        'attrs' => $attrs,
                        'value' => SellingFormat::TAX_CATEGORY_MODE_VALUE,
                        'label' => $taxCategory['title'],
                    ];
                }

                $preparedValues[] = [
                    'label' => $this->__('Ebay Recommended'),
                    'value' => $preparedAttributesCategories,
                ];
            }

            if (!empty($attributesByInputTypes['text'])) {
                $preparedAttributes = [];

                foreach ($attributesByInputTypes['text'] as $attribute) {
                    $attrs = ['attribute_code' => $attribute['code']];
                    if (
                        $formData['tax_category_mode'] == SellingFormat::TAX_CATEGORY_MODE_ATTRIBUTE
                        && $formData['tax_category_attribute'] == $attribute['code']
                    ) {
                        $attrs['selected'] = 'selected';
                    }
                    $preparedAttributes[] = [
                        'attrs' => $attrs,
                        'value' => SellingFormat::TAX_CATEGORY_MODE_ATTRIBUTE,
                        'label' => $attribute['label'],
                    ];
                }

                $preparedValues[] = [
                    'label' => $this->__('Magento Attributes'),
                    'value' => $preparedAttributes,
                    'attrs' => ['is_magento_attribute' => true],
                ];
            }

            $fieldset->addField(
                'tax_category_mode',
                self::SELECT,
                [
                    'label' => $this->__('Tax Category'),
                    'values' => array_merge(
                        [SellingFormat::TAX_CATEGORY_MODE_NONE => $this->__('None')],
                        $preparedValues
                    ),
                    'value' => $formData['tax_category_mode'] != SellingFormat::TAX_CATEGORY_MODE_VALUE
                    && $formData['tax_category_mode'] != SellingFormat::TAX_CATEGORY_MODE_ATTRIBUTE
                        ? $formData['tax_category_mode'] : '',
                    'create_magento_attribute' => true,
                ]
            )->addCustomAttribute('allowed_attribute_types', 'text');

            $fieldset->addField(
                'tax_category_value',
                'hidden',
                [
                    'name' => 'selling_format[tax_category_value]',
                    'value' => $formData['tax_category_value'],
                ]
            );

            $fieldset->addField(
                'tax_category_attribute',
                'hidden',
                [
                    'name' => 'selling_format[tax_category_attribute]',
                    'value' => $formData['tax_category_attribute'],
                ]
            );
        }

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_selling_format_edit_form_prices',
            [
                'legend' => $this->__('Price'),
                'collapsable' => true,
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['fixed_price_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                && $formData['fixed_price_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'fixed_price_mode',
            self::SELECT,
            [
                'container_id' => 'fixed_price_tr',
                'label' => __('Price'),
                'class' => 'select-main',
                'name' => 'selling_format[fixed_price_mode]',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => __('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => __('Special Price'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['fixed_price_mode']
                != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    ? $formData['fixed_price_mode'] : '',
                'tooltip' => __('The Price for Fixed Price Items.'),
                'create_magento_attribute' => true,
            ]
        );

        $fieldset->addField(
            'fixed_price_custom_attribute',
            'hidden',
            [
                'name' => 'selling_format[fixed_price_custom_attribute]',
                'value' => $formData['fixed_price_custom_attribute'],
            ]
        );
        $this->addPriceRoundingField($fieldset, \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_TYPE_FIXED, $formData['fixed_price_rounding_option']);

        $this->appendPriceChangeElements(
            $fieldset,
            \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_TYPE_FIXED,
            \Ess\M2ePro\Helper\Json::encode($formData['fixed_price_modifier'])
        );

        $fieldset->addField(
            'price_variation_mode',
            self::SELECT,
            [
                'container_id' => 'variation_price_tr',
                'label' => __('Variation Price'),
                'class' => 'select-main',
                'name' => 'selling_format[price_variation_mode]',
                'values' => [
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_VARIATION_MODE_PARENT => __('Main Product'),
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_VARIATION_MODE_CHILDREN => __('Associated Products'),
                ],
                'value' => $formData['price_variation_mode'],
                'tooltip' => __('Choose the source of the price value for Bundle Products variations.'),
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['start_price_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                && $formData['start_price_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'start_price_mode',
            self::SELECT,
            [
                'container_id' => 'start_price_tr',
                'label' => __('Start Price'),
                'class' => 'select-main',
                'name' => 'selling_format[start_price_mode]',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => __('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => __('Special Price'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['start_price_mode']
                != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    ? $formData['start_price_mode'] : '',
                'tooltip' => __('The starting Price at which bidding begins.'),
                'create_magento_attribute' => true,
            ]
        );

        $fieldset->addField(
            'start_price_custom_attribute',
            'hidden',
            [
                'name' => 'selling_format[start_price_custom_attribute]',
                'value' => $formData['start_price_custom_attribute'],
            ]
        );

        $this->addPriceCoefField($fieldset, $formData['start_price_coefficient'], \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_TYPE_START, 'Start Price Coefficient');
        $this->addPriceRoundingField($fieldset, \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_TYPE_START, $formData['start_price_rounding_option']);

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['reserve_price_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                && $formData['reserve_price_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'reserve_price_mode',
            self::SELECT,
            [
                'container_id' => 'reserve_price_tr',
                'label' => __('Reserve Price'),
                'class' => 'select-main',
                'name' => 'selling_format[reserve_price_mode]',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE => __('None'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => __('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => __('Special Price'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['reserve_price_mode']
                != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    ? $formData['reserve_price_mode'] : '',
                'tooltip' => __('The lowest Price at which you are selling an Item.<br/><b>Note:</b> eBay charges some additional fee for using this Option.'),
                'create_magento_attribute' => true,
            ]
        );
        $fieldset->addField(
            'reserve_price_custom_attribute',
            'hidden',
            [
                'name' => 'selling_format[reserve_price_custom_attribute]',
                'value' => $formData['reserve_price_custom_attribute'],
            ]
        );

        $this->addPriceCoefField($fieldset, $formData['reserve_price_coefficient'], \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_TYPE_RESERVE, 'Reserve Price Coefficient');
        $this->addPriceRoundingField($fieldset, \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_TYPE_RESERVE, $formData['reserve_price_rounding_option']);

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['buyitnow_price_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                && $formData['buyitnow_price_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'buyitnow_price_mode',
            self::SELECT,
            [
                'container_id' => 'buyitnow_price_tr',
                'label' => __('"Buy It Now" Price'),
                'class' => 'select-main',
                'name' => 'selling_format[buyitnow_price_mode]',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE => __('None'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => __('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => __('Special Price'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['buyitnow_price_mode']
                != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    ? $formData['buyitnow_price_mode'] : '',
                'tooltip' => __('The Fixed Price for immediate purchase.<br/>Find out more about <a href="https://www.ebay.com/help/selling/listings/selling-buy-now?id=4109#section2" target="_blank">adding a Buy It Now Price</a> to your Listing.'),
                'create_magento_attribute' => true,
            ]
        );

        $fieldset->addField(
            'buyitnow_price_custom_attribute',
            'hidden',
            [
                'name' => 'selling_format[buyitnow_price_custom_attribute]',
                'value' => $formData['buyitnow_price_custom_attribute'],
            ]
        );
        $this->addPriceCoefField($fieldset, $formData['buyitnow_price_coefficient'], \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_TYPE_BUYITNOW, '"Buy It Now" Price Coefficient');
        $this->addPriceRoundingField($fieldset, \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_TYPE_BUYITNOW, $formData['buyitnow_price_rounding_option']);

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['price_discount_stp_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                && $formData['price_discount_stp_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'price_discount_stp_mode',
            self::SELECT,
            [
                'container_id' => 'price_discount_stp_tr',
                'label' => __('Strike-Through Price'),
                'class' => 'select-main',
                'name' => 'selling_format[price_discount_stp_mode]',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE => __('None'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => __('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => __('Special Price'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['price_discount_stp_mode']
                != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    ? $formData['price_discount_stp_mode'] : '',
                'tooltip' => __('The Strike-Through Price is the original Price of a Product that is discounted shown with a line through it.
                                    It is only available to certain Sellers who have been pre-approved by eBay.<br/><br/>
                                    If you qualify to display Strike-Through Price choose the Magento Attribute you want to use for it.'),
                'create_magento_attribute' => true,
            ]
        );

        $fieldset->addField(
            'price_discount_stp_attribute',
            'hidden',
            [
                'name' => 'selling_format[price_discount_stp_attribute]',
                'value' => $formData['price_discount_stp_attribute'],
            ]
        );

        $fieldset->addField(
            'price_discount_stp_type',
            self::SELECT,
            [
                'container_id' => 'price_discount_stp_reason_tr',
                'label' => __('Reason (UK, DE only)'),
                'class' => 'select-main',
                'name' => 'selling_format[price_discount_stp_type]',
                'values' => [
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_STP_TYPE_RRP => __('Recommended Retail Price'),
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_STP_TYPE_SOLD_ON_EBAY => __('Previous Selling Price used on eBay'),
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_STP_TYPE_SOLD_OFF_EBAY => __('Previous Selling Price used beyond eBay'),
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_STP_TYPE_SOLD_ON_BOTH => __('Previous Selling Price used both'),
                ],
                'value' => $formData['price_discount_stp_type'],
                'tooltip' => __('<strong>Recommended Retail Price</strong><br/>
                This Price is recommended by the manufacturer to be used at the initial sale.<br/>
                <strong>Previous Selling Price used on eBay</strong><br/>
                This Price was used in another Listing on eBay 30 days prior to Listing the Item, excluding shipping and handling.<br/>
                <strong>Previous Selling Price used beyond eBay</strong><br/>
                This Price was used beyond eBay either online or offline 30 days prior to Listing the Item, excluding shipping and handling.<br/>
                <strong>Previous Selling Price used both on and beyond eBay</strong><br/>
                    This Price was used both on and beyond eBay 30 days prior to Listing the Item, excluding shipping and handling.'),
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['price_discount_map_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                && $formData['price_discount_map_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'price_discount_map_mode',
            self::SELECT,
            [
                'container_id' => 'price_discount_map_tr',
                'label' => __('Minimum Advertised Price'),
                'class' => 'select-main',
                'name' => 'selling_format[price_discount_map_mode]',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE => __('None'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => __('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => __('Special Price'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['price_discount_map_mode']
                != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    ? $formData['price_discount_map_mode'] : '',
                'tooltip' => __('This determines where the Minimum Advertised Price should be taken from.'),
                'create_magento_attribute' => true,
            ]
        );

        $fieldset->addField(
            'price_discount_map_attribute',
            'hidden',
            [
                'name' => 'selling_format[price_discount_map_attribute]',
                'value' => $formData['price_discount_map_attribute'],
            ]
        );

        $fieldset->addField(
            'price_discount_map_exposure_type',
            self::SELECT,
            [
                'container_id' => 'price_discount_map_exposure_tr',
                'label' => __('Exposure'),
                'class' => 'select-main',
                'name' => 'selling_format[price_discount_map_exposure_type]',
                'values' => [
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_MAP_EXPOSURE_NONE => __('None'),
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_MAP_EXPOSURE_DURING_CHECKOUT => __('During Checkout'),
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_MAP_EXPOSURE_PRE_CHECKOUT => __('Pre-Checkout'),
                ],
                'value' => $formData['price_discount_map_exposure_type'],
                'tooltip' => __('<strong>None</strong><br/>
                                    Discounted Price will not be shown during either Pre-Checkout or Checkout. A Buyer will be aware of discount before the payment is done.<br/>
                                    <strong>During Checkout</strong><br/>
                                    Discounted Price will be shown on the eBay Checkout flow Page. A Buyer will be aware of discount before the purchase is confirmed.<br/>
                                    <strong>PreCheckout</strong><br/>
                                    The information of discounted Price will be available on the Product Page. A Buyer will be aware of discount before he decides to make a purchase by clicking "See Price" link. Discounted Price will be shown in a pop-up window.'),
            ]
        );

        $currencyAvailabilityMessage = $this->getCurrencyAvailabilityMessage();
        $fieldset->addField(
            'template_selling_format_messages',
            self::CUSTOM_CONTAINER,
            [
                'text' => $currencyAvailabilityMessage,
                'css_class' => 'm2epro-fieldset-table no-margin-bottom',
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_payment_form_data_paypal',
            [
                'legend' => $this->__('Payments'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'paypal_immediate_payment',
            self::SELECT,
            [
                'name' => 'selling_format[paypal_immediate_payment]',
                'label' => $this->__('Require Immediate Payment'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value'   => $formData['paypal_immediate_payment'],
                'after_element_html' => '<label for="pay_pal_mode"></label>' . $this->getTooltipHtml($this->__(
                    'Select this option if you want the buyer to pay immediately. Your item will remain
                            available for others to buy until the payment is complete.
Since the buyer must pay immediately, they wont be able to contact you with Request total price from seller.
This means your listing must include the shipping costs to all locations where you do ship, and discount rules if you
offer discounts.'
                ), true)
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_selling_format_edit_form_charity',
            [
                'legend' => $this->__('Donations'),
                'collapsable' => true,
            ]
        );

        $charityBlock = $this->getLayout()
                             ->createBlock(
                                 \Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form\Charity::class
                             )
                             ->addData([
                                 'form_data' => $formData,
                                 'marketplace' => $this->getMarketplace(),
                             ]);

        $fieldset->addField(
            'charity_table_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $charityBlock->toHtml(),
                'css_class' => 'm2epro-fieldset-table',
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_selling_format_edit_form_best_offer',
            [
                'legend' => $this->__('Best Offer'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'template_selling_format_messages_best_offer',
            self::CUSTOM_CONTAINER,
            [
                'css_class' => 'm2epro-fieldset-table no-margin-bottom',
            ]
        );

        $fieldset->addField(
            'best_offer_mode',
            self::SELECT,
            [
                'label' => $this->__('Allow Best Offer'),
                'name' => 'selling_format[best_offer_mode]',
                'values' => [
                    SellingFormat::BEST_OFFER_MODE_YES => $this->__('Yes'),
                    SellingFormat::BEST_OFFER_MODE_NO => $this->__('No'),
                ],
                'value' => $formData['best_offer_mode'],
                'tooltip' => $this->__(
                    'The Best Offer Option allows you to accept offers from Buyers and negotiate a Price.
                    You can accept Best Offers on fixed Price and Classified Ads in certain Categories,
                    such as eBay Motors.'
                ),
            ]
        );

        $bestOfferAcceptValue = $this->elementFactory->create('text', [
            'data' => [
                'html_id' => 'best_offer_accept_value',
                'name' => 'selling_format[best_offer_accept_value]',
                'value' => $formData['best_offer_accept_value'],
                'class' => 'coef validate-digits M2ePro-required-when-visible',
                'after_element_html' => '%',
            ],
        ]);
        $bestOfferAcceptValue->setForm($form);

        $preparedAttributes = [];

        if (
            $formData['best_offer_accept_mode'] == SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE &&
            !$this->magentoAttributeHelper
                ->isExistInAttributesArray($formData['best_offer_accept_attribute'], $attributes) &&
            $formData['best_offer_accept_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['best_offer_accept_attribute'],
                    'selected' => 'selected',
                ],
                'value' => SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper
                    ->getAttributeLabel($formData['best_offer_accept_attribute']),
            ];
        }

        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['best_offer_accept_mode'] == SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE
                && $formData['best_offer_accept_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'best_offer_accept_mode',
            self::SELECT,
            [
                'css_class' => 'best_offer_respond_table_container',
                'label' => $this->__('Accept Offers of at Least'),
                'name' => 'selling_format[best_offer_accept_mode]',
                'values' => [
                    SellingFormat::BEST_OFFER_ACCEPT_MODE_NO => $this->__('No'),
                    [
                        'label' => '#',
                        'value' => SellingFormat::BEST_OFFER_ACCEPT_MODE_PERCENTAGE,
                        'attrs' => [
                            'id' => 'best_offer_accept_percentage_option',
                        ],
                    ],
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['best_offer_accept_mode'] != SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE
                    ? $formData['best_offer_accept_mode'] : '',
                'create_magento_attribute' => true,
                'note' => $this->getCurrency() !== null ?
                    $this->__('Currency') . ': ' . $this->getCurrency() : '',
                'after_element_html' => '<span id="best_offer_accept_value_tr">'
                    . $bestOfferAcceptValue->toHtml() . '</span>',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField(
            'best_offer_accept_custom_attribute',
            'hidden',
            [
                'name' => 'selling_format[best_offer_accept_attribute]',
            ]
        );

        //-----------

        $bestOfferRejectValue = $this->elementFactory->create('text', [
            'data' => [
                'html_id' => 'best_offer_reject_value',
                'name' => 'selling_format[best_offer_reject_value]',
                'value' => $formData['best_offer_reject_value'],
                'class' => 'coef validate-digits M2ePro-required-when-visible',
                'after_element_html' => '%',
            ],
        ]);
        $bestOfferRejectValue->setForm($form);

        $preparedAttributes = [];

        if (
            $formData['best_offer_reject_mode'] == SellingFormat::BEST_OFFER_REJECT_MODE_ATTRIBUTE &&
            !$this->magentoAttributeHelper
                ->isExistInAttributesArray($formData['best_offer_reject_attribute'], $attributes) &&
            $formData['best_offer_reject_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['best_offer_reject_attribute'],
                    'selected' => 'selected',
                ],
                'value' => SellingFormat::BEST_OFFER_REJECT_MODE_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper
                    ->getAttributeLabel($formData['best_offer_reject_attribute']),
            ];
        }

        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['best_offer_reject_mode'] == SellingFormat::BEST_OFFER_REJECT_MODE_ATTRIBUTE
                && $formData['best_offer_reject_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::BEST_OFFER_REJECT_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'best_offer_reject_mode',
            self::SELECT,
            [
                'css_class' => 'best_offer_respond_table_container',
                'label' => $this->__('Decline Offers Less than'),
                'name' => 'selling_format[best_offer_reject_mode]',
                'values' => [
                    SellingFormat::BEST_OFFER_REJECT_MODE_NO => $this->__('No'),
                    [
                        'label' => '#',
                        'value' => SellingFormat::BEST_OFFER_REJECT_MODE_PERCENTAGE,
                        'attrs' => [
                            'id' => 'best_offer_reject_percentage_option',
                        ],
                    ],
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['best_offer_reject_mode'] != SellingFormat::BEST_OFFER_REJECT_MODE_ATTRIBUTE
                    ? $formData['best_offer_reject_mode'] : '',
                'create_magento_attribute' => true,
                'note' => $this->getCurrency() !== null ?
                    $this->__('Currency') . ': ' . $this->getCurrency() : '',
                'after_element_html' => '<span id="best_offer_reject_value_tr">'
                    . $bestOfferRejectValue->toHtml() . '</span>',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField(
            'best_offer_reject_custom_attribute',
            'hidden',
            [
                'name' => 'selling_format[best_offer_reject_attribute]',
            ]
        );

        $this->setForm($form);

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Template\SellingFormat::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Template\SellingFormat::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Template\Manager::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Ebay::class)
        );

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Ebay_Template_SellingFormat'));

        $this->jsTranslator->addTranslations([
            'Search For Charities' => $this->__('Search For Charities'),
            'Please select a percentage of donation' => $this->__(
                'Please select a percentage of donation'
            ),
            'If you do not see the organization you were looking for, ' .
            'try to enter another keywords and run the Search again.' =>
                $this->__(
                    'If you do not see the organization you were looking for,
                try to enter another keywords and run the Search again.'
                ),
            'Please, enter the organization name or ID.' => $this->__(
                'Please, enter the organization name or ID.'
            ),
            'wrong_value_more_than_30' => $this->__(
                'Wrong value. Must be no more than 30. Max applicable length is 6 characters,
                 including the decimal (e.g., 12.345).'
            ),

            'Price Change is not valid.' => $this->__('Price Change is not valid.'),
            'Wrong value. Only integer numbers.' => $this->__('Wrong value. Only integer numbers.'),

            'Price' => $this->__('Price'),
            'Fixed Price' => $this->__('Fixed Price'),

            'The Price for Fixed Price Items.' => $this->__(
                'The Price for Fixed Price Items.'
            ),
            'The Fixed Price for immediate purchase.<br/>Find out more about
             <a href="https://www.ebay.com/help/selling/listings/selling-buy-now?id=4109#section2"
                target="_blank">adding a Buy It Now Price</a> to your Listing.' =>
                $this->__(
                    'The Fixed Price for immediate purchase.<br/>Find out more about
                 <a href="https://www.ebay.com/help/selling/listings/selling-buy-now?id=4109#section2"
                    target="_blank">adding a Buy It Now Price</a> to your Listing.'
                ),

            '% of Price' => $this->__('% of Price'),
            '% of Fixed Price' => $this->__('% of Fixed Price'),
            'Search for Charity Organization' => $this->__('Search for Charity Organization'),
            'Wrong value. Lot Size must be from 2 to 100000 Items.' => $this->__(
                'Wrong value. Lot Size must be from 2 to 100000 Items.'
            ),
        ]);

        $this->js->add("M2ePro.formData.isStpEnabled = Boolean({$this->isStpAvailable()});");
        $this->js->add("M2ePro.formData.isStpAdvancedEnabled = Boolean({$this->isStpAdvancedAvailable()});");
        $this->js->add("M2ePro.formData.isMapEnabled = Boolean({$this->isMapAvailable()});");

        $this->js->add(
            "M2ePro.formData.duration_mode
            = {$this->dataHelper->escapeJs($formData['duration_mode'])};"
        );
        $this->js->add("M2ePro.formData.qty_mode = {$this->dataHelper->escapeJs($formData['qty_mode'])};");
        $this->js->add(
            "M2ePro.formData.qty_modification_mode
            = {$this->dataHelper->escapeJs($formData['qty_modification_mode'])};"
        );

        $currency = $this->getCurrency();

        if ($currency !== null) {
            $this->js->add("M2ePro.formData.currency = '{$this->currency->getCurrency($currency)->getSymbol()}';");
        }

        $charityDictionary = \Ess\M2ePro\Helper\Json::encode($charityBlock->getCharityDictionary());
        if (empty($formData['charity'])) {
            $charityRenderJs = <<<JS
    EbayTemplateSellingFormatObj.charityDictionary = {$charityDictionary};
JS;
        } else {
            $formDataJson = \Ess\M2ePro\Helper\Json::encode($formData['charity']);
            $charityRenderJs = <<<JS
    EbayTemplateSellingFormatObj.charityDictionary = {$charityDictionary};
    EbayTemplateSellingFormatObj.renderCharities({$formDataJson});
JS;
        }

        $fixedPriceModifierRenderJs = '';
        $formDataJson = \Ess\M2ePro\Helper\Json::encode($formData['fixed_price_modifier']);
        $fixedPriceModifierRenderJs = <<<JS
    EbayTemplateSellingFormatObj.priceChangeHelper.renderPriceChangeRows(
        'fixed_price',
        {$formDataJson}
        );
JS;

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Ebay/Template/SellingFormat',
    ], function(){
        window.EbayTemplateSellingFormatObj = new EbayTemplateSellingFormat();
        EbayTemplateSellingFormatObj.initObservers();

        {$charityRenderJs}
        {$fixedPriceModifierRenderJs}
    });
JS
        );

        return parent::_prepareForm();
    }

    private function getTitle()
    {
        if ($this->isCustom()) {
            return isset($this->_data['custom_title']) ? $this->_data['custom_title'] : '';
        }

        $template = $this->globalDataHelper->getValue('ebay_template_selling_format');

        if ($template === null) {
            return '';
        }

        return $template->getTitle();
    }

    private function isCustom()
    {
        if (isset($this->_data['is_custom'])) {
            return (bool)$this->_data['is_custom'];
        }

        return false;
    }

    private function getFormData()
    {
        $template = $this->globalDataHelper->getValue('ebay_template_selling_format');

        if ($template === null || $template->getId() === null) {
            return [];
        }

        $data = array_merge($template->getData(), $template->getChildObject()->getData());

        return $data;
    }

    private function getAttributesByInputTypes()
    {
        $attributes = $this->globalDataHelper->getValue('ebay_attributes');

        return [
            'text' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text']),
            'text_select' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text', 'select']),
            'text_price' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text', 'price']),
        ];
    }

    private function getDefault()
    {
        return $this->modelFactory->getObject('Ebay_Template_SellingFormat_Builder')->getDefaultData();
    }

    private function getCurrency()
    {
        $marketplace = $this->globalDataHelper->getValue('ebay_marketplace');

        if ($marketplace === null) {
            return null;
        }

        return $marketplace->getChildObject()->getCurrency();
    }

    public function getCurrencyAvailabilityMessage()
    {
        $marketplace = $this->globalDataHelper->getValue('ebay_marketplace');
        $store = $this->globalDataHelper->getValue('ebay_store');
        /** @var \Ess\M2ePro\Model\Ebay\Template\SellingFormat $template */
        $template = $this->globalDataHelper->getValue('ebay_template_selling_format');

        if ($template === null || $template->getId() === null) {
            $templateData = $this->getDefault();
            $templateData['component_mode'] = \Ess\M2ePro\Helper\Component\Ebay::NICK;
        } else {
            $templateData = $template->getData();
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Template\SellingFormat\Messages $messagesBlock */
        $messagesBlock = $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Template\SellingFormat\Messages::class);
        $messagesBlock->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $messagesBlock->setTemplateNick(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT);

        $messagesBlock->setData('template_data', $templateData);
        $messagesBlock->setData('marketplace_id', $marketplace ? $marketplace->getId() : null);
        $messagesBlock->setData('store_id', $store ? $store->getId() : null);

        $messages = $messagesBlock->getMessages();
        if (empty($messages)) {
            return '';
        }

        return $messagesBlock->getMessagesHtml($messages);
    }

    /**
     * @return  \Ess\M2ePro\Model\Marketplace|null
     **/
    public function getMarketplace()
    {
        return $this->globalDataHelper->getValue('ebay_marketplace');
    }

    public function getMarketplaceId()
    {
        $marketplace = $this->getMarketplace();

        if ($marketplace === null) {
            return null;
        }

        return $marketplace->getId();
    }

    public function isStpAvailable()
    {
        $marketplace = $this->getMarketplace();

        if ($marketplace === null) {
            return true;
        }

        if ($marketplace->getChildObject()->isStpEnabled()) {
            return true;
        }

        return false;
    }

    public function isStpAdvancedAvailable()
    {
        $marketplace = $this->getMarketplace();

        if ($marketplace === null) {
            return true;
        }

        if ($marketplace->getChildObject()->isStpAdvancedEnabled()) {
            return true;
        }

        return false;
    }

    public function isMapAvailable()
    {
        $marketplace = $this->getMarketplace();

        if ($marketplace === null) {
            return true;
        }

        if ($marketplace->getChildObject()->isMapEnabled()) {
            return true;
        }

        return false;
    }

    //########################################

    public function getTaxCategoriesInfo()
    {
        $marketplacesCollection = $this->ebayFactory->getObject('Marketplace')
                                                    ->getCollection()
                                                    ->addFieldToFilter(
                                                        'status',
                                                        \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE
                                                    )
                                                    ->setOrder('sorder', 'ASC');

        $marketplacesCollection->getSelect()->limit(1);

        $marketplaces = $marketplacesCollection->getItems();

        if (count($marketplaces) == 0) {
            return [];
        }

        return array_shift($marketplaces)->getChildObject()->getTaxCategoryInfo();
    }

    //########################################

    public function getCharityDictionary(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableDictMarketplace = $this->dbStructureHelper
            ->getTableNameWithPrefix('m2epro_ebay_dictionary_marketplace');

        $dbSelect = $connection->select()
                               ->from($tableDictMarketplace, ['marketplace_id', 'charities']);

        $data = $connection->fetchAssoc($dbSelect);

        foreach ($data as $key => $item) {
            $data[$key]['charities'] = \Ess\M2ePro\Helper\Json::decode($item['charities']);
        }

        return $data;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     * @param string $priceType
     * @param string|null $priceModifier
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function appendPriceChangeElements(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        string $priceType,
        ?string $priceModifier
    ): void {
        $block = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Template\SellingFormat\PriceChange::class)
                      ->addData([
                          'price_type' => $priceType,
                          'price_modifier' => (string)$priceModifier,
                      ]);

        $fieldset->addField(
            $priceType . '_change_placement',
            'label',
            [
                'container_id' => $priceType . '_change_placement_tr',
                'label' => '',
                'after_element_html' => $block->toHtml(),
            ]
        );
    }

    public function addPriceCoefField($fieldset, $formData, $priceType, $label)
    {
        $fieldset->addField(
            $priceType . '_coefficient_mode',
            self::SELECT,
            [
                'container_id' => $priceType . '_change_td',
                'label' => __($label),
                'class' => 'select admin__control-select M2ePro-validate-price-coefficient price_coefficient_mode required-entry',
                'name' => 'selling_format[' . $priceType . '_coefficient_mode]',
                'values' => [
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_NONE => __('None'),
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_INCREASE => __('Absolute Value increase'),
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_DECREASE => __('Absolute Value decrease'),
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_INCREASE => __('Percentage increase'),
                    \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_DECREASE => __('Percentage decrease'),
                ],
                'value' => $formData,
                'after_element_html' => '
<div id="' . $priceType . '_coefficient_input_div" class="price_coefficient_container">
    <div style="width: 10px;">
        <span id="' . $priceType . '_coefficient_sign_span"></span>
    </div>
    <input name="selling_format[' . $priceType . '_coefficient]" value="' . preg_replace('/(%$|^[+-])/', '', $formData) . '" type="text" class="admin__control-text M2ePro-validation-float input-text coef"/>
    <span id="' . $priceType . '_coefficient_percent_span" style="padding-left: 2px;"></span>
</div>
<script type="text/javascript">
            require(["jquery"], function ($) {
                $(document).ready(function () {
                    var priceCoefficient = ' . json_encode($formData) . ';
                    if (priceCoefficient == "") {
                        $("#' . $priceType . '_coefficient_mode").val(' . json_encode(\Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_NONE) . ');
                    } else if (priceCoefficient.match(/^\+[0-9.,]*$/)) {
                        $("#' . $priceType . '_coefficient_mode").val(' . json_encode(\Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_INCREASE) . ');
                    } else if (priceCoefficient.match(/^\-[0-9.,]*$/)) {
                        $("#' . $priceType . '_coefficient_mode").val(' . json_encode(\Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_DECREASE) . ');
                    } else if (priceCoefficient.match(/^\+[0-9.,]*%$/)) {
                        $("#' . $priceType . '_coefficient_mode").val(' . json_encode(\Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_INCREASE) . ');
                    } else if (priceCoefficient.match(/^\-[0-9.,]*%$/)) {
                        $("#' . $priceType . '_coefficient_mode").val(' . json_encode(\Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_DECREASE) . ');
                    }
                });
            });
        </script>'
            ]
        );
    }

    public function addPriceRoundingField($fieldset, $priceType, $formData)
    {
        $fieldset->addField(
            $priceType . '_rounding_option',
            'select',
            [
                'container_id' => $priceType . '_rounding_option_container',
                'name' => 'selling_format[' . $priceType . '_rounding_option]',
                'label' => __('Rounding'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing\Product\PriceRounder::PRICE_ROUNDING_NONE, 'label' => __('None')],
                    ['value' => \Ess\M2ePro\Model\Listing\Product\PriceRounder::PRICE_ROUNDING_NEAREST_HUNDREDTH, 'label' => __('Nearest 0.09')],
                    ['value' => \Ess\M2ePro\Model\Listing\Product\PriceRounder::PRICE_ROUNDING_NEAREST_TENTH, 'label' => __('Nearest 0.99')],
                    ['value' => \Ess\M2ePro\Model\Listing\Product\PriceRounder::PRICE_ROUNDING_NEAREST_INT, 'label' => __('Nearest 1.00')],
                    ['value' => \Ess\M2ePro\Model\Listing\Product\PriceRounder::PRICE_ROUNDING_NEAREST_HUNDRED, 'label' => __('Nearest 10.00')],
                ],
                'value' => $formData,
                'tooltip' => __('Use <b>Price Rounding</b> to round Product sale prices to convenient numbers like $9.99 or  $10.00<br>
If the <b>Price Change</b> is used, the <b>Rounding</b> will be applied to the final Price')
            ]
        );
    }
}
