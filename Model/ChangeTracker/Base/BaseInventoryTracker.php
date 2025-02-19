<?php

namespace Ess\M2ePro\Model\ChangeTracker\Base;

use Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;

abstract class BaseInventoryTracker implements TrackerInterface
{
    /** @var string */
    private $channel;
    /** @var SelectQueryBuilder */
    protected $queryBuilder;
    /** @var InventoryStock */
    protected $inventoryStock;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger */
    protected $logger;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder */
    private $attributesQueryBuilder;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\BlockingErrorConfig */
    private $blockingErrorConfig;

    public function __construct(
        string $channel,
        QueryBuilderFactory $queryBuilderFactory,
        InventoryStock $inventoryStock,
        ProductAttributesQueryBuilder $attributesQueryBuilder,
        TrackerLogger $logger,
        \Ess\M2ePro\Helper\Component\Ebay\BlockingErrorConfig $blockingErrorConfig
    ) {
        $this->channel = $channel;
        $this->inventoryStock = $inventoryStock;
        $this->queryBuilder = $queryBuilderFactory->make();
        $this->logger = $logger;
        $this->attributesQueryBuilder = $attributesQueryBuilder;
        $this->blockingErrorConfig = $blockingErrorConfig;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return TrackerInterface::TYPE_INVENTORY;
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @return \Magento\Framework\DB\Select
     */
    public function getDataQuery(): \Magento\Framework\DB\Select
    {
        $query = $this->getSelectQuery();

        $mainQuery = $this->queryBuilder
            ->makeSubQuery()
            ->distinct()
            ->addSelect('listing_product_id', 'base.listing_product_id')
            ->addSelect('additional_data', $this->makeAdditionalDataSelectQuery())
            ->from('base', $query);

        /* List condition */
        $isMeetList = '
            status = 0 AND (
                base.calculated_qty >= base.list_with_qty_greater_or_equal_then
                AND IF (base.list_only_enabled_products, base.product_disabled = FALSE, TRUE)
                AND IF (base.list_only_in_stock_products, base.is_in_stock, TRUE)
            )
        ';
        $mainQuery->orWhere($isMeetList);

        /* Revise condition */
        $isMeetRevise = '
            base.status = 2 AND (
                (base.calculated_qty > base.online_qty AND base.online_qty < base.revise_threshold)
                OR (base.calculated_qty != base.online_qty AND base.calculated_qty < base.revise_threshold)
            )
        ';
        $mainQuery->orWhere($isMeetRevise);

        /* Relist condition */
        $isMeetRelist = '
            base.status IN (1, 3, 4, 5) AND (
                (base.stock_qty >= base.relist_when_qty_more_or_equal)
                AND IF (base.relist_when_product_is_in_stock, base.is_in_stock = TRUE, TRUE)
                AND IF (base.relist_when_product_status_enabled, base.product_disabled = FALSE, TRUE)
            )
        ';
        $mainQuery->orWhere($isMeetRelist);

        /* Stop condition */
        $isMeetStop = '
            base.status = 2 AND (
                (base.stop_when_qty_less_than >= base.stock_qty)
                AND IF(base.stop_when_product_out_of_stock, base.is_in_stock = 0, TRUE)
                AND IF(base.stop_when_product_disabled, base.product_disabled = TRUE, TRUE)
            )
        ';
        $mainQuery->orWhere($isMeetStop);

        $message = sprintf(
            'Data query %s %s',
            $this->getType(),
            $this->getChannel()
        );
        $this->logger->debug($message, [
            'query' => (string)$mainQuery->getQuery(),
            'type' => $this->getType(),
            'channel' => $this->getChannel(),
        ]);

        return $mainQuery->getQuery();
    }

    /**
     * Base product sub query.
     * Includes all necessary information regarding the listing product
     * @return SelectQueryBuilder
     */
    protected function productSubQuery(): SelectQueryBuilder
    {
        $query = $this->queryBuilder->makeSubQuery();
        $query->distinct();

        $listingProductIdExpression = 'lp.id';
        $query->addSelect('listing_product_id', $listingProductIdExpression);

        $productIdExpression = 'IFNULL(lpvo.product_id, lp.product_id)';
        $query->addSelect('product_id', $productIdExpression);

        $query
            ->addSelect('status', 'lp.status')
            ->addSelect('store_id', 'l.store_id')
            ->addSelect('sync_template_id', 'c_l.template_synchronization_id')
            ->addSelect('selling_template_id', 'c_l.template_selling_format_id')
            ->addSelect('is_variation', 'IFNULL(c_lp.is_variation_parent, 0)');

        $query->addSelect(
            'product_enabled',
            $this->attributesQueryBuilder->getQueryForAttribute(
                'status',
                'l.store_id',
                $productIdExpression
            )
        );

        $onlineQtyExpression = 'IFNULL(c_lp.online_qty, 0)';
        $query->addSelect('online_qty', $onlineQtyExpression);

        $query
            ->from(
                'c_lp',
                $this->setChannelToTableName('m2epro_%s_listing_product')
            )
            ->innerJoin(
                'lp',
                'm2epro_listing_product',
                'lp.id = c_lp.listing_product_id'
            )
            ->innerJoin(
                'l',
                'm2epro_listing',
                'lp.listing_id = l.id'
            )
            ->innerJoin(
                'c_l',
                $this->setChannelToTableName('m2epro_%s_listing'),
                'c_l.listing_id = lp.listing_id'
            )
            ->leftJoin(
                'lpv',
                'm2epro_listing_product_variation',
                'lpv.listing_product_id = lp.id'
            )
            ->leftJoin(
                'lpvo',
                'm2epro_listing_product_variation_option',
                'lpvo.listing_product_variation_id = lpv.id'
            );

        /* We do not include grouped and bundle products in the selection */
        $query->andWhere("IFNULL(lpvo.product_type, 'simple') != ?", 'grouped');
        $query->andWhere("IFNULL(lpvo.product_type, 'simple') != ?", 'bundle');

        /* We do not include products marked duplicate in the sample */
        $query->andWhere("JSON_EXTRACT(lp.additional_data, '$.item_duplicate_action_required') IS NULL");

        $blockingErrorsRetryHours = $this->blockingErrorConfig->getEbayBlockingErrorRetrySeconds();
        $minRetryDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $minRetryDate->modify("-$blockingErrorsRetryHours seconds");

        /* Exclude products with a blocking error */
        $query->andWhere(
            "lp.last_blocking_error_date IS NULL OR ? >= lp.last_blocking_error_date",
            $minRetryDate->format('Y-m-d H:i:s')
        );

        return $query;
    }

    /**
     * @return SelectQueryBuilder
     */
    protected function sellingPolicySubQuery(): SelectQueryBuilder
    {
        return $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('template_selling_format_id', 'sp.template_selling_format_id')
            ->addSelect('mode', 'sp.qty_mode')
            ->addSelect('percentage', 'sp.qty_percentage')
            ->addSelect('custom_value', 'sp.qty_custom_value')
            ->addSelect('custom_attribute', 'sp.qty_custom_attribute')
            ->addSelect('custom_attribute_default_value', 'CAST(ea.default_value AS UNSIGNED)')
            ->addSelect('conditional_quantity', 'sp.qty_modification_mode')
            ->addSelect('min_qty', 'sp.qty_min_posted_value')
            ->addSelect('max_qty', 'sp.qty_max_posted_value')
            ->from(
                'sp',
                $this->setChannelToTableName('m2epro_%s_template_selling_format')
            )
            ->leftJoin(
                'ea',
                'eav_attribute',
                'ea.attribute_code = sp.qty_custom_attribute'
            );
    }

    /**
     * @return SelectQueryBuilder
     */
    protected function synchronizationPolicySubQuery(): SelectQueryBuilder
    {
        return $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('template_synchronization_id', 'ts.template_synchronization_id')
            ->addSelect(
                'revise_threshold',
                'IF(
                            ts.revise_update_qty_max_applied_value_mode = 1,
                            ts.revise_update_qty_max_applied_value,
                            999999
                        )'
            )
            ->addSelect(
                'list_only_enabled_products',
                'IF(ts.list_mode = 1 AND ts.list_status_enabled = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'list_only_in_stock_products',
                'IF(ts.list_mode = 1 AND ts.list_is_in_stock = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'list_with_qty_greater_or_equal_then',
                'IF(ts.list_mode = 1 AND ts.list_qty_calculated = 1, ts.list_qty_calculated_value, 999999)'
            )
            ->addSelect(
                'stop_when_product_disabled',
                'IF(ts.stop_mode = 1 AND ts.stop_status_disabled = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'stop_when_product_out_of_stock',
                'IF(ts.stop_mode = 1 AND ts.stop_out_off_stock = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'stop_when_qty_less_than',
                'IF(ts.stop_mode = 1 AND ts.stop_qty_calculated = 1, ts.stop_qty_calculated_value, -999999)'
            )
            ->addSelect(
                'relist_when_stopped_manually',
                'IF(ts.relist_mode = 1 AND ts.relist_filter_user_lock = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'relist_when_product_status_enabled',
                'IF(ts.relist_mode = 1 AND ts.relist_status_enabled = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'relist_when_product_is_in_stock',
                'IF(ts.relist_mode = 1 AND ts.relist_is_in_stock = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'relist_when_qty_more_or_equal',
                'IF(ts.relist_mode = 1 AND ts.relist_qty_calculated = 1, relist_qty_calculated_value, 999999)'
            )
            ->from(
                'ts',
                $this->setChannelToTableName('m2epro_%s_template_synchronization')
            );
    }

