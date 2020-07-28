<?php
/**
 * Copyright © 2016 Payssion All rights reserved.
 */

namespace Payssion\Payment\Model\Paymentmethod;

use Magento\Framework\UrlInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;
use Payssion\Payment\Model\Config;

/**
 * Description of AbstractPaymentMethod
 *
 * @author Payssion Technical <technical@payssion.com>
 */
abstract class PaymentMethod extends AbstractMethod
{
    protected $_isInitializeNeeded = true;

    protected $_canRefund = false;
    
    protected $_code;
    
    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function initialize($paymentAction, $stateObject)
    {
        $state = $this->getConfigData('order_status');
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);  
    }

    public function startTransaction(Order $order, UrlInterface $url)
    {
        $total = $order->getGrandTotal();
        $items = $order->getAllVisibleItems();

        $order_id = $order->getIncrementId();
        $quoteId = $order->getQuoteId();

        $currency = $order->getOrderCurrencyCode();

        $skus = array();
        foreach($items as $i):
            $skus[] = $i->getName().','.$i->getSku();
        endforeach;
        $products_purchased = implode('|', $skus);



        $notify_url = $url->getUrl('payssion/checkout/notify/');
        $return_url = $url->getUrl('payssion/checkout/finish/');

        $data = array(
        		'source' => 'magento2',
        		'amount' => number_format($total, 2),
        		'currency' => $currency,
        		'pm_id' => $this->getPMID(),
        		'order_id' => $order_id,
                'description' => "Items # ".$products_purchased,
        		'ip' => $order->getRemoteIp(),
        		'notify_url' => $notify_url,
        		'return_url' => $return_url,
        );
        
        $billing_address = $order->getBillingAddress();
        if ($billing_address) {
        	$data['payer_name'] = $billing_address['firstname'] . ' ' . $billing_address['lastname'];
        	$data['payer_email'] = $billing_address['email'];
        }
        
        $data = array(
        	'source' => 'magento2',
            'amount' => number_format($total, 2),
            'currency' => $currency,
            'pm_id' => $this->getPMID(),
        	'payer_name' => $billing_address['firstname'] . ' ' . $billing_address['lastname'],
        	'payer_email' => $billing_address['email'],
        	'order_id' => $order_id,
//            'description' => "Order # $order_id",
            'description' => "Items # ".$products_purchased,
        	'ip' => $order->getRemoteIp(),
        	'notify_url' => $notify_url,
        	'return_url' => $return_url,
        );

        if (!class_exists('PayssionClient')) {
        	$config = \Magento\Framework\App\Filesystem\DirectoryList::getDefaultConfig();
        	require_once(BP . '/' . $config['lib_internal']['path'] . "/payssion/lib/PayssionClient.php");
        }
        
        $config = new Config($this->_scopeConfig);
        $payssion = new \PayssionClient($config->getApiKey(), $config->getSecretKey(), !$config->isTestMode());
        $response = $payssion->create($data);
        if ($payssion->isSuccess()) {
        	return $response['redirect_url'];
        } else {
        	throw new \Exception($response['description']);
        }
    }
    
    private function getPMID() {
    	return substr($this->_code, strlen('payssion_payment_'));
    }
}