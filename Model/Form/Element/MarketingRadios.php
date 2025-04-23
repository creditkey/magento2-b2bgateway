<?php
namespace CreditKey\B2BGateway\Model\Form\Element;

use Magento\Framework\Escaper;

/**
 * MarketingRadios Form Element
 */
class MarketingRadios extends \Magento\Framework\Data\Form\Element\Radios
{
    /**
     * Config Helper
     *
     * @var \CreditKey\B2BGateway\Helper\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $json;

    /**
     * @var \CreditKey\B2BGateway\Helper\Api
     */
    private $creditKeyApi;

    /**
     * @param \Magento\Framework\Data\Form\Element\Factory           $factoryElement
     * @param \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection
     * @param Escaper                                                $escaper
     * @param \CreditKey\B2BGateway\Helper\Config                    $config
     * @param \Magento\Framework\Serialize\SerializerInterface       $json
     * @param \CreditKey\B2BGateway\Helper\Api                       $creditKeyApi
     * @param array                                                  $data
     */
    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        Escaper $escaper,
        \CreditKey\B2BGateway\Helper\Config $config,
        \Magento\Framework\Serialize\SerializerInterface $json,
        \CreditKey\B2BGateway\Helper\Api $creditKeyApi,
        $data = []
    ) {
        $this->config = $config;
        $this->json = $json;
        $this->creditKeyApi = $creditKeyApi;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->setType('radios');
    }

    /**
     * @return string
     */
    public function getElementHtml()
    {
        $html = '';
        $value = $this->getValue();
        if ($values = $this->getValues()) {
            foreach ($values as $option) {
                $html .= $this->_optionToHtml($option, $value);
            }
        }
        $html .= $this->getAfterElementHtml();

        // initialize our js component
        $id = $this->getHtmlId();
        $html .= "
                <script type='text/x-magento-init'>
                    {
                        \"input[id^={$id}]\": {
                            \"CreditKey_B2BGateway/js/config/creditkey_marketing\": " . $this->getJsonConfig() . "
                        }
                    }
                </script>";

        return $html;
    }

    /**
     * Get JSON config for JS component
     *
     * @return string
     */
    private function getJsonConfig()
    {
        $this->creditKeyApi->configure();

        $config = [
            'ckConfig' => [
                'endpoint' => $this->config->getEndpoint(),
                'publicKey' => $this->config->getPublicKey()
            ]
        ];

        return $this->json->serialize($config);
    }
}
