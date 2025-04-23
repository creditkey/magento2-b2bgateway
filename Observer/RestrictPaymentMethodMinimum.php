<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace CreditKey\B2BGateway\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;

class RestrictPaymentMethodMinimum implements ObserverInterface
{
    /**
     * @var \CreditKey\B2BGateway\Helper\Config
     */
    protected $_configHelper;

    /**
     * RestrictPaymentMethodMinimum constructor.
     *
     * @param \CreditKey\B2BGateway\Helper\Config $configHelper
     */
    public function __construct(\CreditKey\B2BGateway\Helper\Config $configHelper)
    {
        $this->_configHelper = $configHelper;
    }

    /**
     * Observer execution
     *
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $event = $observer->getEvent();
        $methodInstance = $event->getMethodInstance();

        if ($methodInstance->getCode() == \CreditKey\B2BGateway\Model\Ui\ConfigProvider::CODE) {
            $quote = $event->getQuote();
            $grandTotal = $quote->getGrandTotal();
            $checkoutMinPrice = abs($this->_configHelper->getCheckoutMinPrice());

            if (is_numeric($checkoutMinPrice) && $checkoutMinPrice != 0 && $grandTotal < $checkoutMinPrice) {
                $result = $observer->getEvent()->getResult();
                $result->setData('is_available', false);
            }
        }
    }
}
