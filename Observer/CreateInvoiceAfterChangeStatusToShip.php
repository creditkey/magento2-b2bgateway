<?php

namespace CreditKey\B2BGateway\Observer;

use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use CreditKey\B2BGateway\Helper\Config;
use Psr\Log\LoggerInterface;

class CreateInvoiceAfterChangeStatusToShip implements ObserverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Config $configHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param TransactionFactory $transactionFactory
     * @param Transaction $transaction
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $configHelper,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        TransactionFactory $transactionFactory,
        Transaction $transaction,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * Observer execution
     *
     * @param Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->configHelper->getActiveMethod()) {
            $order = $observer->getData('order');
            if (!$order->getId()) {
                return;
            }
            $paymentMethod = $order->getPayment()->getMethod();
            if ($paymentMethod == 'creditkey_gateway' || $paymentMethod == 'creditkey_gateway_admin') {
                if ($this->configHelper->isEnabledMeetanshiAutoInvShip()) {
                    return;
                }

                if ($order instanceof AbstractModel) {
                    $statusForAutoCreateInvoice = $this->configHelper->getStatusForCreateInvoiceAfterUpdateStatus();

                    if ($order->getState() !== $statusForAutoCreateInvoice &&
                        $order->getStatus() !== $statusForAutoCreateInvoice
                    ) {
                        return;
                    }

                    $order = $this->orderRepository->get($order->getId());
                    $invoice = $this->invoiceService->prepareInvoice($order);
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    $transaction = $this->transactionFactory->create();
                    $transaction->addObject($order)
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())->save();
                    try {
                        if ($invoice->getId()) {
                            $this->invoiceSender->send($invoice);
                        }
                    } catch (\Exception $e) {
                        $this->logger->info('Error when sending invoice email: ' . $e->getMessage());
                    }
                }
            }
        }
    }
}