    /**
     * @inheridoc
     */
    protected function getSelectQuery(): SelectQueryBuilder
    {
        $query = $this->queryBuilder;

        /* Selects */
        $query
            ->addSelect('listing_product_id', 'product.listing_product_id')
            ->addSelect('product_id', 'product.product_id')
            ->addSelect('status', 'product.status')
            ->addSelect('product_disabled', new \Zend_Db_Expr('product.product_enabled = 2'))
            ->addSelect('store_id', 'product.store_id')
            ->addSelect('sync_template_id', 'product.sync_template_id')
            ->addSelect('selling_template_id', 'product.selling_template_id')
            ->addSelect('is_in_stock', 'stock.is_in_stock')
            ->addSelect('stock_qty', 'FLOOR(stock.qty)')
            ->addSelect('is_variation', 'product.is_variation')
            ->addSelect('revise_threshold', 'sync_policy.revise_threshold')
            ->addSelect('list_with_qty_greater_or_equal_then', 'sync_policy.list_with_qty_greater_or_equal_then')
            ->addSelect('list_only_enabled_products', 'sync_policy.list_only_enabled_products')
            ->addSelect('list_only_in_stock_products', 'sync_policy.list_only_in_stock_products')
            ->addSelect('stop_when_product_disabled', 'sync_policy.stop_when_product_disabled')
            ->addSelect('stop_when_product_out_of_stock', 'sync_policy.stop_when_product_out_of_stock')
            ->addSelect('stop_when_qty_less_than', 'sync_policy.stop_when_qty_less_than')
            ->addSelect('relist_when_stopped_manually', 'sync_policy.relist_when_stopped_manually')
            ->addSelect('relist_when_product_status_enabled', 'sync_policy.relist_when_product_status_enabled')
            ->addSelect('relist_when_product_is_in_stock', 'sync_policy.relist_when_product_is_in_stock')
            ->addSelect('relist_when_qty_more_or_equal', 'sync_policy.relist_when_qty_more_or_equal')
        ;

        $query->addSelect('online_qty', 'product.online_qty');
        $query->addSelect('calculated_qty', $this->calculatedQtyExpression());

        /* Tables */
        $query
            ->from('product', $this->productSubQuery())
            ->innerJoin(
                'stock',
                $this->inventoryStock->getInventoryStockTableName(),
                'product.product_id = stock.product_id'
            )
            ->leftJoin(
                'selling_policy',
                $this->sellingPolicySubQuery(),
                'selling_policy.template_selling_format_id = product.selling_template_id'
            )
            ->leftJoin(
                'sync_policy',
                $this->synchronizationPolicySubQuery(),
                'sync_policy.template_synchronization_id = product.sync_template_id'
            )
            ->leftJoin(
                'ea',
                'eav_attribute',
                'ea.attribute_code = selling_policy.custom_attribute'
            );

        $entityVarcharCondition = '
            cpev.attribute_id = ea.attribute_id
            AND cpev.entity_id = product.product_id
            AND cpev.store_id = product.store_id
        ';
        $query->leftJoin(
            'cpev',
            'catalog_product_entity_varchar',
            $entityVarcharCondition
        );

        $query
            ->leftJoin(
                'pl',
                'm2epro_processing_lock',
                'pl.object_id = product.listing_product_id'
            )
            ->leftJoin(
                'lpsa',
                'm2epro_listing_product_scheduled_action',
                'lpsa.listing_product_id = product.listing_product_id'
            )
            ->leftJoin(
                'lpi',
                'm2epro_listing_product_instruction',
                'lpi.listing_product_id = product.listing_product_id'
            );

        $query
            ->andWhere('pl.object_id IS NULL')
            ->andWhere('lpsa.listing_product_id IS NULL')
            ->andWhere('lpi.listing_product_id IS NULL');

        return $query;
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    protected function setChannelToTableName(string $tableName): string
    {
        return sprintf($tableName, $this->getChannel());
    }

    /**
     * @return string
     */
    protected function calculatedQtyExpression(): string
    {
        $calcQty = '
            (CASE
                WHEN selling_policy.mode IN (2, 3)
                    THEN selling_policy.custom_value
                WHEN selling_policy.mode IN (1, 5)
                    THEN stock.qty * selling_policy.percentage / 100
                WHEN selling_policy.mode = 4
                    THEN IF(
                        cpev.value IS NULL,
                        selling_policy.custom_attribute_default_value,
                        CAST(cpev.value AS DECIMAL(10, 0))
                    ) * selling_policy.percentage / 100
            END)
        ';

        return "FLOOR(CASE
                WHEN conditional_quantity = 1 AND $calcQty > selling_policy.max_qty
                    THEN selling_policy.max_qty
                ELSE $calcQty
            END
        )";
    }

