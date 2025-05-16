<?php

namespace CreditKey\B2BGateway\Block\Product\View;

use CreditKey\B2BGateway\Helper\Api;
use CreditKey\B2BGateway\Helper\Config;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Tax\Api\TaxCalculationInterface;
use Psr\Log\LoggerInterface;

/**
 * Marketing Block
 */
class Marketing extends Template
{
    /**
     * @var Product
     */
    private $product = null;

    /**
     * @var Configurable
     */
    private $configurableProduct;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SerializerInterface
     */
    private $json;

    /**
     * @var Api
     */
    private $creditKeyApi;

    /**
     * @var TaxCalculationInterface
     */
    private $taxCalculation;

    /**
     * Stored array of category ids authorized to display marketing content
     *
     * @var array
     */
    private $authorizedCategories;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Config $config
     * @param SerializerInterface $json
     * @param Api $creditKeyApi
     * @param LoggerInterface $logger
     * @param TaxCalculationInterface $taxCalculation
     * @param Configurable $configurableProduct
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $config,
        SerializerInterface $json,
        Api $creditKeyApi,
        LoggerInterface $logger,
        TaxCalculationInterface $taxCalculation,
        Configurable $configurableProduct,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->config = $config;
        $this->json = $json;
        $this->creditKeyApi = $creditKeyApi;
        $this->logger = $logger;
        $this->taxCalculation = $taxCalculation;
        $this->configurableProduct = $configurableProduct;
        parent::__construct($context, $data);
    }

    /**
     * Returns a Product
     *
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->product) {
            $this->product = $this->coreRegistry->registry('product');
        }
        return $this->product;
    }

    /**
     * Check if the current product is authorized to display marketing content
     *
     * @return bool
     */
    public function isAuthorized()
    {
        $product = $this->getProduct();

        // Sanity check product has been loaded
        if ($product && $product->getId()) {

            $price = abs((float) $this->config->getPdpMarketingPrice());

            $productPrice = $product->getPrice();
            if ($product->getTypeId() == Configurable::TYPE_CODE) {
                $childProducts = $this->configurableProduct->getUsedProducts($product);
                foreach ($childProducts as $child) {
                    if ($child->getPrice() > $productPrice) {
                        $productPrice = $child->getPrice();
                    }
                }
            }

            if (is_numeric($price) && $price != 0 && $productPrice < $price) {
                return false;
            }

            if (empty($this->getAuthorizedCategories())) {
                // If no authorized categories specified we default to all
                return true;
            } else {
                // Is product in any authorized category?
                $categoryIds = $product->getCategoryIds();
                $matches = array_intersect($this->getAuthorizedCategories(), $categoryIds);
                return (bool) (!empty($matches));
            }
        }

        return false;
    }

    /**
     * Get JSON config for JS component
     *
     * @return string
     */
    public function getJsonConfig()
    {
        $this->creditKeyApi->configure();

        $config = [
            'ckConfig' => [
                'endpoint' => $this->config->getEndpoint(),
                'publicKey' => $this->config->getPublicKey(),
                'charges' => $this->getCharges()
            ]
        ];

        return $this->json->serialize($config);
    }

    /**
     * Get an array of the charges for the product
     *
     * @return array of charges as follows
     * [total, shipping, tax, discount_amount, grand_total]
     */
    private function getCharges()
    {
        $product = $this->getProduct();

        $taxClassId = $product->getCustomAttribute('tax_class_id');
        $taxRate = $taxClassId
            ? $this->taxCalculation->getCalculatedRate($taxClassId->getValue())
            : 0;

        return [
            $product->getFinalPrice(),
            0, // no quote yet to calc shipping
            $taxRate,
            0, // no quote to apply discount
            $product->getFinalPrice() + $taxRate
        ];
    }

    /**
     * Return an array of category ids authorized to display our marketing content
     *
     * @return array
     */
    private function getAuthorizedCategories()
    {
        if (!$this->authorizedCategories) {
            $this->authorizedCategories = $this->config->getPdpMarketingCategories();
        }
        return $this->authorizedCategories;
    }
}
