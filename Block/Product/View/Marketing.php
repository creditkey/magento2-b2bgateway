<?php

namespace CreditKey\B2BGateway\Block\Product\View;

use CreditKey\B2BGateway\Helper\Api;
use CreditKey\B2BGateway\Helper\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
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
     * @var ProductRepositoryInterface
     */
    private $productRepository;

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
     * @param ProductRepositoryInterface $productRepository
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
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->config = $config;
        $this->json = $json;
        $this->creditKeyApi = $creditKeyApi;
        $this->logger = $logger;
        $this->taxCalculation = $taxCalculation;
        $this->configurableProduct = $configurableProduct;
        $this->productRepository = $productRepository;
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

            $productPrice = $this->getProductMessagingPrice($product);

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
        $productPrice = $this->getProductMessagingPrice($product);

        $taxClassId = $product->getCustomAttribute('tax_class_id');
        $taxRate = $taxClassId
            ? $this->taxCalculation->getCalculatedRate($taxClassId->getValue())
            : 0;

        return [
            $productPrice,
            0, // no quote yet to calc shipping
            $taxRate,
            0, // no quote to apply discount
            $productPrice + $taxRate
        ];
    }

    /**
     * Get the amount to send to Credit Key PDP messaging.
     *
     * Products that render price ranges, such as configurable products, can
     * return 0 from getFinalPrice() on the parent product. Prefer Magento's
     * minimal final price so the SDK receives the lower visible PDP price.
     *
     * @param Product $product
     * @return float
     */
    private function getProductMessagingPrice(Product $product)
    {
        $minimalPrice = $this->getMinimalFinalPrice($product);
        if ($minimalPrice > 0) {
            return $minimalPrice;
        }

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $childPrice = $this->getLowestChildPrice($product);
            if ($childPrice > 0) {
                return $childPrice;
            }
        }

        $finalPrice = (float) $product->getFinalPrice();
        if ($finalPrice > 0) {
            return $finalPrice;
        }

        return max(0, (float) $product->getPrice());
    }

    /**
     * Get Magento's minimal final price amount, when available.
     *
     * @param Product $product
     * @return float
     */
    private function getMinimalFinalPrice(Product $product)
    {
        try {
            $price = $product->getPriceInfo()->getPrice('final_price');

            if (method_exists($price, 'getMinimalPrice')) {
                return $this->getAmountValue($price->getMinimalPrice());
            }

            if (method_exists($price, 'getAmount')) {
                return $this->getAmountValue($price->getAmount());
            }
        } catch (\Exception $e) {
            $this->logger->debug('Unable to resolve Credit Key PDP minimal price: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Get the lowest positive child price for configurable products.
     *
     * @param Product $product
     * @return float
     */
    private function getLowestChildPrice(Product $product)
    {
        $lowestPrice = null;
        $childIdsByAttribute = $this->configurableProduct->getChildrenIds($product->getId());
        $childIds = [];

        foreach ($childIdsByAttribute as $ids) {
            $childIds = array_merge($childIds, $ids);
        }

        foreach (array_unique($childIds) as $childId) {
            try {
                $child = $this->productRepository->getById($childId);
                $childPrice = (float) $child->getFinalPrice();
                if ($childPrice <= 0) {
                    $childPrice = (float) $child->getPrice();
                }
            } catch (\Exception $e) {
                $this->logger->debug('Unable to resolve Credit Key PDP child price: ' . $e->getMessage());
                continue;
            }

            if ($childPrice > 0 && ($lowestPrice === null || $childPrice < $lowestPrice)) {
                $lowestPrice = $childPrice;
            }
        }

        return $lowestPrice === null ? 0 : $lowestPrice;
    }

    /**
     * Extract a numeric value from Magento price amount objects.
     *
     * @param mixed $amount
     * @return float
     */
    private function getAmountValue($amount)
    {
        if (is_object($amount) && method_exists($amount, 'getValue')) {
            return (float) $amount->getValue();
        }

        return (float) $amount;
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
