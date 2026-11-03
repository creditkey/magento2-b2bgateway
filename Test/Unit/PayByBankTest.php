<?php
use CreditKey\B2BGateway\Gateway\Config\PayByBankValueHandler;
use CreditKey\B2BGateway\Helper\Config;
use CreditKey\B2BGateway\Model\CheckoutMethod;
use CreditKey\B2BGateway\Model\Ui\ConfigProvider;
use CreditKey\B2BGateway\Observer\RestrictPaymentMethodMinimum;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\ConfigInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PayByBankTest extends TestCase
{
    public static function activeCases(): array
    {
        return [[0, 0, false], [0, 1, false], [1, 0, false], [1, 1, true]];
    }

    #[DataProvider('activeCases')]
    public function testBankRequiresBothTogglesAtTheRequestedStore($active, $enabled, $expected): void
    {
        $config = $this->createStub(ConfigInterface::class);
        $config->method('getValue')->willReturnCallback(function ($field, $store) use ($active, $enabled) {
            $this->assertSame(7, $store);
            return ['active' => $active, 'pay_by_bank_enabled' => $enabled][$field];
        });
        $handler = new PayByBankValueHandler($config);
        $this->assertSame($expected, $handler->handle(['field' => 'active'], 7));
    }

    public function testBankInheritsGatewaySettingsAndHasItsOwnTitleAndSortOrder(): void
    {
        $config = $this->createStub(ConfigInterface::class);
        $config->method('getValue')->willReturnCallback(function ($field, $store) {
            $this->assertSame(7, $store);
            return ['payment_action' => 'authorize_capture', 'sort_order' => 10,
                'can_capture' => 1, 'can_refund' => 1, 'specificcountry' => 'US'][$field];
        });
        $handler = new PayByBankValueHandler($config);
        foreach (['payment_action' => 'authorize_capture', 'can_capture' => 1,
            'can_refund' => 1, 'specificcountry' => 'US', 'sort_order' => 11,
            'title' => 'Pay by Bank', 'can_use_internal' => false] as $field => $expected) {
            $actual = $handler->handle(['field' => $field], 7);
            $this->assertSame($expected, $field === 'title' ? (string) $actual : $actual);
        }
    }

    public static function minimumCases(): array
    {
        $cases = [];
        foreach ([ConfigProvider::CODE, ConfigProvider::BANK_CODE] as $code) {
            foreach ([[99, 100, false], [100, 100, true], [101, 100, true], [1, 0, true]] as $case) {
                $cases[] = array_merge([$code], $case);
            }
        }
        $cases[] = ['checkmo', 1, 100, true];
        return $cases;
    }

    #[DataProvider('minimumCases')]
    public function testBothMethodsShareMinimumAmount($code, $total, $minimum, $expected): void
    {
        $config = $this->createStub(Config::class);
        $config->method('getCheckoutMinPrice')->willReturn($minimum);
        $result = new DataObject(['is_available' => true]);
        $event = new Event(['method_instance' => new DataObject(['code' => $code]),
            'quote' => new DataObject(['grand_total' => $total]), 'result' => $result]);
        (new RestrictPaymentMethodMinimum($config))->execute(new Observer(['event' => $event]));
        $this->assertSame($expected, $result->getData('is_available'));
    }

    public function testMissingQuoteDoesNotCauseAnError(): void
    {
        $config = $this->createStub(Config::class);
        $result = new DataObject(['is_available' => true]);
        $event = new Event(['method_instance' => new DataObject(['code' => ConfigProvider::BANK_CODE]),
            'quote' => null, 'result' => $result]);
        (new RestrictPaymentMethodMinimum($config))->execute(new Observer(['event' => $event]));
        $this->assertTrue($result->getData('is_available'));
    }

    public function testUnknownPaymentMethodCannotStartCreditKeyCheckout(): void
    {
        $quote = new DataObject(['payment' => new DataObject(['method' => 'checkmo'])]);
        $this->expectException(LocalizedException::class);
        (new CheckoutMethod())->getMerchantData($quote, [], null);
    }
}
