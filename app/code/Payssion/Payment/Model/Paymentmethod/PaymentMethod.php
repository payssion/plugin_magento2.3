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
        return $this->getConfigData('instructions');
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

        $pm_id = $this->getPMID();
        $data = array(
        	'source' => 'magento2',
            'amount' => number_format($total, 2),
            'currency' => $currency,
            'pm_id' => $pm_id,
        	'order_id' => $order_id,
            'payer_name' => $order->getCustomerName(),
            'payer_email' => $order->getCustomerEmail(),
            'description' => "Order #$order_id",
        	'ip' => $order->getRemoteIp(),
        	'notify_url' => $notify_url,
        	'return_url' => $return_url,
        );
        
        $payer_ref = $this->getPayerRef($pm_id, $order);
        if ($payer_ref) {
            $data['payer_ref'] = $payer_ref;
        }
        
        $address = $order->getBillingAddress();
        if ($address) {
            $data['billing_address'] = [
                'organization_name' => $address->getCompany(),
                'first_name'        => $address->getFirstname(),
                'last_name'         => $address->getLastname(),
                'email'             => $address->getEmail(),
                "phone"             => $address->getTelephone(),
                "line1"             => $address->getStreetLine(1),
                "line2"             => $address->getStreetLine(2),
                'postal_code'       => $address->getPostcode(),
                'city'              => $address->getCity(),
                'region'            => $address->getRegion(),
                'country'           => $address->getCountryId(),
            ];
        } else {
            die ('no billing address');
        }
        
        if (true/*substr($pm_id, 0, strlen('klarna')) === 'klarna'*/) {
            $data['order_items'] = $this->getOrderLines($order);
        }

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
    
    private function getPayerRef($pm_id, $order) {
        $pos = strpos($pm_id, '_');
        $suffix = $pos ? substr($pm_id, $pos + 1) : null;
        $payer_ref = null;
        if ('br' == $suffix) {
            //$payer_ref = $order->getData('custom_value');
        }
        
        return $payer_ref;
    }
    
    /**
     * Get Order lines of Order
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    private function getOrderLines($order)
    {
        $orderLines = array();
        $tax = 0;
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            
            /**
             * The total amount of the line, including VAT and discounts
             * Should Match: (unitPrice × quantity) - discountAmount
             * NOTE: TotalAmount can differ from actutal Total Amount due to rouding in tax or exchange rate
             */
            $totalAmount = $this->getTotalAmountOrderItem($item);
            
            /**
             * The total discount amount of the line.
             */
            $discountAmount = $this->getDiscountAmountOrderItem($item);
            
            /**
             * The price of a single item including VAT in the order line.
             * Calculated back from the totalAmount + discountAmount to overcome rounding issues.
             */
            $unitPrice = round(($totalAmount + $discountAmount) / $item->getQtyOrdered(), 2);
            
            /**
             * The amount of VAT on the line.
             * Should Match: totalAmount × (vatRate / (100 + vatRate)).
             * Due to Mollie API requirements, we calculate this instead of using $item->getTaxAmount() to overcome
             * any rouding issues.
             */
            $vatAmount = round($totalAmount * ($item->getTaxPercent() / (100 + $item->getTaxPercent())), 2);
            $tax += $vatAmount;
            
            $orderLine = array(
                'type'        => $item->getProduct()->getTypeId() != 'downloadable' ? 'physical' : 'digital',
                'name'        => preg_replace("/[^A-Za-z0-9 -]/", "", $item->getName()),
                'quantity'    => round($item->getQtyOrdered()),
                'unit_price'  => $unitPrice,
                'amount'      => $totalAmount + $discountAmount,
                'tax_rate'    => sprintf("%.2f", $item->getTaxPercent()),
                'tax_amount'  => $vatAmount,
                'sku'         => $item->getProduct()->getSku(),
                'product_url' => $item->getProduct()->getProductUrl()
            );
            
            if (false/*$discountAmount*/) {
                $orderLine['discount_amount'] = $discountAmount;
            }
            
            $orderLines[] = $orderLine;
        }
        
        if (!$order->getIsVirtual()) {
            /**
             * The total amount of the line, including VAT and discounts
             * NOTE: TotalAmount can differ from actutal Total Amount due to rouding in tax or exchange rate
             */
            $totalAmount = $this->getTotalAmountShipping($order);
            $vatRate = $this->getShippingVatRate($order);
            
            /**
             * The amount of VAT on the line.
             * Should Match: totalAmount × (vatRate / (100 + vatRate)).
             * Due to Mollie API requirements, we recalculare this from totalAmount
             */
            $vatAmount = round($totalAmount * ($vatRate / (100 + $vatRate)), 2);
            $tax += $vatAmount;
            
            $orderLines[] = array(
                'type'        => 'shipping',
                'name'        => preg_replace("/[^A-Za-z0-9 -]/", "", $order->getShippingDescription()),
                'quantity'    => 1,
                'unit_price'  => $totalAmount,
                'amount'      => $totalAmount,
                'tax_rate'    => sprintf("%.2f", $vatRate),
                'tax_amount'  => $vatAmount,
                'sku'         => $item->getSku(),
            );
        }
        
        if ($tax > 0) {
            $orderLines[] = [
                "type" => "tax",
                "description" => "Tax",
                "amount" => $tax
            ];
        }
        
        $discount = $order->getDiscountAmount();
        if ($discount < 0) {
            $orderLines[] = array(
                'type'        => 'discount',
                'name'        => 'Discount',
                'quantity'    => 1,
                'unit_price'  => $discount,
                'amount'      => $discount,
            );
        }
        
        //die(print_r($orderLines, true));
        return $orderLines;
    }
    
    /**
     * @param OrderItemInterface $item
     *
     * @return float
     */
    private function getTotalAmountOrderItem($item)
    {
        return $item->getRowTotal()
        - $item->getDiscountAmount()
        + $item->getTaxAmount()
        + $item->getDiscountTaxCompensationAmount();
    }
    
    /**
     * @param OrderItemInterface $item
     *
     * @return float
     */
    private function getDiscountAmountOrderItem($item)
    {
        return abs($item->getDiscountAmount() + $item->getDiscountTaxCompensationAmount());
    }
    
    /**
     * @param Mage_Sales_Model_Order $order
     * @param                        $forceBaseCurrency
     *
     * @return float
     */
    protected function getTotalAmountShipping($order)
    {
        return $order->getShippingAmount()
        + $order->getShippingTaxAmount()
        + $order->getShippingDiscountTaxCompensationAmount();
    }
    
    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return double
     */
    protected function getShippingVatRate($order)
    {
        $taxPercentage = 0;
        if ($order->getShippingAmount() > 0) {
            $taxPercentage = ($order->getShippingTaxAmount() / $order->getShippingAmount()) * 100;
        }
        
        return $taxPercentage;
    }
    
    private function getPMID() {
    	return substr($this->_code, strlen('payssion_payment_'));
    }
}