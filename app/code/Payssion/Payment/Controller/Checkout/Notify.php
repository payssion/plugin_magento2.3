<?php
/**
 * Copyright © 2016 Payssion All rights reserved.
 */

namespace Payssion\Payment\Controller\Checkout;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Checkout\Model\Session;
use Payssion\Payment\Model\Email;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Notify extends \Magento\Framework\App\Action\Action  implements CsrfAwareActionInterface
{
    /**
     *
     * @var \Payssion\Payment\Model\Config
     */
    protected $_config;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    protected $_email;


    const STATE_PAID = 2;
    const TRANSACTION_TYPE_ORDER = 'order';

    /**
     * Index constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Payssion\Payment\Model\Config $config
     * @param Session $checkoutSession
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Payssion\Payment\Model\Config $config,
        Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        Email $email
    )
    {
        $this->_config = $config;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_email = $email;

        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if(empty($params['pm_id'])){
            exit('order not found');
        }

        $UrlParams = http_build_query($params , '' , '&');
        $this->writeLog('payssion.txt',$UrlParams,'notify');

        if ($this->validateNotify($params)) {
            $orderModel = $this->_objectManager->get('Magento\Sales\Model\Order');

            $orderIncrementId = $params['order_id'];
            $order = $orderModel->loadByIncrementId($orderIncrementId);
            if (empty($order)) {
                echo 'order not found';
            } else {
                $orderStatus = null;
                switch ($params['state']) {
                    case 'completed':
                        $orderStatus = $orderModel::STATE_PROCESSING;
                        $this->createOrderInvoice($orderModel, $params, $order);

                        break;
                    case 'cancelled_by_user':
                    case 'cancelled':
                    case 'failed':
                    case 'error':
                    case 'expired':
                        $orderStatus = $orderModel::STATE_CANCELED;
                        break;
                    default:
                        break;
                }

                if ($orderStatus) {
                    $orderModel->setStatus($orderStatus);
                    $orderModel->setState($orderStatus);
                    $orderModel->save();
                    echo 'success';
                } else {
                    echo 'failed to update';
                }
            }

        } else {
            echo 'failed to check api_sig';
        }
    }

    protected function validateNotify($params)
    {
        $check_parameters = array(
            $this->_config->getApiKey(),
            $params['pm_id'],
            $params['amount'],
            $params['currency'],
            $params['order_id'],
            $params['state'],
            $this->_config->getSecretKey()
        );
        $check_msg = implode('|', $check_parameters);
        $check_sig = md5($check_msg);
        $notify_sig = $params['notify_sig'];
        return ($notify_sig == $check_sig);
    }

    public function createOrderInvoice($orderModel, $params, $order)
    {
        if ($orderModel->canInvoice()) {
            $invoice = $this->_objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($orderModel);
            $invoice->register();
            $invoice->setState(self::STATE_PAID);
            $invoice->save();

            $transactionSave = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();

            $orderModel->addStatusHistoryComment(__('Created invoice #%1,transaction_id :'.$params['transaction_id'], $invoice->getId()))->setIsCustomerNotified(true)->save();

            $this->createTransaction($orderModel, $params, $order);
        }
    }

    public function createTransaction($orderModel, $params, $order)
    {
        $payment = $this->_objectManager->create('Magento\Sales\Model\Order\Payment');
        $payment->setTransactionId($params['transaction_id']);
        $payment->setOrder($orderModel);
        $payment->setIsTransactionClosed(1);
        $transaction = $payment->addTransaction(self::TRANSACTION_TYPE_ORDER);
        $transaction->beforeSave();
        $transaction->save();


        $objectManager  =  \Magento\Framework\App\ObjectManager::getInstance();
        $payssion = $objectManager->create('\Payssion\Payment\Model\Log');
        $payssion->addData($params);
        $payssion->save();

        $this->_email->send($order); //发送订单确认邮件

    }

    /**
     * Return checkout session object
     *
     * @return Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }


    /**
     * write Log
     * @param string $logfile
     * @param string $str
     * @param string $type
     * @param string $tip
     */
    private function writeLog($logfile = '', $str='', $type='return',$tip='') {
        $root='/tmp/';
        $write_type = "ab";
        $log_file = $root . $logfile;
        $notes = '';
        $notes .= date('Y-m-d H:i:s')."    ".$type."    ".$tip."\n";
        $notes .= $str . "\n\n\n";
        if ($handle = fopen($log_file, $write_type)) {
            fwrite($handle, $notes);
            fclose($handle);
        }
    }


    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}