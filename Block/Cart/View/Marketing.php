<?php
namespace CreditKey\B2BGateway\Block\Cart\View;

/**
 * Marketing Block
 */
class Marketing extends \Magento\Framework\View\Element\Template
{
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
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * Marketing constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \CreditKey\B2BGateway\Helper\Config $config
     * @param \Magento\Framework\Serialize\SerializerInterface $json
     * @param \CreditKey\B2BGateway\Helper\Api $creditKeyApi
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \CreditKey\B2BGateway\Helper\Config $config,
        \Magento\Framework\Serialize\SerializerInterface $json,
        \CreditKey\B2BGateway\Helper\Api $creditKeyApi,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        $this->config = $config;
        $this->json = $json;
        $this->creditKeyApi = $creditKeyApi;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
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
                'desktop' => $this->config->getCartMarketingDesktop(),
                'mobile' => $this->config->getCartMarketingMobile(),
                'charges' => $this->getCharges()
            ]
        ];

        return $this->json->serialize($config);
    }

    /**
     * @return mixed
     */
    public function isActiveEnable() {
        return $this->config->getCartMarketingEnable();
    }

    /**
     * Get an array of the charges for the cart page
     *
     * @return array of charges as follows
     * [total, shipping, tax, discount_amount, grand_total]
     */
    private function getCharges()
    {
        $quote = $this->checkoutSession->getQuote();
        $tax = isset($quote->getTotals()['tax']) ? $quote->getTotals()['tax']->getValue() : 0;
        return [
            $quote->getSubtotal(),
            0,
            $tax,
            0,
            $quote->getGrandTotal()
        ];
    }


}
