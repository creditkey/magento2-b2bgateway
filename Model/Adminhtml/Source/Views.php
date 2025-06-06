<?php
namespace CreditKey\B2BGateway\Model\Adminhtml\Source;

class Views implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => "left", 'label' => 'Left'],
            ['value' => "right", 'label' => 'Right'],
            ['value' => "center", 'label' => 'Centered']
        ];

        return $options;
    }
}
