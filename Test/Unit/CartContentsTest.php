<?php
use CreditKey\B2BGateway\Helper\Data;
use CreditKey\B2BGateway\Model\ResourceModel\OrderPayment;
use Magento\Framework\DataObject;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Service\InvoiceService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CartContentsTest extends TestCase
{
    public function testCartItemsCarryTheCatalogProductIdNotTheLineItemId(): void
    {
        $holder = new DataObject(['all_visible_items' => [
            new DataObject(['item_id' => 901, 'product_id' => 15, 'name' => 'Desk', 'price' => '120.50', 'sku' => 'DSK', 'qty' => 2]),
            new DataObject(['item_id' => 902, 'product_id' => 42, 'name' => 'Chair', 'price' => '49.00', 'sku' => 'CHR', 'qty' => 3]),
        ]]);
        $helper = new Data(
            $this->createStub(LoggerInterface::class),
            $this->createStub(OrderPayment::class),
            $this->createStub(InvoiceService::class),
            $this->createStub(TransactionFactory::class)
        );

        $items = $helper->buildCartContents($holder);

        $this->assertSame([15, 42], array_map(fn ($item) => $item->getMerchantId(), $items));
        $this->assertSame([15, 42], array_column(array_map(fn ($item) => $item->toFormData(), $items), 'merchant_id'));
        $this->assertSame(
            388.0,
            array_sum(array_map(fn ($item) => $item->getPrice() * $item->getQuantity(), $items))
        );
    }
}
