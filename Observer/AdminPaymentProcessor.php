<?php

namespace CreditKey\B2BGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AdminPaymentProcessor
 * @package CreditKey\B2BGateway\Observer
 */
class AdminPaymentProcessor implements ObserverInterface
{
    /** @var \CreditKey\B2BGateway\Helper\Api */
    protected $apiHelper;

    /** @var \Magento\Framework\Url */
    protected $urlBuilder;

    /**
     * AdminPaymentProcessor constructor.
     * @param \CreditKey\B2BGateway\Helper\Api $apiHelper
     * @param \Magento\Framework\Url $urlBuilder
     */
    public function __construct(
        \CreditKey\B2BGateway\Helper\Api $apiHelper,
        \Magento\Framework\Url $urlBuilder
    ) {
        $this->apiHelper = $apiHelper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param Observer $observer
     * @return null|void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        $paymentInstance = $order->getPayment()->getMethodInstance();

        // Check code payment method
        if ($paymentInstance->getCode() == 'creditkey_gateway_admin') {

            $formData = [];
            $formData['remote_id'] = $order->getIncrementId();
            $formData['remote_customer_id'] = $order->getCustomerId();
            $formData['cart_items'] = [];
            $formData['billing_address'] = [];
            $formData['shipping_address'] = [];
            $formData['charges'] = [];

            $formData['return_url'] = $this->urlBuilder->getUrl(
                'creditkey_gateway/order/admincomplete',
                [
                    'id' => 'CKKEY',
                    '_secure' => true
                ]
            );

            $formData['cancel_url'] = $this->urlBuilder->getUrl('checkout');
            $formData['order_complete_url'] = $this->urlBuilder->getUrl(
                'creditkey_gateway/order/admincomplete',
                [
                    'id' => 'CKKEY',
                    '_secure' => true
                ]
            );

            $formData['return_url'] = str_replace('CKKEY', '%CKKEY%', $formData['return_url']);
            $formData['order_complete_url'] = str_replace('CKKEY', '%CKKEY%', $formData['order_complete_url']);
            $formData['merchant_data'] = [];
            $formData['mode'] = 'link';

            $orderItems = $order->getAllVisibleItems();
            foreach ($orderItems as $orderItem) {
                $cartItem = [];
                $cartItem['merchant_id'] = $orderItem->getProductId();
                $cartItem['name'] = $orderItem->getName();
                $cartItem['price'] = $orderItem->getPrice();
                $cartItem['quantity'] = $orderItem->getQty();
                $cartItem['sku'] = $orderItem->getSku();
                $formData['cart_items'][] = $cartItem;
            }

            $billingAddress = $order->getBillingAddress();
            $address = [];
            $address['first_name'] = $billingAddress->getFirstname();
            $address['last_name'] = $billingAddress->getLastname();
            $address['company_name'] = $billingAddress->getCompany();
            $address['email'] = $billingAddress->getEmail();
            $address['address1'] = @$billingAddress->getStreet(1)[0];
            $address['address2'] = @$billingAddress->getStreet(1)[1];
            $address['city'] = $billingAddress->getCity();
            $address['state'] = $billingAddress->getRegion();
            $address['zip'] = $billingAddress->getPostcode();
            $address['phone_number'] = $billingAddress->getTelephone();
            $formData['billing_address'] = $address;

            $shippingAddress = $order->getShippingAddress();
            $address = [];
            $address['first_name'] = $shippingAddress->getFirstname();
            $address['last_name'] = $shippingAddress->getLastname();
            $address['company_name'] = $shippingAddress->getCompany();
            $address['email'] = $shippingAddress->getEmail();
            $address['address1'] = @$shippingAddress->getStreet(1)[0];
            $address['address2'] = @$shippingAddress->getStreet(1)[1];
            $address['city'] = $shippingAddress->getCity();
            $address['state'] = $shippingAddress->getRegion();
            $address['zip'] = $shippingAddress->getPostcode();
            $address['phone_number'] = $shippingAddress->getTelephone();
            $formData['shipping_address'] = $address;

            $charge = [];
            $charge['total'] = $order->getSubtotal();
            $charge['shipping'] = $order->getShippingAmount();
            $charge['tax'] = $order->getTaxAmount();
            $charge['discount_amount'] = $order->getDiscountAmount();
            $charge['grand_total'] = $order->getGrandTotal();
            $formData['charges'] = $charge;

            $merchantData = [];
            $merchantData['contact_method'] = 'email';
            $merchantData['cc_rep'] = 'true';
            $merchantData['csr'] = 'customer_service_rep@example.org';
            $merchantData['response'] = 'async';
            $formData['merchant_data'] = $merchantData;

            $this->apiHelper->configure();
            $result = \CreditKey\Api::post('/ecomm/begin_standalone_checkout', $formData);

            $payment->setAdditionalInformation("ckOrderId", $result->id);
            $payment->setAdditionalInformation("ckCheckoutUrl", $result->checkout_url);
            $payment->save();
            $order->addStatusHistoryComment(__('Credit Key Payment created with ID : ' . $result->id))->save();
            $order->addStatusHistoryComment(__('Payment URL : ' . $result->checkout_url))->save();
            //$order->addStatusHistoryComment(__('COMPLETE URL : ' . $formData['order_complete_url']))->save();
            //$order->addStatusHistoryComment(__('RETURN URL : ' . $formData['return_url']))->save();
        }
    }
}
