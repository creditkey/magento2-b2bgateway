<?php
namespace CreditKey\B2BGateway\Helper;

use \Psr\Log\LoggerInterface;

/**
 * Data Helper
 */
class Data
{
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /** @var $connection */
    private $connection;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * Data constructor.
     * @param LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     */
    public function __construct(LoggerInterface $logger,
                                \Magento\Framework\App\ResourceConnection $resource,
                                \Magento\Sales\Model\Service\InvoiceService $invoiceService,
                                \Magento\Framework\DB\TransactionFactory $transactionFactory)
    {
        $this->logger = $logger;
        $this->connection = $resource->getConnection();
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * Return a collection of \CreditKey\Models\CartItem objects from a Quote or Order object
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
     * @return \CreditKey\Models\Charges
     */
    public function buildCharges($holder)
    {
        $grandTotal = (float)$holder->getGrandTotal();
        return $this->buildChargesWithUpdatedGrandTotal($holder, $grandTotal);
    }

    /**
     * Return a \CreditKey\Models\Charges objects from a Quote or Order object, but with an updated grand total amount.
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
     * @param $ckOrderId
     * @return |null
     */
    public function getOrderIdByCkId($ckOrderId)
    {
        $sopTable = $this->connection->getTableName('sales_order_payment');

        try {
            $searchQuery = "SELECT parent_id AS order_id, JSON_UNQUOTE(JSON_EXTRACT(`additional_information`,'$.ckOrderId'))
                            AS ckOrderId FROM  sales_order_payment HAVING ckOrderId='{$ckOrderId}';";
            $orderData = $this->connection->fetchRow($searchQuery);

            if($orderData && isset($orderData['order_id'])) {
                return $orderData['order_id'];
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
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
