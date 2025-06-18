<?php
namespace CreditKey\B2BGateway\Controller\Order;

use Magento\Framework\Controller\ResultFactory;

/**
 * Create Order Controller
 */
class Create extends \CreditKey\B2BGateway\Controller\AbstractCreditKeyController
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;
    /**
     * @var \CreditKey\B2BGateway\Helper\Config
     */
    private $config;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \CreditKey\B2BGateway\Helper\Api      $creditKeyApi
     * @param \CreditKey\B2BGateway\Helper\Data     $creditKeyData
     * @param \CreditKey\B2BGateway\Helper\Config   $config
     * @param \Magento\Customer\Model\Url           $customerUrl
     * @param \Magento\Checkout\Model\Session       $checkoutSession
     * @param \Magento\Customer\Model\Session       $customerSession
     * @param \Psr\Log\LoggerInterface              $logger
     * @param \Magento\Framework\UrlInterface       $urlBuilder
     * @param \Magento\Framework\App\Request\Http   $request
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CreditKey\B2BGateway\Helper\Api $creditKeyApi,
        \CreditKey\B2BGateway\Helper\Data $creditKeyData,
        \CreditKey\B2BGateway\Helper\Config $config,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->config = $config;

        parent::__construct(
            $context,
            $creditKeyApi,
            $creditKeyData,
            $customerUrl,
            $checkoutSession,
            $customerSession,
            $logger
        );
    }

    /**
     * Execute Create order action
     *
     * @return \Magento\Framework\Controller\Result\Redirect|$this
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $quote = $this->checkoutSession->getQuote();

        $cartContents = $this->creditKeyData->buildCartContents($quote);
        $billingAddress = $this->creditKeyData->buildAddress($quote->getBillingAddress());
        $shippingAddress = $this->creditKeyData->buildAddress($quote->getShippingAddress());
        $charges = $this->creditKeyData->buildCharges($quote);

        // need to use this id to reference the quote when completing the order
        $remoteId = $quote->getId();
        $customerId = null;
        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomer()->getId();
        }

        $returnUrl = $this->urlBuilder->getUrl(
            'creditkey_gateway/order/complete',
            [
                'ref' => $remoteId,
                'key' => '%CKKEY%',
                '_secure' => true
            ]
        );
        $cancelUrl = $this->urlBuilder->getUrl('creditkey_gateway/order/cancel');

        $this->creditKeyApi->configure();

        $mode = 'redirect';
        if ($this->request->getParam('modal')) {
            $mode = 'modal';
        }

        if ($this->config->getCheckoutMode()) {
            $mode = $this->config->getCheckoutMode();
        }

        try {
            $redirectTo = \CreditKey\Checkout::beginCheckout(
                $cartContents,
                $billingAddress,
                $shippingAddress,
                $charges,
                $remoteId,
                $customerId,
                $returnUrl,
                $cancelUrl,
                $mode
            );

            $resultRedirect->setUrl($redirectTo);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(__('CREDIT_KEY_UNAVAILABLE'));
            $resultRedirect->setPath('modal' === $mode ? '*/*/failure' : 'checkout/cart');
        }

        return $resultRedirect;
    }
}
