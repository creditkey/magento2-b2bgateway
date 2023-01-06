<?php

namespace CreditKey\B2BGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use CreditKey\B2BGateway\Helper\Config;

class CreateInvoiceAfterChangeStatusToShip implements ObserverInterface
{
    protected $orderRepository;
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;
    protected $configHelper;
    protected $transactionFactory;

    /**
     * CreateInvoiceAfterChangeStatusToShip constructor.
     * @param Config $configHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param Transaction $transaction
     */
    public function __construct(
        Config $configHelper,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        Transaction $transaction
    ) {
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->configHelper = $configHelper;
    }

    /**
     * @param Observer $observer
     * @return $this|void
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

                if ($order instanceof \Magento\Framework\Model\AbstractModel) {
                    $statusForAutoCreateInvoice = $this->configHelper->getStatusForCreateInvoiceAfterUpdateStatus();
                    if ($order->getState() == $statusForAutoCreateInvoice || $order->getStatus() == $statusForAutoCreateInvoice) {
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
                        }
                    }
                }
            }
        }
        return $this;
    }
}