    protected function getAdditionalDataFields(): array
    {
        return [
            'listing_product_id' => 'base.listing_product_id',
            'product_id' => 'base.product_id',
            'status' => 'base.status',

            'calculated_qty' => 'base.calculated_qty',
            'online_qty' => 'base.online_qty',

            'product_disabled' => 'base.product_disabled',
            'is_in_stock' => 'base.is_in_stock',

            'revise_threshold' => 'base.revise_threshold',

            'list_with_qty_greater_or_equal_then' => 'base.list_with_qty_greater_or_equal_then',
            'list_only_enabled_products' => 'base.list_only_enabled_products',
            'list_only_in_stock_products' => 'base.list_only_in_stock_products',

            'relist_when_qty_more_or_equal' => 'base.relist_when_qty_more_or_equal',
            'relist_when_product_is_in_stock' => 'base.relist_when_product_is_in_stock',
            'relist_when_product_status_enabled' => 'base.relist_when_product_status_enabled',

            'stop_when_product_disabled' => 'base.stop_when_product_disabled',
            'stop_when_product_out_of_stock' => 'base.stop_when_product_out_of_stock',
            'stop_when_qty_less_than' => 'base.stop_when_qty_less_than',
        ];
    }

    private function makeAdditionalDataSelectQuery(): \Zend_Db_Expr
    {
        $additionalDataSql = '';
        foreach ($this->getAdditionalDataFields() as $fieldName => $fieldValue) {
            $additionalDataSql .= "'$fieldName', $fieldValue, ";
        }
        $additionalDataSql = rtrim($additionalDataSql, ' ,');

        return new \Zend_Db_Expr("JSON_OBJECT($additionalDataSql)");
    }
}
