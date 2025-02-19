<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    /**
     * @return \string[][]
     */
    public function getFeaturesList(): array
    {
        return [
            'dev'     => [
                'ChangeDevelopVersion',
                'ReinstallHookWithFix',
                'PrimaryConfigs',
                'ModuleConfigs',
                'AddAmazonCollects',
            ],
            'y19_m01' => [
                'NewUpgradesEngine',
                'AmazonOrdersUpdateDetails',
                'NewCronRunner',
            ],
            'y19_m04' => [
                'Walmart',
                'Maintenance',
                'WalmartAuthenticationForCA',
                'WalmartOptionImagesURL',
                'WalmartOrdersReceiveOn',
                'MigrationFromMagento1',
            ],
            'y19_m05' => [
                'WalmartAddMissingColumn',
            ],
            'y19_m07' => [
                'WalmartSynchAdvancedConditions',
            ],
            'y19_m10' => [
                'ConfigsNoticeRemoved',
                'RemoveAmazonShippingOverride',
                'NewSynchronization',
                'EnvironmentToConfigs',
                'CronTaskRemovedFromConfig',
                'EbayInStorePickup',
                'DropAutoMove',
                'Configs',
                'ProductVocabulary',
            ],
            'y19_m11' => [
                'AddEpidsAu',
                'RemoveListingOtherLog',
                'ProductsStatisticsImprovements',
                'WalmartProductIdOverride',
            ],
            'y19_m12' => [
                'RemoveReviseTotal',
                'RemoveEbayTranslation',
                'SynchDataFromM1',
                'RenameTableIndexerVariationParent',
                'WalmartReviseDescription',
                'EbayReturnPolicyM1',
            ],
            'y20_m01' => [
                'WebsitesActions',
                'FulfillmentCenter',
                'WalmartRemoveChannelUrl',
                'RemoveOutOfStockControl',
                'EbayLotSize',
                'EbayOrderUpdates',
            ],
            'y20_m02' => [
                'RepricingCount',
                'OrderNote',
                'ReviewPriorityCoefficients',
                'Configs',
            ],
            'y20_m03' => [
                'CronStrategy',
                'RemoveModePrefixFromChannelAccounts',
                'AmazonSendInvoice',
                'AmazonNL',
                'RemoveVersionsHistory',
                'EbayCategories',
            ],
            'y20_m04' => [
                'SaveEbayCategory',
                'BrowsenodeIdFix',
            ],
            'y20_m05' => [
                'DisableUploadInvoicesAvailableNl',
                'Logs',
                'RemoveMagentoQtyRules',
                'RemovePriceDeviationRules',
                'PrimaryConfigs',
                'CacheConfigs',
                'ModuleConfigs',
                'ConvertIntoInnoDB',
            ],
            'y20_m06' => [
                'WalmartConsumerId',
                'RemoveCronDomains',
                'GeneralConfig',
                'EbayConfig',
                'AmazonConfig',
                'RefundShippingCost',
            ],
            'y20_m07' => [
                'EbayTemplateStoreCategory',
                'HashLongtextFields',
                'EbayTemplateCustomTemplateId',
                'WalmartKeywordsFields',
                'WalmartOrderItemQty',
            ],
            'y20_m08' => [
                'EbayManagedPayments',
                'GroupedProduct',
                'AmazonSkipTax',
                'AmazonTR',
                'VCSLiteInvoices',
            ],
            'y20_m09' => [
                'AmazonSE',
                'SellOnAnotherSite',
                'InventorySynchronization',
            ],
            'y20_m10' => [
                'ChangeSingleItemOption',
                'AddInvoiceAndShipment',
                'SellOnAnotherSite',
                'AddShipmentToAmazonListing',
                'AddGermanyInStorePickUp',
                'AddITCAShippingRateTable',
                'DefaultValuesInSyncPolicy',
            ],
            'y20_m11' => [
                'WalmartCustomCarrier',
                'RemoteFulfillmentProgram',
                'EbayRemoveCustomTemplates',
                'SynchronizeInventoryConfigs',
                'DisableVCSOnNL',
                'AmazonDuplicatedMarketplaceFeature',
                'AddSkipEvtinSetting',
                'EbayOrderCancelRefund',
            ],
            'y21_m01' => [
                'AmazonJP',
                'WalmartCancelRefundOption',
                'EbayRemoveClickAndCollect',
            ],
            'y21_m02' => [
                'MoveAUtoAsiaPacific',
                'AmazonPL',
                'EbayManagedPayments',
            ],
            'y21_m03' => [
                'IncludeeBayProductDetails',
                'EbayMotorsAddManagedPayments',
            ],
            'y21_m04' => [
                'AmazonRelistPrice',
                'AddShipByDate',
            ],
            'y21_m05' => [
                'EbayStoreCategoryIDs',
            ],
            'y21_m06' => [
                'FixBrokenUrl',
                'EbayTaxReference',
            ],
            'y21_m07' => [
                'AmazonIossNumber',
            ],
            'y21_m08' => [
                'FixedStuckedManualPriceRevise',
            ],
            'y21_m10' => [
                'UpdateWatermarkImage',
                'PartsCompatibilityImprovement',
            ],
            'y21_m11' => [
                'EbayAddVatMode',
            ],
            'y21_m12' => [
                'AmazonOrdersFbaStore',
            ],
            'y22_m01' => [
                'ChangeRegistryKey',
            ],
            'y22_m02' => [
                'RemoveForumUrl',
                'ImportTaxRegistrationId',
                'ChangeDocumentationUrl',
            ],
            'y22_m03' => [
                'SetPrecisionInVatRateColumns',
            ],
            'y22_m04' => [
                'RemoveUnnecessaryConfig',
            ],
            'y22_m05' => [
                'AmazonOrderCancellationNewFlow',
                'DropListingColumns',
                'RemoveEbayPayment',
                'AddFeeColumnForEbayOrder',
            ],
            'y22_m06' => [
                'FixMistakenConfigs',
                'EbayFixedPriceModifier',
                'WalmartOrderItemBuyerCancellation',
            ],
            'y22_m07' => [
                'AddEpidsForItaly',
                'FixFieldBuyerCancellationRequested',
                'AmazonAccountRemoveToken',
                'AmazonMarketplaceRemoveAutomaticTokenColumn',
                'MoveEbayProductIdentifiers',
                'FixRemovedPolicyInScheduledActions',
                'ClearPolicyLinkingToDeletedAccount',
            ],
            'y22_m08' => [
                'AddAmazonMarketplacesBrSgInAe',
                'FixDevKeyForJapanAmazonMarketplace',
                'ClearPartListingAdditionalData',
                'AddIsReplacementColumnToAmazonOrder',
                'AddAfnProductActualQty',
                'FixNullableGroupsInConfigs',
                'MoveAmazonProductIdentifiers',
            ],
            'y22_m09' => [
                'AddAmazonMarketplaceBelgium',
                'RemoveHitCounterFromEbayDescriptionPolicy',
                'AddWalmartCustomerOrderId',
                'UpdateConfigAttrSupportUrl',
                'AddIsCriticalErrorReceivedFlag',
            ],
            'y22_m10' => [
                'AddIsSoldByAmazonColumnToAmazonOrder',
                'AddRepricingAccountTokenValidityField',
                'UpdateAmazonMarketplace',
                'RemoveEpidsForAustralia',
                'RemoveWalmartLegacySettings',
                'RemovePickupInStoreTablesAndColumns',
                'AmazonWalmartSellingPolicyPriceModifier',
                'RemoveRepricingDisablingConfig',
            ],
            'y22_m11' => [
                'FixWalmartChildListingId',
            ],
            'y23_m01' => [
                'FixEbayQtyReservationDays',
                'ChangeRepricerBaseUrl',
                'WalmartTrackingDetails',
                'RemoveConfigConvertLinebreaks',
                'EbayListingProductScheduledStopAction',
                'UpdateConfigSupportUrl',
                'AmazonRemoveUnnecessaryData',
                'AmazonProductTypes',
            ],
            'y23_m02' => [
                'AddImmediatePaymentColumn',
                'AddTags',
                'AddErrorCodeColumnForTags',
                'AmazonShippingTemplates',
            ],
            'y23_m03' => [
                'WalmartProductIdentifiers',
                'RemoveLicenseStatus',
                'RenameClientsToAccounts',
                'AddColumnIsStoppedManuallyForAmazonAndWalmartProducts',
                'UpgradeTags',
                'AddWizardVersionDowngrade',
            ],
            'y23_m04' => [
                'SetIsVatEbayMarketplacePL',
                'ChangeTypeProductAddIds',
                'RemoveUnavailableDataType',
                'EbayBuyerInitiatedOrderCancellation',
                'UpdateEbayVatMode',
            ],
            'y23_m06' => [
                'RemoveBuildLastVersionFromRegistry',
                'RemoveWalmartInventoryWpid',
                'CreateProductTypeValidationTable',
                'IgnoreVariationMpnInResolverConfig',
                'AddEbayBlockingErrorSetting',
            ],
            'y23_m07' => [
                'ChangeProductTypeValidationTableErrorMessageField',
                'DropTemplateDescriptionIdIndex',
                'RemoveScaleFromWatermarkSetting',
                'ChangeDocumentationUrl',
            ],
            'y23_m08' => [
                'AddShippingIrregularForEbay',
                'AddIsGetDeliveryPreferencesColumnToAmazonOrderTable',
                'RemoveCashOnDelivery',
                'RemoveAmazonDescriptionPolicyRelatedData',
                'CreateAmazonShippingMapTable',
                'AddNewColumnsToAmazonOrder',
                'AddAmazonSellingFormatListPrice',
                'AddFinalFeesColumnToAmazonOrderTable',
            ],
            'y23_m09' => [
                'AddOnlineBestOfferForEbayProduct',
                'RefactorAmazonOrderColumns',
                'RemoveLastAccessAndRunFromConfigTable',
                'AddAmazonProductTypeAttributeMappingTable',
                'AddProductModeColumnToEbayListing',
                'AddPriceRoundingToEbayAmazonWalmartSellingTemplate',
            ],
            'y23_m10' => [
                'EnableAmazonShippingServiceForSomeMarketplaces',
                'AddProductTypeViewModeColumn',
                'ImproveAmazonOrderPrefixes',
                'EnableEbayShippingRate',
                'RenameSoldByAmazonSetting',
                'ReAddIsSoldByAmazonColumnToAmazonOrder',
                'CreateEbayCategorySpecificValidationResultTable',
            ],
        ];
    }

    /**
     * @return \string[][]
     */
    public function getMultiRunFeaturesList(): array
    {
        return [
            'y20_m07' => [
                'WalmartOrderItemQty',
            ],
        ];
    }
}
