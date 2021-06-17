<?php
namespace CreditKey\B2BGateway\Block\Product\View;

use Magento\Catalog\Model\Product;

/**
 * Marketing Block
 */
class Marketing extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Product
     */
    private $product = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry = null;

    /**
     * @var \CreditKey\B2BGateway\Helper\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $json;

    /**
     * @var \CreditKey\B2BGateway\Helper\Api
     */
    private $creditKeyApi;

    /**
     * @var \Magento\Tax\Api\TaxCalculationInterface
     */
    private $taxCalculation;

    /**
     * Stored array of category ids authorized to display marketing content
     *
     * @var array
     */
    private $authorizedCategories;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \CreditKey\B2BGateway\Helper\Config $config
     * @param \Magento\Framework\Serialize\SerializerInterface $json
     * @param \CreditKey\B2BGateway\Helper\Api $creditKeyApi
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculation
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \CreditKey\B2BGateway\Helper\Config $config,
        \Magento\Framework\Serialize\SerializerInterface $json,
        \CreditKey\B2BGateway\Helper\Api $creditKeyApi,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculation,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->config = $config;
        $this->json = $json;
        $this->creditKeyApi = $creditKeyApi;
        $this->logger = $logger;
        $this->taxCalculation = $taxCalculation;
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

            // Is product in selected price range?
            $price = abs($this->config->getPdpMarketingPrice());
            if (is_numeric($price) && $price != 0 && $product->getPrice() >= $price) {
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
     * Return an array of category ids authorized to display
     * our marketing content
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
