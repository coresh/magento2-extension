<?php

namespace Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown;

class OptionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function create(
        string $label,
        string $value,
        string $optionGroupLabel = Option::DEFAULT_OPTION_GROUP_LABEL
    ): Option {
        return $this->objectManager->create(Option::class, [
            'label' => $label,
            'value' => $value,
            'optionGroupLabel' => $optionGroupLabel,
        ]);
    }
}
