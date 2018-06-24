<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Creditkey\B2BGateway\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Creditkey\B2BGateway\Gateway\Http\Client\ClientMock;
use \Psr\Log\LoggerInterface;

class AuthorizationDataRequest implements BuilderInterface
{
    const FORCE_RESULT = 'FORCE_RESULT';

    protected $logger;

    public function __construct(
      LoggerInterface $logger
    ) {
      $this->logger = $logger;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();

        $transactionResult = $payment->getAdditionalInformation('transaction_result');
        return [
            self::FORCE_RESULT => $transactionResult === null
                ? ClientMock::SUCCESS
                : $transactionResult
        ];
    }
}
