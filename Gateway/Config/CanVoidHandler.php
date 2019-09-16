<?php
namespace CreditKey\B2BGateway\Gateway\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Can Void Handler
 */
class CanVoidHandler implements ValueHandlerInterface
{
    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $subject, $storeId = null)
    {
        $paymentDO = $subject['payment'];
        $payment = $paymentDO->getPayment();
        return $payment instanceof Payment && !(bool)$payment->getAmountPaid();
    }
}
