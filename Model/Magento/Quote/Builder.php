<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Quote;

use Ess\M2ePro\Helper\Module\Configuration;

/**
 * Builds the quote object, which then can be converted to magento order
 */
class Builder extends \Ess\M2ePro\Model\AbstractModel
{
    public const PROCESS_QUOTE_ID = 'PROCESS_QUOTE_ID';

    //########################################

    protected $proxyOrder;

    /** @var  \Magento\Quote\Model\Quote */
    protected $quote;

    protected $currency;
    protected $magentoCurrencyFactory;
    protected $calculation;
    protected $storeConfig;
    protected $productResource;

    /** @var \Ess\M2ePro\Model\Magento\Quote\Manager */
    protected $quoteManager;

    /** @var \Ess\M2ePro\Model\Magento\Quote\Store\Configurator */
    protected $storeConfigurator;

    /** @var \Magento\Sales\Model\OrderIncrementIdChecker */
    protected $orderIncrementIdChecker;

    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $configurationHelper;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Order\ProxyObject $proxyOrder,
        \Ess\M2ePro\Model\Currency $currency,
        \Magento\Directory\Model\CurrencyFactory $magentoCurrencyFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Tax\Model\Calculation $calculation,
        \Magento\Framework\App\Config\ReinitableConfigInterface $storeConfig,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Magento\Quote\Manager $quoteManager,
        \Magento\Sales\Model\OrderIncrementIdChecker $orderIncrementIdChecker,
        Configuration $configurationHelper
    ) {
        $this->proxyOrder = $proxyOrder;
        $this->currency = $currency;
        $this->magentoCurrencyFactory = $magentoCurrencyFactory;
        $this->calculation = $calculation;
        $this->storeConfig = $storeConfig;
        $this->productResource = $productResource;
        $this->quoteManager = $quoteManager;
        $this->orderIncrementIdChecker = $orderIncrementIdChecker;
        $this->configurationHelper = $configurationHelper;

        parent::__construct($helperFactory, $modelFactory);
    }

    public function __destruct()
    {
        if ($this->storeConfigurator === null) {
            return;
        }

        $this->storeConfigurator->restoreOriginalStoreConfigForOrder();
    }

    //########################################

    public function build()
    {
        try {
            // do not change invoke order
            // ---------------------------------------
            $this->initializeQuote();
            $this->initializeCustomer();
            $this->initializeAddresses();

            $this->configureStore();
            $this->configureTaxCalculation();

            $this->initializeCurrency();
            $this->initializeShippingMethodData();
            $this->initializeQuoteItems();
            $this->initializePaymentMethodData();

            $this->quote = $this->quoteManager->save($this->quote);

            $this->prepareOrderNumber();

            return $this->quote;
            // ---------------------------------------
        } catch (\Exception $e) {
            if ($this->quote === null) {
                $this->getHelper('Module_Exception')->process($e);

                throw $e;
            }

            // Remove ordered items from customer cart
            $this->quote->setIsActive(false);
            $this->quote->removeAllAddresses();
            $this->quote->removeAllItems();

            $this->quote->save();

            throw $e;
        }
    }

    //########################################

    private function initializeQuote()
    {
        $this->quote = $this->quoteManager->getBlankQuote();

        $this->quote->setCheckoutMethod($this->proxyOrder->getCheckoutMethod());
        $this->quote->setStore($this->proxyOrder->getStore());
        $this->quote->getStore()->setData('current_currency', $this->quote->getStore()->getBaseCurrency());

        /**
         * The quote is empty at this moment, so it is not need to collect totals
         */
        $this->quote->setTotalsCollectedFlag(true);
        $this->quote = $this->quoteManager->save($this->quote);
        $this->quote->setTotalsCollectedFlag(false);

        $this->quote->setIsM2eProQuote(true);
        $this->quote->setIsNeedToSendEmail($this->proxyOrder->isMagentoOrdersCustomerNewNotifyWhenOrderCreated());
        $this->quote->setNeedProcessChannelTaxes(
            $this->proxyOrder->isTaxModeChannel() ||
            ($this->proxyOrder->isTaxModeMixed() &&
                ($this->proxyOrder->hasTax() || $this->proxyOrder->getWasteRecyclingFee()))
        );

        $this->quoteManager->replaceCheckoutQuote($this->quote);

        /** @var \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper */
        $globalDataHelper = $this->getHelper('Data\GlobalData');

        $globalDataHelper->unsetValue(self::PROCESS_QUOTE_ID);
        $globalDataHelper->setValue(self::PROCESS_QUOTE_ID, $this->quote->getId());
    }

    //########################################

    private function initializeCustomer()
    {
        if ($this->proxyOrder->isCheckoutMethodGuest()) {
            $this->quote
                ->setCustomerId(null)
                ->setCustomerEmail($this->proxyOrder->getBuyerEmail())
                ->setCustomerFirstname($this->proxyOrder->getCustomerFirstName())
                ->setCustomerLastname($this->proxyOrder->getCustomerLastName())
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);

            return;
        }

        $this->quote->assignCustomer($this->proxyOrder->getCustomer());
    }

    //########################################

    private function initializeAddresses()
    {
        $billingAddress = $this->quote->getBillingAddress();
        $billingAddress->addData($this->proxyOrder->getBillingAddressData());

        $magentoCode = $this->proxyOrder->getMagentoShippingCode();
        $carrierCode = $this->proxyOrder->getCarrierCode();

        $billingAddress->setLimitCarrier($carrierCode);
        $billingAddress->setShippingMethod($magentoCode);
        $billingAddress->setCollectShippingRates(true);
        $billingAddress->setShouldIgnoreValidation($this->proxyOrder->shouldIgnoreBillingAddressValidation());

        // ---------------------------------------

        $shippingAddress = $this->quote->getShippingAddress();
        $shippingAddress->setSameAsBilling(0); // maybe just set same as billing?
        $shippingAddress->addData($this->proxyOrder->getAddressData());

        $shippingAddress->setLimitCarrier($carrierCode);
        $shippingAddress->setShippingMethod($magentoCode);
        $shippingAddress->setCollectShippingRates(true);
    }

    //########################################

    private function initializeCurrency()
    {
        /** @var \Ess\M2ePro\Model\Currency $currencyHelper */
        $currencyHelper = $this->currency;

        if ($currencyHelper->isConvertible($this->proxyOrder->getCurrency(), $this->quote->getStore())) {
            $currentCurrency = $this->magentoCurrencyFactory->create()->load(
                $this->proxyOrder->getCurrency()
            );
        } else {
            $currentCurrency = $this->quote->getStore()->getBaseCurrency();
        }

        $this->quote->getStore()->setData('current_currency', $currentCurrency);
    }

    //########################################

    /**
     * Configure store (invoked only after address, customer and store initialization and before price calculations)
     */
    private function configureStore()
    {
        $this->storeConfigurator = $this->modelFactory->getObject(
            'Magento_Quote_Store_Configurator',
            ['quote' => $this->quote, 'proxyOrder' => $this->proxyOrder]
        );

        $this->storeConfigurator->prepareStoreConfigForOrder();
    }

    //########################################

    private function configureTaxCalculation()
    {
        // this prevents customer session initialization (which affects cookies)
        // see Mage_Tax_Model_Calculation::getCustomer()
        $this->calculation->setCustomer($this->quote->getCustomer());
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order\Item\ProxyObject $item
     * @param \Ess\M2ePro\Model\Magento\Quote\Item $quoteItemBuilder
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\DataObject $request
     *
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function initializeQuoteItem($item, $quoteItemBuilder, $product, $request)
    {
        // ---------------------------------------
        $productOriginalPrice = (float)$product->getPrice();

        $price = $item->getBasePrice();
        $product->setPrice($price);
        $product->setSpecialPrice($price);
        // ---------------------------------------

        // see Mage_Sales_Model_Observer::substractQtyFromQuotes
        $this->quote->setItemsCount($this->quote->getItemsCount() + 1);
        $this->quote->setItemsQty((float)$this->quote->getItemsQty() + $request->getQty());

        $result = $this->quote->addProduct($product, $request);
        if (is_string($result)) {
            throw new \Ess\M2ePro\Model\Exception($result);
        }

        $quoteItem = $this->quote->getItemByProduct($product);
        if ($quoteItem === false) {
            return;
        }

        $quoteItem->setStoreId($this->quote->getStoreId());
        $quoteItem->setOriginalCustomPrice($item->getPrice());
        $quoteItem->setOriginalPrice($productOriginalPrice);
        $quoteItem->setBaseOriginalPrice($productOriginalPrice);
        $quoteItem->setNoDiscount(1);
        foreach ($quoteItem->getChildren() as $itemChildren) {
            $itemChildren->getProduct()->setTaxClassId($quoteItem->getProduct()->getTaxClassId());
        }

        $giftMessageId = $quoteItemBuilder->getGiftMessageId();
        if (!empty($giftMessageId)) {
            $quoteItem->setGiftMessageId($giftMessageId);
        }

        $quoteItem->setAdditionalData($quoteItemBuilder->getAdditionalData($quoteItem));

        $quoteItem->setWasteRecyclingFee($item->getWasteRecyclingFee() / $item->getQty());
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function initializeQuoteItems()
    {
        $this->quote->setUseM2eProDiscount(false);
        $discountAmount = 0;

        foreach ($this->proxyOrder->getItems() as $item) {
            $this->clearQuoteItemsCache();

            /** @var \Ess\M2ePro\Model\Magento\Quote\Item $quoteItemBuilder */
            $quoteItemBuilder = $this->modelFactory->getObject(
                'Magento_Quote_Item',
                [
                    'quote' => $this->quote,
                    'proxyItem' => $item,
                ]
            );

            $product = $quoteItemBuilder->getProduct();

            if (!$item->pretendedToBeSimple()) {
                $this->initializeQuoteItem($item, $quoteItemBuilder, $product, $quoteItemBuilder->getRequest());
                continue;
            }

            // ---------------------------------------

            $totalPrice = 0;
            $products = [];
            foreach ($product->getTypeInstance()->getAssociatedProducts($product) as $associatedProduct) {
                /** @var \Magento\Catalog\Model\Product $associatedProduct */
                if ($associatedProduct->getQty() <= 0) { // skip product if default qty zero
                    continue;
                }

                $totalPrice += $associatedProduct->getPrice();
                $products[] = $associatedProduct;
            }

            // ---------------------------------------

            foreach ($products as $associatedProduct) {
                $item->setQty($associatedProduct->getQty() * $item->getOriginalQty());

                $productPriceInSetPercent = ($associatedProduct->getPrice() / $totalPrice) * 100;
                $productPriceInItem = (($item->getOriginalPrice() * $productPriceInSetPercent) / 100);
                $item->setPrice($productPriceInItem / $associatedProduct->getQty());

                if ($this->configurationHelper->isGroupedProductModeSet()) {
                    $discountAmount += $this->getDiscount(
                        $productPriceInItem,
                        $associatedProduct->getQty(),
                        $item->getOriginalQty()
                    );
                }

                /** @var \Ess\M2ePro\Model\Magento\Quote\Item $quoteItemBuilder */
                $quoteItemBuilder = $this->modelFactory->getObject(
                    'Magento_Quote_Item',
                    [
                        'quote' => $this->quote,
                        'proxyItem' => $item,
                    ]
                );

                $this->initializeQuoteItem(
                    $item,
                    $quoteItemBuilder,
                    $quoteItemBuilder->setTaxClassIntoProduct($associatedProduct),
                    $quoteItemBuilder->getRequest()
                );
            }
        }

        $allItems = $this->quote->getAllItems();
        $this->quote->getItemsCollection()->removeAllItems();

        foreach ($allItems as $item) {
            $item->save();
            $this->quote->getItemsCollection()->addItem($item);
        }

        if ($this->quote->getUseM2eProDiscount()) {
            $this->quote->setCoinDiscount($discountAmount);
        }
    }

    private function getDiscount($productPriceInItem, $associatedProductQty, $OriginalQty)
    {
        $total = 0;
        $roundPrice = round(($productPriceInItem / $associatedProductQty), 2) * $associatedProductQty;

        if ($productPriceInItem !== $roundPrice) {
            $this->quote->setUseM2eProDiscount(true);
            $total = ($roundPrice - $productPriceInItem) * $OriginalQty;
        }

        return $total;
    }

    /**
     * Mage_Sales_Model_Quote_Address caches items after each collectTotals call. Some extensions calls collectTotals
     * after adding new item to quote in observers. So we need clear this cache before adding new item to quote.
     */
    private function clearQuoteItemsCache()
    {
        foreach ($this->quote->getAllAddresses() as $address) {
            $address->unsetData('cached_items_all');
            $address->unsetData('cached_items_nominal');
            $address->unsetData('cached_items_nonnominal');
        }
    }

    //########################################

    private function initializeShippingMethodData()
    {
        $this->getHelper('Data\GlobalData')->unsetValue('shipping_data');
        $this->getHelper('Data\GlobalData')->setValue('shipping_data', $this->proxyOrder->getShippingData());

        $this->proxyOrder->initializeShippingMethodDataPretendedToBeSimple();
    }

    //########################################

    private function initializePaymentMethodData()
    {
        $quotePayment = $this->quote->getPayment();
        $quotePayment->importData($this->proxyOrder->getPaymentData());
    }

    //########################################

    private function prepareOrderNumber()
    {
        if ($this->proxyOrder->isOrderNumberPrefixSourceChannel()) {
            $orderNumber = $this->proxyOrder->getOrderNumberPrefix() . $this->proxyOrder->getChannelOrderNumber();
            $this->orderIncrementIdChecker->isIncrementIdUsed($orderNumber) && $orderNumber .= '(1)';

            $this->quote->setReservedOrderId($orderNumber);

            return;
        }

        $orderNumber = $this->quote->getReservedOrderId();
        empty($orderNumber) && $orderNumber = $this->quote->getResource()->getReservedOrderId($this->quote);
        $orderNumber = $this->proxyOrder->getOrderNumberPrefix() . $orderNumber;

        if ($this->orderIncrementIdChecker->isIncrementIdUsed($orderNumber)) {
            $orderNumber = $this->quote->getResource()->getReservedOrderId($this->quote);
        }

        $this->quote->setReservedOrderId($orderNumber);
    }

    //########################################
}
