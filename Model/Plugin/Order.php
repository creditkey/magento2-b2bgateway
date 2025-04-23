<?php
/**
 * @package   CreditKey/B2bGateWay
 * @copyright Copyright © 2021 CoreDevelopment LLC. All Rights Reserved. *
 */
declare(strict_types=1);

namespace CreditKey\B2BGateway\Model\Plugin;

use CreditKey\B2BGateway\Helper\Data;
use Psr\Log\LoggerInterface;
use CreditKey\B2BGateway\Helper\Api;

class Order
{
    /**
     * @var \CreditKey\B2BGateway\Helper\Api
     */
    private $api;
    /**
     * @var
     */
    private $logger;
    /**
     * @var Data
     */
    private $creditKeyData;

    /**
     * @param Api             $api
     * @param Data            $data
     * @param LoggerInterface $logger
     */
    public function __construct(
        Api             $api,
        Data            $data,
        LoggerInterface $logger
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->creditKeyData = $data;
    }

    /**
     * @param  \Magento\Sales\Model\Order $subject
     * @param  $result
     * @return mixed
     */
    public function afterAfterSave(\Magento\Sales\Model\Order $subject, $result)
    {
        try {
            $payment = $subject->getPayment();
            $status = ['complete', 'shipped'];
            if ($payment && $payment->getMethod() == \CreditKey\B2BGateway\Model\Ui\ConfigProvider::CODE
                && $payment->getAdditionalInformation('ckOrderId')
                && in_array(
                    $subject->getStatus(),
                    $status
                )
            ) {
                $ckOrderId = $payment->getAdditionalInformation('ckOrderId');
                $this->api->configure();
                $cartContents = $this->creditKeyData->buildCartContents($subject);
                $charges = $this->creditKeyData->buildChargesWithUpdatedGrandTotal(
                    $subject,
                    $subject->getGrandTotal()
                );
                $ckOrder = \CreditKey\Orders::confirm(
                    $payment->getAdditionalInformation('ckOrderId'),
                    $subject->getIncrementId(),
                    'shipped',
                    $cartContents,
                    $charges,
                    null
                );
                $this->logger->info(__('Change status for %1 order to shipped successfully', $ckOrderId));
            }
        } catch (\CreditKey\Exceptions\NotFoundException $notFoundException) {
            $this->logger->info('Not found order ' . $ckOrderId);
        } catch (\Exception $exception) {
            $this->logger->info('Error when update status');
            $this->logger->critical($exception->getMessage());
        }
        return $result;
    }
}
