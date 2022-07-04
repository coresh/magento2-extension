<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperDataGlobalData = $helperDataGlobalData;
    }

    public function execute()
    {
        $listing = $this->amazonFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );
        $this->helperDataGlobalData->setValue('listing', $listing);

        $autoMode  = $this->getRequest()->getParam('auto_mode');
        empty($autoMode) && $autoMode = $listing->getAutoMode();

        $autoModes = [
            \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL => 'Amazon_Listing_AutoAction_Mode_GlobalMode',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE => 'Amazon_Listing_AutoAction_Mode_Website',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY => 'Amazon_Listing_AutoAction_Mode_Category',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE => 'Amazon_Listing_AutoAction_Mode'
        ];

        if (isset($autoModes[$autoMode])) {
            $blockName = $autoModes[$autoMode];
        } else {
            $blockName = $autoModes[\Ess\M2ePro\Model\Listing::AUTO_MODE_NONE];
        }

        $this->setJsonContent([
            'mode' => $autoMode,
            'html' => $this->createBlock($blockName)->toHtml()
        ]);
        return $this->getResult();
    }
}
