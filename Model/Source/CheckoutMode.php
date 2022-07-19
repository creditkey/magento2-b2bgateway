<?php

namespace CreditKey\B2BGateway\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CheckoutMode implements OptionSourceInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'modal',
                'label' => __('Modal')
            ],
            [
                'value' => 'redirect',
                'label' => __('Redirect')
            ]
        ];
    }
}
