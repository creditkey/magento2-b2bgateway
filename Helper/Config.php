<?php

namespace CreditKey\B2BGateway\Helper;

use Magento\Store\Model\ScopeInterface;
use CreditKey\B2BGateway\Model\StoreConfigResolver;

/**
 * Config Helper
 */
class Config
{
    /**
     * Config paths
     */
    const XML_PATH_PAYMENT_CKGATEWAY = 'payment/creditkey_gateway';
    const XML_KEY_ENDPOINT = '/creditkey_endpoint';
    const XML_KEY_CHECKOUT_MODE = '/checkout_mode';
    const XML_KEY_PUBLICKEY = '/creditkey_publickey';
    const XML_KEY_SECRET = '/creditkey_sharedsecret';
    const XML_KEY_CHECKOUT_MIN_PRICE = '/price';
    const XML_KEY_CHECKOUT_MARKETING_TYPE = '/creditkey_checkoutdisplay';
    const XML_KEY_CHECKOUT_MARKETING_SIZE = '/creditkey_checkoutsize';
    const XML_KEY_PDP_MARKETING_ACTIVE = '/creditkey_productmarketing/active';
    const XML_KEY_PDP_MARKETING_CATS = '/creditkey_productmarketing/categories';
    const XML_KEY_PDP_MARKETING_PRICE = '/creditkey_productmarketing/price';
    const XML_KEY_PDP_MARKETING_TYPE = '/creditkey_productmarketing/type';
    const XML_KEY_PDP_MARKETING_SIZE = '/creditkey_productmarketing/size';
    const XML_KEY_CART_MARKETING_DESKTOP = '/creditkey_cartmarketing/desktop';
    const XML_KEY_CART_MARKETING_MOBILE = '/creditkey_cartmarketing/mobile';
    const XML_KEY_CART_MARKETING_ACTIVE = '/creditkey_cartmarketing/active';
    const XML_KEY_CART_MARKETING_PRICE = '/creditkey_cartmarketing/price';
    const XML_PATH_CREATE_INVOICE_AFTER_UPDATE_STATUS_ACTIVE = '/creditkey_create_invoice_auto/active';
    const XPATH_CREATE_INVOICE_AFTER_STATUS = '/creditkey_create_invoice_auto/create_invoice_after_status';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var StoreConfigResolver
     */
    private $storeConfigResolver;
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param StoreConfigResolver                                $storeConfigResolver
     * @param \Magento\Framework\Module\Manager                  $moduleManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        StoreConfigResolver                                $storeConfigResolver,
        \Magento\Framework\Module\Manager                  $moduleManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeConfigResolver = $storeConfigResolver;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Check is Meetanshi Auto Invoice Ship module enabled
     *
     * @return bool
     */
    public function isEnabledMeetanshiAutoInvShip()
    {
        return $this->moduleManager->isEnabled('Meetanshi_AutoInvShip');
    }

    /**
     * Get config value
     *
     * @param string $key
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getConfigValue($key)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_CKGATEWAY . $key,
            ScopeInterface::SCOPE_STORE,
            $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * Get active method
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getActiveMethod()
    {
        return $this->getConfigValue(self::XML_PATH_CREATE_INVOICE_AFTER_UPDATE_STATUS_ACTIVE);
    }

    /**
     * Get status for create invoice after update status
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStatusForCreateInvoiceAfterUpdateStatus()
    {
        return $this->getConfigValue(self::XPATH_CREATE_INVOICE_AFTER_STATUS);
    }

    /**
     * Get API Endpoint
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->getConfigValue(self::XML_KEY_ENDPOINT);
    }

    /**
     * Get Public Key
     *
     * @return string
     */
    public function getPublicKey()
    {
        return $this->getConfigValue(self::XML_KEY_PUBLICKEY);
    }

    /**
     * Get Shared Secret
     *
     * @return string
     */
    public function getSharedSecret()
    {
        return $this->getConfigValue(self::XML_KEY_SECRET);
    }

    /**
     * Get Checkout Minimum Price
     *
     * @return string
     */
    public function getCheckoutMinPrice()
    {
        return $this->getConfigValue(self::XML_KEY_CHECKOUT_MIN_PRICE);
    }

    /**
     * Is displaying marketing content on product details page enabled
     *
     * @return boolean
     */
    public function isPdpMarketingActive()
    {
        return (boolean)$this->getConfigValue(self::XML_KEY_PDP_MARKETING_ACTIVE);
    }

    /**
     * Get the marketing display type for checkout
     *
     * @return string
     */
    public function getCheckoutMarketingType()
    {
        return $this->getConfigValue(self::XML_KEY_CHECKOUT_MARKETING_TYPE);
    }

    /**
     * Get the marketing display size for checkout
     *
     * @return string
     */
    public function getCheckoutMarketingSize()
    {
        return $this->getConfigValue(self::XML_KEY_CHECKOUT_MARKETING_SIZE);
    }

    /**
     * Get array of category IDs selected to display marketing content
     *
     * @return array
     */
    public function getPdpMarketingCategories()
    {
        $catIds = $this->getConfigValue(self::XML_KEY_PDP_MARKETING_CATS);
        return ($catIds === null) ? [] : explode(',', (string) $catIds);
    }

    /**
     * Get price of marketing content to display on product details page
     *
     * @return string
     */
    public function getPdpMarketingPrice()
    {
        return $this->getConfigValue(self::XML_KEY_PDP_MARKETING_PRICE);
    }

    /**
     * Get price of marketing content to display on cart page
     *
     * @return string
     */
    public function getCartMarketingPrice()
    {
        return $this->getConfigValue(self::XML_KEY_CART_MARKETING_PRICE);
    }

    /**
     * Get type of marketing content to display on product details page
     *
     * @return string
     */
    public function getPdpMarketingType()
    {
        return $this->getConfigValue(self::XML_KEY_PDP_MARKETING_TYPE);
    }

    /**
     * Get size of marketing content to display on product details page
     *
     * @return string
     */
    public function getPdpMarketingSize()
    {
        return $this->getConfigValue(self::XML_KEY_PDP_MARKETING_SIZE);
    }

    /**
     * Get the marketing display type for cart desktop
     *
     * @return mixed
     */
    public function getCartMarketingDesktop()
    {
        return $this->getConfigValue(self::XML_KEY_CART_MARKETING_DESKTOP);
    }

    /**
     * Get the marketing display type for cart mobile
     *
     * @return mixed
     */
    public function getCartMarketingMobile()
    {
        return $this->getConfigValue(self::XML_KEY_CART_MARKETING_MOBILE);
    }

    /**
     * Check if displaying marketing content on cart page is enabled
     *
     * @return mixed
     */
    public function getCartMarketingEnable()
    {
        return $this->getConfigValue(self::XML_KEY_CART_MARKETING_ACTIVE);
    }

    /**
     * Get checkout mode
     *
     * @return mixed
     */
    public function getCheckoutMode()
    {
        return (string)$this->getConfigValue(self::XML_KEY_CHECKOUT_MODE);
    }
}
