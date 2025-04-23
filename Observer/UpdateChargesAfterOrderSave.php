<?php

declare(strict_types=1);

namespace CreditKey\B2BGateway\Observer;

use CreditKey\B2BGateway\Helper\Api;
use CreditKey\B2BGateway\Helper\Data;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class UpdateChargesAfterOrderSave implements ObserverInterface
{
    /**
     * @var Data
     */
    private $creditKeyData;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Data $creditKeyData
     * @param Api $api
     * @param LoggerInterface $logger
     */
    public function __construct(
        Data $creditKeyData,
        Api $api,
        LoggerInterface $logger
    ) {
        $this->creditKeyData = $creditKeyData;
        $this->api = $api;
        $this->logger = $logger;
    }

    /**
     * Observer execution
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getData('order');
        $payment = $order->getPayment();
        $paymentMethod = $order->getPayment()->getMethod();
        $ckOrderId = $payment->getAdditionalInformation('ckOrderId');

        if (!in_array($paymentMethod, ['creditkey_gateway', 'creditkey_gateway_admin']) || !$ckOrderId) {
            return;
        }

        $this->api->configure();
        $cartContents = $this->creditKeyData->buildCartContents($order);
        $charges = $this->creditKeyData->buildChargesWithUpdatedGrandTotal(
            $order,
            $order->getGrandTotal()
        );

        try {
            \CreditKey\Orders::update(
                $ckOrderId,
                $order->getState(),
                $order->getIncrementId(),
                $cartContents,
                $charges,
                null
            );
        } catch (\Exception $exception) {
            $this->logger->info('Error when update charges');
            $this->logger->critical($exception->getMessage());
        }
    }
}
