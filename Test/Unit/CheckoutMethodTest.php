<?php
use CreditKey\B2BGateway\Model\CheckoutMethod;
use CreditKey\B2BGateway\Model\Ui\ConfigProvider;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class CheckoutMethodTest extends TestCase
{
    protected function setUp(): void
    {
        require __DIR__ . '/Fixture/Checkout.php';
    }

    private function quote($code, $available = true, $countryAllowed = true): DataObject
    {
        $method = new class($available, $countryAllowed) {
            public function __construct(private bool $available, private bool $countryAllowed) {}
            public function isAvailable($quote) { return $this->available; }
            public function canUseForCountry($country) { return $country === 'US' && $this->countryAllowed; }
        };
        return new DataObject([
            'payment' => new DataObject(['method' => $code, 'method_instance' => $method]),
            'billing_address' => new DataObject(['country_id' => 'US'])
        ]);
    }

    public static function methods(): array
    {
        return [[ConfigProvider::CODE, null], [ConfigProvider::BANK_CODE, ['pay_now' => true]]];
    }

    #[DataProvider('methods')]
    public function testOnlyBankCheckoutAddsBooleanPayNow($code, $expected): void
    {
        $this->assertSame($expected, (new CheckoutMethod())->getMerchantData($this->quote($code), ['cart'], 'customer'));
        $this->assertSame([[['cart'], 'customer']], \CreditKey\Checkout::$calls);
    }

    public static function codes(): array
    {
        return [[ConfigProvider::CODE], [ConfigProvider::BANK_CODE]];
    }

    #[DataProvider('codes')]
    public function testEligibilityDenialBlocksBothMethods($code): void
    {
        \CreditKey\Checkout::$eligible = false;
        $this->expectException(LocalizedException::class);
        (new CheckoutMethod())->getMerchantData($this->quote($code), [], null);
    }

    #[DataProvider('codes')]
    public function testUnavailableMethodCannotStartCheckout($code): void
    {
        $this->expectException(LocalizedException::class);
        (new CheckoutMethod())->getMerchantData($this->quote($code, false), [], null);
    }

    #[DataProvider('codes')]
    public function testCountryRestrictionBlocksBothMethods($code): void
    {
        $this->expectException(LocalizedException::class);
        (new CheckoutMethod())->getMerchantData($this->quote($code, true, false), [], null);
    }
}
