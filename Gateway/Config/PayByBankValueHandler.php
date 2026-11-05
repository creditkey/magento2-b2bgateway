<?php
namespace CreditKey\B2BGateway\Gateway\Config;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

/** Inherit Credit Key settings at the requested store scope. */
class PayByBankValueHandler implements ValueHandlerInterface
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function handle(array $subject, $storeId = null)
    {
        $field = SubjectReader::readField($subject);
        switch ($field) {
            case 'active':
                return (bool) $this->config->getValue('active', $storeId)
                    && (bool) $this->config->getValue('pay_by_bank_enabled', $storeId);
            case 'title':
                return __('Pay by Bank');
            case 'sort_order':
                return (int) $this->config->getValue('sort_order', $storeId) + 1;
            case 'can_use_internal':
                return false;
            default:
                return $this->config->getValue($field, $storeId);
        }
    }
}
