<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\Shipping;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $accountId;
    private $productsIds;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonTemplateShippingGrid');

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility(true);
        $this->setDefaultSort('title');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    /**
     * @param int $accountId
     *
     * @return void
     */
    public function setAccountId(int $accountId): void
    {
        $this->accountId = $accountId;
    }

    /**
     * @return mixed
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @param array $productsIds
     *
     * @return void
     */
    public function setProductsIds(array $productsIds): void
    {
        $this->productsIds = $productsIds;
    }

    /**
     * @return mixed
     */
    public function getProductsIds()
    {
        return $this->productsIds;
    }

    protected function _prepareCollection()
    {
        $this->setNoTemplatesText();

        $collection = $this->activeRecordFactory->getObject('Amazon_Template_Shipping')->getCollection();
        $collection->addFieldToFilter('account_id', $this->accountId);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function _prepareColumns(): void
    {
        $this->addColumn('title', [
            'header' => $this->__('Title'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'filter_index' => 'title',
            'escape' => false,
            'sortable' => true,
            'frame_callback' => [$this, 'callbackColumnTitle'],
        ]);

        $this->addColumn('action', [
            'header' => $this->__('Action'),
            'align' => 'left',
            'type' => 'number',
            'index' => 'id',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnAction'],
        ]);
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\Shipping\Grid
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout(): self
    {
        $this->setChild(
            'refresh_button',
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                 ->setData([
                     'id' => 'shipping_template_refresh_btn',
                     'label' => $this->__('Refresh'),
                     'class' => 'action primary',
                     'onclick' => "ListingGridObj.templateShippingHandler.loadGrid()",
                 ])
        );

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getRefreshButtonHtml(): string
    {
        return $this->getChildHtml('refresh_button');
    }

    /**
     * @return string
     */
    public function getMainButtonsHtml(): string
    {
        return $this->getRefreshButtonHtml() . parent::getMainButtonsHtml();
    }

    public function callbackColumnTitle($value, $row, $column, $isExport): string
    {
        $templateEditUrl = $this->getUrl('*/amazon_template_shipping/edit', [
            'id' => $row->getData('id'),
            'close_on_save' => true,
        ]);

        $title = $this->dataHelper->escapeHtml($value);

        return <<<HTML
<a target="_blank" href="{$templateEditUrl}">{$title}</a>
HTML;
    }

    public function callbackColumnAction($value, $row, $column, $isExport)
    {
        $assignText = $this->__('Assign');

        return <<<HTML
<a href="javascript:void(0)"
    class="assign-shipping-template"
    templateShippingId="{$value}">
    {$assignText}
</a>
HTML;
    }

    /**
     * @return string
     */
    protected function _toHtml(): string
    {
        $this->js->add(
            <<<JS
ListingGridObj.templateShippingHandler.newTemplateUrl='{$this->getNewTemplateShippingUrl()}';
JS
        );

        return parent::_toHtml();
    }

    /**
     * @return string
     */
    public function getGridUrl(): string
    {
        return $this->getUrl('*/*/viewGrid', [
            '_current' => true,
            '_query' => [
                'account_id' => $this->getAccountId(),
            ],
            'products_ids' => implode(',', $this->getProductsIds()),
        ]);
    }

    /**
     * @param $row
     *
     * @return bool
     */
    public function getRowUrl($row): bool
    {
        return false;
    }

    /**
     * @return void
     */
    protected function setNoTemplatesText(): void
    {
        $messageTxt = $this->__('Shipping Policies are not found.');
        $linkTitle = $this->__('Create New Shipping Policy.');

        $message = <<<HTML
<p>{$messageTxt} <a href="javascript:void(0);"
    class="new-shipping-template">{$linkTitle}</a>
</p>
HTML;

        $this->setEmptyText($message);
    }

    /**
     * @return string
     */
    protected function getNewTemplateShippingUrl(): string
    {
        return $this->getUrl('*/amazon_template_shipping/new', [
            'close_on_save' => true,
        ]);
    }
}
