<?php

declare(strict_types=1);

namespace CreditKey\B2BGateway\Observer;

use CreditKey\B2BGateway\Model\Ui\ConfigProvider;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RestrictPaymentMethod implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $result = $observer->getData('result');
        $methodInstance = $observer->getData('method_instance');

        if (ConfigProvider::CODE === $methodInstance->getCode()) {
            $result->setData('is_available', false);
        }
    }
}
