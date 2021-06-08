<?php
namespace CreditKey\B2BGateway\Controller\Order;

/**
 * Cancel order controller
 */
class Cancel extends \CreditKey\B2BGateway\Controller\AbstractCreditKeyController
{
    /**
     * Execute the cancel action
     *
     * @return $this
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('checkout');
        return $resultRedirect;
    }
}
