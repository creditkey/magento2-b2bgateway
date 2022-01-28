<?php
namespace CreditKey\B2BGateway\Controller\Order;

/**
 * Class AdminComplete
 * @package CreditKey\B2BGateway\Controller\Order
 */
class AdminComplete extends \CreditKey\B2BGateway\Controller\AbstractCreditKeyController
{
    /** @var \Magento\Sales\Api\OrderRepositoryInterface  */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;

    protected $scopeConfig;

    /**
     * AdminComplete constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \CreditKey\B2BGateway\Helper\Api $creditKeyApi
     * @param \CreditKey\B2BGateway\Helper\Data $creditKeyData
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CreditKey\B2BGateway\Helper\Api $creditKeyApi,
        \CreditKey\B2BGateway\Helper\Data $creditKeyData,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->scopeConfig = $scopeConfig;

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
     * Execute the cancel action
     *
     * @return $this
     */
    public function execute()
    {
        $ckId = $this->getRequest()->getParam('id');
        $orderId = $this->creditKeyData->getOrderIdByCkId($ckId);

        if($orderId) {
            $order = $this->orderRepository->get($orderId);
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->save();

            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $action = $this->scopeConfig->getValue('payment/creditkey_gateway/payment_action', $storeScope);

            if($action == \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE) {
                $this->captureAndCreateInvoice($order);
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function captureAndCreateInvoice(\Magento\Sales\Model\Order $order)
    {
        // prepare invoice and generate it
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->register();

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($order)
            ->addObject($invoice)
            ->addObject($invoice->getOrder())->save();
    }
}
