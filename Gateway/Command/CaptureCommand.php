<?php
namespace CreditKey\B2BGateway\Gateway\Command;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;

/**
 * Capture Payment Command
 */
class CaptureCommand implements CommandInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \CreditKey\B2BGateway\Helper\Api
     */
    private $creditKeyApi;

    /**
     * @var \CreditKey\B2BGateway\Helper\Data
     */
    private $creditKeyData;

    /**
     * Construct
     *
     * @param \Psr\Log\LoggerInterface          $logger
     * @param \CreditKey\B2BGateway\Helper\Api  $creditKeyApi
     * @param \CreditKey\B2BGateway\Helper\Data $creditKeyData
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \CreditKey\B2BGateway\Helper\Api $creditKeyApi,
        \CreditKey\B2BGateway\Helper\Data $creditKeyData
    ) {
        $this->logger = $logger;
        $this->creditKeyApi = $creditKeyApi;
        $this->creditKeyData = $creditKeyData;
    }

    /**
     * Executes command basing on business object
     *
     * @param  array $commandSubject
     * @return null|Command\ResultInterface
     * @throws CommandException
     */
    public function execute(array $commandSubject)
    {
        $paymentDO = $commandSubject['payment'];
        $captureAmount = $commandSubject['amount'];
        $payment = $paymentDO->getPayment();
        $ckOrderId = $payment->getAdditionalInformation('ckOrderId');

        $this->logger->debug('Capture Credit Key Payment: ' . $ckOrderId . ': ' . $captureAmount);

        $order = $payment->getOrder();
        $merchantOrderId = $order->getIncrementId();
        $merchantOrderStatus = $order->getStatus();
        $cartContents = $this->creditKeyData->buildCartContents($order);
        $charges = $this->creditKeyData->buildChargesWithUpdatedGrandTotal($order, $captureAmount);

        $this->creditKeyApi->configure();
        $ckOrder = \CreditKey\Orders::confirm(
            $ckOrderId,
            $merchantOrderId,
            $merchantOrderStatus,
            $cartContents,
            $charges
        );

        $payment->setParentTransactionId($payment->getLastTransId());
        $payment->setTransactionId($ckOrder->getOrderId());
        $payment->setShouldCloseParentTransaction(true);

        return null;
    }
}
