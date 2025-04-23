<?php
namespace CreditKey\B2BGateway\Gateway\Command;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use \Psr\Log\LoggerInterface;

class AuthorizeCommand implements CommandInterface
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
        $paymentDO = $commandSubject['payment'];
        $payment = $paymentDO->getPayment();

        $this->logger->debug('Authorizing Credit Key Payment: ' . $payment->getId());

        return null;
    }
}
