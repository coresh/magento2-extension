<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Item;

class Builder extends \Ess\M2ePro\Model\AbstractModel
{
    private $walmartFactory;

    /** @var bool */
    private $previousBuyerCancellationRequested;
    /** @var int */
    private $walmartOrderItemId;
    /** @var array */
    private $mergedWalmartOrderItemIds = [];

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    public function initialize(array $data)
    {
        // Init general data
        // ---------------------------------------
        $this->walmartOrderItemId = (int)$data['walmart_order_item_id'];
        $this->setData('walmart_order_item_id', $this->walmartOrderItemId);
        $this->setData('status', $data['status']);
        $this->setData('order_id', $data['order_id']);
        $this->setData('sku', trim($data['sku']));
        $this->setData('title', trim($data['title']));
        $this->setData('buyer_cancellation_requested', $data['buyer_cancellation_requested']);
        // ---------------------------------------

        // Init sale data
        // ---------------------------------------
        $this->setData('price', (float)$data['price']);
        $this->setData('qty_purchased', (int)$data['qty']);
        // ---------------------------------------

        // ----------------------------------------
        if (!empty($data['shipping_details']['tracking_details']['number'])) {
            $this->setData('tracking_details', \Ess\M2ePro\Helper\Json::encode([
                'number' => $data['shipping_details']['tracking_details']['number'],
                'title' => $data['shipping_details']['tracking_details']['carrier'],
            ]));
        }
        // ----------------------------------------

        /**
         * Walmart returns the same Order Item more than one time with single QTY. We will merge this data
         */
        // ---------------------------------------
        if (!empty($data['merged_walmart_order_item_ids'])) {
            $this->mergedWalmartOrderItemIds = $data['merged_walmart_order_item_ids'];
            $this->setData(
                'merged_walmart_order_item_ids',
                \Ess\M2ePro\Helper\Json::encode($data['merged_walmart_order_item_ids'])
            );
        }
        // ---------------------------------------
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\Item
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process()
    {
        /** @var \Ess\M2ePro\Model\Order\Item $existItem */
        $existItem = $this
            ->walmartFactory
            ->getObject('Order\Item')
            ->getCollection()
            ->addFieldToFilter('order_id', $this->getData('order_id'))
            ->addFieldToFilter('sku', $this->getData('sku'))
            ->addFieldToFilter('walmart_order_item_id', $this->getAllWalmartOrderItemIds())
            ->getFirstItem();

        $this->previousBuyerCancellationRequested = false;
        if ($existItem->getId()) {
            $this->previousBuyerCancellationRequested = $existItem->getChildObject()->isBuyerCancellationRequested();
        }

        foreach ($this->getData() as $key => $value) {
            if (!$existItem->getId() || ($existItem->hasData($key) && $existItem->getData($key) != $value)) {
                $existItem->addData($this->getData());
                $existItem->save();
                break;
            }
        }

        $walmartItem = $existItem->getChildObject();

        if (
            $existItem->getId() !== null
            && $walmartItem->getWalmartOrderItemId() !== $this->walmartOrderItemId
        ) {
            $this->setData('walmart_order_item_id', $walmartItem->getWalmartOrderItemId());
            $this->setData('merged_walmart_order_item_ids', $walmartItem->getData('merged_walmart_order_item_ids'));
        }

        foreach ($this->getData() as $key => $value) {
            if (!$existItem->getId() || ($walmartItem->hasData($key) && $walmartItem->getData($key) != $value)) {
                $walmartItem->addData($this->getData());
                $walmartItem->save();
                break;
            }
        }

        return $existItem;
    }

    /**
     * @return bool
     */
    public function getPreviousBuyerCancellationRequested(): bool
    {
        return $this->previousBuyerCancellationRequested;
    }

    private function getAllWalmartOrderItemIds(): array
    {
        if ($this->mergedWalmartOrderItemIds === []) {
            return [$this->walmartOrderItemId];
        }

        $allIds = $this->mergedWalmartOrderItemIds;
        $allIds[] = $this->walmartOrderItemId;

        return array_map('intval', $allIds);
    }
}
