<?php
namespace CreditKey\B2BGateway\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class PayByBank extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setIsChecked((bool) $element->getValue());
        $element->setValue('1');
        // Submit zero when unchecked. Magento handles scope inheritance separately.
        $hidden = '<input type="hidden" name="' . $this->escapeHtmlAttr($element->getName())
            . '" value="0" />';
        return $hidden . $element->getElementHtml();
    }
}
