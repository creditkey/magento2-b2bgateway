<?php

namespace CreditKey\B2BGateway\Model\ResourceModel;

use Magento\Sales\Model\ResourceModel\Order\Payment;

class OrderPayment extends Payment
{
    /**
     * Get order ID by CreditKey ID
     *
     * @param string $ckOrderId
     * @return string
     */
    public function getOrderIdByCkId($ckOrderId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['order_id'])
            ->columns([
                'ckOrderId' => new \Zend_Db_Expr(
                    "JSON_UNQUOTE(JSON_EXTRACT(`additional_information`, '$.ckOrderId'))"
                )
            ])
            ->having('ckOrderId = ?', $ckOrderId);

        return $connection->fetchOne($select);
    }
}
