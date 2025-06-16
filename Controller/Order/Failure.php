<?php

declare(strict_types=1);

namespace CreditKey\B2BGateway\Controller\Order;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Cancel order controller
 */
class Failure implements HttpGetActionInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    public function __construct(ResultFactory $resultFactory)
    {
        $this->resultFactory = $resultFactory;
    }

    public function execute(): ResultInterface
    {
        return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
    }
}
