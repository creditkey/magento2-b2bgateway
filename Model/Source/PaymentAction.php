<?php

namespace CreditKey\B2BGateway\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Payment method settings: Payment actions
 */
class PaymentAction implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'authorize',
                'label' => __('Authorize'),
            ],
            [
                'value' => 'authorize_capture',
                'label' => __('Authorize and Capture'),
            ],
        ];
    }
}
