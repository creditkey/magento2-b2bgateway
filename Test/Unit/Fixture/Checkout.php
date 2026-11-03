<?php
namespace CreditKey;

// Loaded only in isolated unit-test processes; never included by module autoloading.
final class Checkout
{
    public static $eligible = true;
    public static $calls = [];

    public static function isDisplayedInCheckout($cart, $customer)
    {
        self::$calls[] = [$cart, $customer];
        return self::$eligible;
    }
}
