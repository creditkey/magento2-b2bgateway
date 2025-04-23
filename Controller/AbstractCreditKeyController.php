<?php
namespace CreditKey\B2BGateway\Controller;

use Magento\Checkout\Controller\Express\RedirectLoginInterface;
use Magento\Framework\App\Action\Action as AppAction;
use Magento\Store\Model\ScopeInterface;

/**
 * Abstract CreditKey Controller Class
 */
abstract class AbstractCreditKeyController extends AppAction implements RedirectLoginInterface
{
    /**
     * @var \CreditKey\B2BGateway\Helper\Api
     */
    protected $creditKeyApi;

    /**
     * @var \CreditKey\B2BGateway\Helper\Data
     */
    protected $creditKeyData;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $customerUrl;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \CreditKey\B2BGateway\Helper\Api      $creditKeyApi
     * @param \CreditKey\B2BGateway\Helper\Data     $creditKeyData
     * @param \Magento\Customer\Model\Url           $customerUrl
     * @param \Magento\Checkout\Model\Session       $checkoutSession
     * @param \Magento\Customer\Model\Session       $customerSession
     * @param \Psr\Log\LoggerInterface              $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CreditKey\B2BGateway\Helper\Api $creditKeyApi,
        \CreditKey\B2BGateway\Helper\Data $creditKeyData,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->creditKeyApi = $creditKeyApi;
        $this->creditKeyData = $creditKeyData;
        $this->customerUrl = $customerUrl;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Returns a list of action flags [flag_key] => boolean
     *
     * @return array
     */
    public function getActionFlagList()
    {
        return [];
    }

    /**
     * Returns before_auth_url redirect parameter for customer session
     *
     * @return void
     */
    public function getCustomerBeforeAuthUrl()
    {
        return;
    }

    /**
     * Get Login URL
     *
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->customerUrl->getLoginUrl();
    }

    /**
     * Get Redirect Action Name
     *
     * @return string
     */
    public function getRedirectActionName()
    {
        return 'start';
    }
}
