<?php
namespace CreditKey\B2BGateway\Test\Unit;

use CreditKey\B2BGateway\Model\CheckoutMethod;
use CreditKey\B2BGateway\Model\Ui\ConfigProvider;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CheckoutMethodTest extends TestCase
{
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

    /**
     * @dataProvider methods
     */
    #[DataProvider('methods')]
    public function testOnlyBankCheckoutAddsBooleanPayNow($code, $expected): void
    {
        $checkoutMethod = $this->getMockBuilder(CheckoutMethod::class)
            ->onlyMethods(['isDisplayedInCheckout'])
            ->getMock();
        $checkoutMethod->expects($this->once())
            ->method('isDisplayedInCheckout')
            ->with(['cart'], 'customer')
            ->willReturn(true);
        $this->assertSame($expected, $checkoutMethod->getMerchantData($this->quote($code), ['cart'], 'customer'));
    }

    public static function codes(): array
    {
        return [[ConfigProvider::CODE], [ConfigProvider::BANK_CODE]];
    }

    /**
     * @dataProvider codes
     */
    #[DataProvider('codes')]
    public function testEligibilityDenialBlocksBothMethods($code): void
    {
        $checkoutMethod = $this->getMockBuilder(CheckoutMethod::class)
            ->onlyMethods(['isDisplayedInCheckout'])
            ->getMock();
        $checkoutMethod->expects($this->once())->method('isDisplayedInCheckout')->willReturn(false);
        $this->expectException(LocalizedException::class);
        $checkoutMethod->getMerchantData($this->quote($code), [], null);
    }

    /**
     * @dataProvider codes
     */
    #[DataProvider('codes')]
    public function testUnavailableMethodCannotStartCheckout($code): void
    {
        $this->expectException(LocalizedException::class);
        (new CheckoutMethod())->getMerchantData($this->quote($code, false), [], null);
    }

    /**
     * @dataProvider codes
     */
    #[DataProvider('codes')]
    public function testCountryRestrictionBlocksBothMethods($code): void
    {
        $this->expectException(LocalizedException::class);
        (new CheckoutMethod())->getMerchantData($this->quote($code, true, false), [], null);
    }
}
