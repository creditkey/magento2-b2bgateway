<?php
namespace CreditKey\B2BGateway\Controller\Order;

use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Complete order controller
 */
class Complete extends \CreditKey\B2BGateway\Controller\AbstractCreditKeyController
{
    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $modelCart;

    /**
     * @var \Magento\Quote\Api\CartRepository
     */
    protected $quoteRepository;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \CreditKey\B2BGateway\Helper\Api $creditKeyApi
     * @param \CreditKey\B2BGateway\Helper\Data $creditKeyData
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRespository
     * @param \Magento\Checkout\Model\Cart $modelCart
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CreditKey\B2BGateway\Helper\Api $creditKeyApi,
        \CreditKey\B2BGateway\Helper\Data $creditKeyData,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        OrderSender $orderSender,
        \Magento\Checkout\Model\Cart $modelCart
    ) {
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->modelCart = $modelCart;
        $this->orderSender = $orderSender;

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
     * Execute the complete order action
     *
     * @return \Magento\Framework\Controller\Result\Redirect|$this
     */
    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('ref');
        $ckOrderId = $this->getRequest()->getParam('key');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        //$quote = $this->checkoutSession->getQuote();
        $quote = $this->quoteRepository->get($quoteId);

        if ($quote->getId() !== $quoteId) {
            // Checkout session expired - redirect back to checkout
            $this->logger->critical("Invalid checkout session");
            $this->messageManager->addErrorMessage(__('INVALID_CHECKOUT_SESSION'));
            $resultRedirect->setPath('checkout');
            return $resultRedirect;
        }

        // Check that the payment is authorized
        $this->creditKeyApi->configure();
        $isAuthorized = false;
        
        try {
            $isAuthorized = \CreditKey\Checkout::completeCheckout($ckOrderId);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(__('CREDIT_KEY_AUTH_FAILED'));
            $resultRedirect->setPath('checkout');
            return $resultRedirect;
        }

        if (!$isAuthorized) {
            // Payment not authorized - redirect back to checkout
            $resultRedirect->setPath('checkout');
            return $resultRedirect;
        }

        $this->checkoutSession
            ->setLastQuoteId($quote->getId())
            ->setLastSuccessQuoteId($quote->getId())
            ->clearHelperData();

        $email = $quote->getBillingAddress()->getEmail();

        if (!$this->customerSession->isLoggedIn()) {
            $quote->setCheckoutMethod('guest');
            $quote->setCustomerIsGuest(true);
            $quote->setCustomerEmail($email);
        }

        $order = $this->quoteManagement->submit($quote);

        if ($order) {
            $this->checkoutSession
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $this->orderSender->send($order);
            $order->save();

            $orderPayment = $order->getPayment();
            $orderPayment->setAdditionalInformation('ckOrderId', $ckOrderId);
            $orderPayment->setTransactionId($ckOrderId);
            $orderPayment->setLastTransId($ckOrderId);
            $orderPayment->setIsTransactionClosed(false);
            $orderPayment->setShouldCloseParentTransaction(false);
            $transaction = $orderPayment->addTransaction(
                \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH
            );

            $order->save();

            try {
                // Send the Magento Order ID and Status to Credit Key
                \CreditKey\Orders::update($ckOrderId, $order->getState(), $order->getIncrementId(), null, null, null);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        $cart = $this->modelCart;
        $cart->truncate();
        $cart->save();
        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            $itemId = $item->getItemId();
            $cart->removeItem($itemId)->save();
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('checkout/onepage/success');
        $this->logger->debug('Finished order complete controller.');
        return $resultRedirect;
    }
}
