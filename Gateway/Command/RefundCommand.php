<?php
namespace CreditKey\B2BGateway\Gateway\Command;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;

class RefundCommand implements CommandInterface
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
     * Construct
     *
     * @param \Psr\Log\LoggerInterface         $logger
     * @param \CreditKey\B2BGateway\Helper\Api $creditKeyApi
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \CreditKey\B2BGateway\Helper\Api $creditKeyApi
    ) {
        $this->logger = $logger;
        $this->creditKeyApi = $creditKeyApi;
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
        $refundAmount = $commandSubject['amount'];

        $payment = $paymentDO->getPayment();
        $ckOrderId = $payment->getAdditionalInformation('ckOrderId');

        $this->logger->debug('Refund Credit Key Payment: ' . $ckOrderId . ': ' . $refundAmount);

        $this->creditKeyApi->configure();
        $ckOrder = \CreditKey\Orders::refund($ckOrderId, $refundAmount);

        return null;
    }
}
