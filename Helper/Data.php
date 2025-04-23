<?php
namespace CreditKey\B2BGateway\Helper;

use CreditKey\B2BGateway\Model\ResourceModel\OrderPayment;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use \Psr\Log\LoggerInterface;

/**
 * Data Helper
 */
class Data
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderPayment
     */
    private $orderPaymentResource;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * Data constructor.
     *
     * @param LoggerInterface $logger
     * @param OrderPayment $orderPaymentResource
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        OrderPayment $orderPaymentResource,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory
    ) {
        $this->logger = $logger;
        $this->orderPaymentResource = $orderPaymentResource;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * Return a collection of \CreditKey\Models\CartItem objects from a Quote or Order object
     *
     * @param AbstractModel $holder
     * @return \CreditKey\Models\CartItem[]
     */
    public function buildCartContents($holder)
    {
        $cartContents = [];
        foreach ($holder->getAllVisibleItems() as $item) {
            $productId = (int)$item->getItemId();
            $name = $item->getName();
            $price = (float)$item->getPrice();
            $sku = $item->getSku();
            $quantity = (int)$item->getQty();

            array_push(
                $cartContents,
                new \CreditKey\Models\CartItem($productId, $name, $price, $sku, $quantity, null, null)
            );
        }

        return $cartContents;
    }

    /**
     * Return a \CreditKey\Models\Address from a Magento Address object
     *
     * @param Address $magentoAddress
     * @return \CreditKey\Models\Address
     */
    public function buildAddress($magentoAddress)
    {
        $street = $magentoAddress->getStreet();
        $address1 = null;
        $address2 = null;

        if (count($street) >= 1) {
            $address1 = $street[0];
        }
        if (count($street) >= 2) {
            $address2 = $street[1];
        }

        return new \CreditKey\Models\Address(
            $magentoAddress->getFirstname(),
            $magentoAddress->getLastname(),
            $magentoAddress->getCompany(),
            $magentoAddress->getEmail(),
            $address1,
            $address2,
            $magentoAddress->getCity(),
            $magentoAddress->getRegionCode(),
            $magentoAddress->getPostcode(),
            $magentoAddress->getTelephone()
        );
    }

    /**
     * Return a \CreditKey\Models\Charges objects from a Quote or Order object
     *
     * @param AbstractModel $holder
     * @return \CreditKey\Models\Charges
     */
    public function buildCharges($holder)
    {
        $grandTotal = (float)$holder->getGrandTotal();
        return $this->buildChargesWithUpdatedGrandTotal($holder, $grandTotal);
    }

    /**
     * Return a \CreditKey\Models\Charges objects from a Quote or Order object, but with an updated grand total amount.
     *
     * @param AbstractModel $holder
     * @param float $updatedGrandTotal
     * @return \CreditKey\Models\Charges
     */
    public function buildChargesWithUpdatedGrandTotal($holder, $updatedGrandTotal)
    {
        $total = (float)$holder->getSubtotal();

        $shippingAmount = $holder->getShippingAmount() == null
          ? (float)0
          : (float)$holder->getShippingAmount();

        if ($shippingAmount == 0) {
            $shippingAmount = $holder->getShippingAddress() == null
              ? (float)0
              : (float)$holder->getShippingAddress()->getShippingAmount();
        }

        $tax = $holder->getBillingAddress() == null
          ? (float)0
          : (float)$holder->getBillingAddress()->getTaxAmount();

        if ($tax == 0) {
            $tax = $holder->getShippingAddress() == null
              ? (float)0
              : (float)$holder->getShippingAddress()->getTaxAmount();
        }

        if ($tax == 0) {
            $tax = $holder->getTaxAmount();
        }

        $discount = $holder->getSubtotalWithDiscount() == null
          ? (float)0
          : (float)$holder->getSubtotal() - $holder->getSubtotalWithDiscount();

        if ($discount == 0) {
            $discount = $holder->getDiscountAmount() == null
              ? (float)0
              : (float)abs($holder->getDiscountAmount());
        }

        return new \CreditKey\Models\Charges($total, $shippingAmount, $tax, $discount, $updatedGrandTotal);
    }

    /**
     * Get order ID by CreditKey ID
     *
     * @param string $ckOrderId
     * @return string
     */
    public function getOrderIdByCkId($ckOrderId)
    {
        return $this->orderPaymentResource->getOrderIdByCkId($ckOrderId);
    }

    /**
     * Capture and create invoice for order
     *
     * @param Order $order
     */
    public function captureAndCreateInvoice(Order $order)
    {
        // prepare invoice and generate it
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->register();

        /**
 * @var \Magento\Framework\DB\Transaction $transaction
*/
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($order)
            ->addObject($invoice)
            ->addObject($invoice->getOrder())->save();
    }
}
