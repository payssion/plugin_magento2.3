<?php
/**
 * Created by PhpStorm.
 * User: chankit
 * Date: 2019-04-29
 * Time: 21:21
 */
namespace Payssion\Payment\Model;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Email extends \Magento\Quote\Observer\SubmitObserver
{
    /**
     * @var OrderSender
     */
    private $orderSender;

    public function __construct(
        OrderSender $orderSender
    ) {
        $this->orderSender = $orderSender;
    }

    public function send($order)
    {
        $this->orderSender->send($order);
    }
}