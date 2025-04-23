<?php
namespace CreditKey\B2BGateway\Gateway\Command;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order;
use \Psr\Log\LoggerInterface;

class InitializeCommand implements CommandInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Construct
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        $stateObject = $commandSubject['stateObject'];
        $paymentDO = $commandSubject['payment'];
        $payment = $paymentDO->getPayment();

        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $payment->authorize(true, $payment->getAmountOrdered());

        $stateObject->setData('state', Order::STATE_PENDING_PAYMENT);
        $stateObject->setData('status', Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);

        return null;
    }
}
