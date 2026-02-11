<?php
namespace Adlab\CustomShipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Shipping\Model\CarrierFactoryInterface;

/**
 * Observer to ensure Billing Address has required fields when using StoreDelivery shipping Method.
 *
 * @category Smile
 * @package  Smile\StoreDelivery
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class QuoteSubmit implements ObserverInterface
{
    /**
     * @var \Magento\Shipping\Model\CarrierFactoryInterface
     */
    private $carrierFactory;
	
	/**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    protected $scopeConfig;


    /**
     * QuoteSubmit constructor.
     *
     * @param \Magento\Shipping\Model\CarrierFactoryInterface $carrierFactory Carrier Factory
     */
    public function __construct(
		CarrierFactoryInterface $carrierFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	)
    {
        $this->carrierFactory = $carrierFactory;
		$this->scopeConfig = $scopeConfig;
    }

    /**
     * Set mandatory fields to shipping address from the billing one, if needed.
     *
     * This can occur when using Store Delivery, since the Shipping Address is set before the Billing.
     * In this case, the shipping address may not have the proper value for FirstName, LastName, and Telephone.
     *
     * @event checkout_submit_before
     *
     * @param \Magento\Framework\Event\Observer $observer The observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    	$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/QuoteSubmit.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);

        /** @var \Magento\Quote\Api\Data\CartInterface $quote */
        $quote = $observer->getQuote();
		
		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		
        /** @var \Magento\Quote\Api\Data\AddressInterface $shippingAddress */
        $shippingAddress = $quote->getShippingAddress();
		
		
		$logger->info('test');
		
        if ($shippingAddress) {
            $shippingMethod = $shippingAddress->getShippingMethod();
            
			$logger->info($shippingMethod);
			$logger->info('Custom shipping firstname'.$shippingAddress->getFirstname());
			$logger->info('Custom shipping Street'.print_r($shippingAddress->getStreet(),true));
		
			if ($shippingMethod) {
                //$methodCode = \Adlab\CustomShipping\Model\Carrier::METHOD_CODE;
				$methodCode = 'customshipping';
                $carrier    = $this->carrierFactory->getIfActive($methodCode);
				
				//$logger->info($methodCode);
				
                if (($carrier && $shippingMethod === sprintf('%s_%s', $methodCode, $carrier->getCarrierCode())) || ($shippingMethod == 'customshipping_customshipping')) {
                    $billingAddress = $quote->getBillingAddress();
					
					$addrfirstname = $this->scopeConfig->getValue('carriers/customshipping/addrfirstname', $storeScope);
					$addrlastname = $this->scopeConfig->getValue('carriers/customshipping/addrlastname', $storeScope);
					$addrcompany = $this->scopeConfig->getValue('carriers/customshipping/addrcompany', $storeScope);
					$addr1 = $this->scopeConfig->getValue('carriers/customshipping/addr1', $storeScope);
					$addr2 = $this->scopeConfig->getValue('carriers/customshipping/addr2', $storeScope);
					$addrcity = $this->scopeConfig->getValue('carriers/customshipping/addrcity', $storeScope);
					$addregion = $this->scopeConfig->getValue('carriers/customshipping/addregion', $storeScope);
					$addrpost = $this->scopeConfig->getValue('carriers/customshipping/addrpost', $storeScope);
					$addrtel = $this->scopeConfig->getValue('carriers/customshipping/addrtel', $storeScope);
					

                    
                    $shippingAddress->setFirstname($addrfirstname);
                    $shippingAddress->setLastname($addrlastname);
					$shippingAddress->setCompany($addrcompany);
					
					$shippingAddress->setStreet(array($addr1,$addr2));
					
					
					$shippingAddress->setCity($addrcity);
					$shippingAddress->setRegion($addregion);
					$shippingAddress->setPostcode($addrpost);
                    $shippingAddress->setTelephone($addrtel);

                    $logger->info("----------------------------------------");
                    $logger->info('Custom shipping firstname After '.$shippingAddress->getFirstname());
					$logger->info('Custom shipping Street After '.print_r($shippingAddress->getStreet(),true));
                }
            }

            $logger->info("====================================");
        }
    }
}
