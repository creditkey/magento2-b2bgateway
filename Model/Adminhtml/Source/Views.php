<?php
namespace CreditKey\B2BGateway\Model\Adminhtml\Source;

/**
 * Class Views
 * @package CreditKey\B2BGateway\Model\Adminhtml\Source
 */
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
            ['value' => "centered", 'label' => 'Centered']
        ];

        return $options;
    }
}
