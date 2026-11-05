<?php
namespace CreditKey\B2BGateway\Model;

use CreditKey\B2BGateway\Model\Ui\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;

/** Resolve the checkout payload only from the server-side quote payment. */
class CheckoutMethod
{
    public function getMerchantData($quote, $cartContents, $customerId)
    {
        $payment = $quote->getPayment();
        $code = $payment->getMethod();
        if (!in_array($code, [ConfigProvider::CODE, ConfigProvider::BANK_CODE], true)
            || !$payment->getMethodInstance()->isAvailable($quote)
            || !$payment->getMethodInstance()->canUseForCountry($quote->getBillingAddress()->getCountryId())
            || !\CreditKey\Checkout::isDisplayedInCheckout($cartContents, $customerId)) {
            throw new LocalizedException(__('Credit Key is not available for this checkout.'));
        }
        return $code === ConfigProvider::BANK_CODE ? ['pay_now' => true] : null;
    }
}
