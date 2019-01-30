<?php
namespace Marvel\Heroes\Helpers;
 
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product $product,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Order $order
    ) {
        $this->_storeManager = $storeManager;
        $this->_product = $product;
        $this->_checkoutSession = $checkoutSession;
        $this->formKey = $formKey;
        $this->_productRepository = $productRepository;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        
        $this->order = $order;
        parent::__construct($context);
    }
 
    public function createMageOrder($orderData) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store=$this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        $customer=$this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($orderData['email']);
        
        if(!$customer->getEntityId()){
            $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setFirstname($orderData['billing_address']['firstname'])
                    ->setLastname($orderData['billing_address']['lastname'])
                    ->setEmail($orderData['email']) 
                    ->setPassword($orderData['email']);
            $customer->save();
        }
         
        $cartId = $this->cartManagementInterface->createEmptyCart(); 
        $quote = $this->cartRepositoryInterface->get($cartId); 
        $quote->setStore($store);

        $customer= $this->customerRepository->getById($customer->getEntityId());
        $quote->setCurrency();
        $quote->assignCustomer($customer); 
 
        foreach($orderData['items'] as $item){
            
            $product = $objectManager->create('\Magento\Catalog\Model\Product')->load($item['product_id']); 
            $quote->addProduct($product, $item['qty']);

        }
 
        $quote->getBillingAddress()->addData($orderData['billing_address']);

        if (!$orderData['shipping_address']){
            $quote->getShippingAddress()->addData($orderData['billing_address']);
        }else{
            $quote->getShippingAddress()->addData($orderData['shipping_address']);
        }
 
        $shippingAddress=$quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod('flatrate_flatrate'); 

        if($orderData['coupon_code']){
            $quote->setCouponCode($orderData['coupon_code']);

        }
        
        $quote->setPaymentMethod('checkmo'); 
        $quote->setInventoryProcessed(false); 
 
        $quote->getPayment()->importData(['method' => 'checkmo']);
        $quote->save(); 

        $quote->collectTotals();

        $quote = $this->cartRepositoryInterface->get($quote->getId());
        $orderId = $this->cartManagementInterface->placeOrder($quote->getId());
        $order = $this->order->load($orderId);
        
        $order->setEmailSent(0);
        $increment_id = $order->getRealOrderId();
        if($order->getEntityId()){
            $result['order_id']= $order->getRealOrderId();
        }else{
            $result=['error'=>1,'msg'=>'Your custom message'];
        }
        return $result;
    }

}
 
?>