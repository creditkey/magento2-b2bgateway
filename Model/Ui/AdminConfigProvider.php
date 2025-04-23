<?php
namespace CreditKey\B2BGateway\Model\Ui;

class AdminConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    const CODE = 'creditkey_gateway_admin';

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepo;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;
    /**
     * @var \CreditKey\B2BGateway\Helper\Config
     */
    private $config;
    /**
     * @var \CreditKey\B2BGateway\Helper\Api
     */
    private $creditKeyApi;
    /**
     * @var \CreditKey\B2BGateway\Helper\Data
     */
    private $creditKeyData;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Construct
     *
     * @param \Magento\Checkout\Model\Cart             $cart
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Customer\Model\Session          $customerSession
     * @param \Magento\Framework\UrlInterface          $urlBuilder
     * @param \CreditKey\B2BGateway\Helper\Config      $config
     * @param \CreditKey\B2BGateway\Helper\Api         $creditKeyApi
     * @param \CreditKey\B2BGateway\Helper\Data        $creditKeyData
     * @param \Psr\Log\LoggerInterface                 $logger
     */
    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \CreditKey\B2BGateway\Helper\Config $config,
        \CreditKey\B2BGateway\Helper\Api $creditKeyApi,
        \CreditKey\B2BGateway\Helper\Data $creditKeyData,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->cart = $cart;
        $this->assetRepo = $assetRepo;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
        $this->creditKeyApi = $creditKeyApi;
        $this->creditKeyData = $creditKeyData;
        $this->logger = $logger;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'endpoint' => $this->config->getEndpoint(),
                    'assetSrc' => $this->assetRepo->getUrl("CreditKey_B2BGateway::images/ck-logo-new.svg"),
                    'publicKey' => $this->config->getPublicKey()
                ]
            ]
        ];
    }
}
