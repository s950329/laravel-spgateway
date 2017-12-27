<?php
/**
 *
 *
 * User: Chu
 * Date: 2017/12/27
 * Time: 上午11:53
 */

namespace LeoChien\Spgateway;


class Spgateway
{
    private $mpg;
    private $receipt;
    private $refund;

    public function __construct()
    {
        $this->mpg = new MPG();
        $this->receipt = new Receipt();
        $this->refund = new Refund();
    }

    public function searchOrder(array $params)
    {
        return $this->mpg->searchOrder($params);
    }

    public function refundOrder($orderId, $orderPrice, $notifyUrl)
    {
        return $this->refund->refund($orderId, $orderPrice, $notifyUrl);
    }
}