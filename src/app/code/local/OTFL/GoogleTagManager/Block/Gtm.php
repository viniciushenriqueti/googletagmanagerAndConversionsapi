<?php

class OTFL_GoogleTagManager_Block_Gtm extends Mage_Core_Block_Template {

    const FACEBOOK_LOG_FILE = 'facebook.log';

    /**
     * Generate JavaScript for the container snippet.
     *
     * @return string
     */
    protected function _getContainerSnippet() {
        $containerId = Mage::helper('googletagmanager')->getContainerId();
        return "<noscript><iframe src=\"//www.googletagmanager.com/ns.html?id=" . $containerId . "\"
height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','" . $containerId . "');</script>\n";
    }

    /**
     * Generate JavaScript for the data layer.
     *
     * @return string|null
     */
    protected function _getDataLayer() {

        $request = $this->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        $data = array();
        if (Mage::helper('googletagmanager')->isDataLayerTransactionsEnabled())
            $data = $data + $this->_getTransactionData();
            if($module == 'checkout' && $action == 'success') {
                $data = $this->_getSuccessData($data);
            }
            if($module == 'mundipagg' && $action == 'success') {
                $data = $this->_getSuccessData($data);
            }
        if (Mage::helper('googletagmanager')->isDataLayerVisitorsEnabled())
            $data = $data + $this->_getVisitorData();

        $data_layer = new Varien_Object();
        $data_layer->setData($data);
        Mage::dispatchEvent('googletagmanager_get_datalayer', array('data_layer' => $data_layer));
        $data = $data_layer->getData();

        if (!empty($data))
            return "<script>dataLayer = [" . json_encode($data) . "];</script>\n\n";
        else
            return '';
    }

    /**
     * Get transaction data for use in the data layer.
     *
     * @link https://developers.google.com/tag-manager/reference
     * @return array
     */
    protected function _getTransactionData() {

        $data = array();

        $request = $this->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();

        if($module == 'checkout' || $module == 'checkout' && $controller == 'cart' && $action == 'index') {
            return $this->_getTransactionDataCart();
        }else  if($controller == 'index' && $this->isHomepage()) {
            return $this->_getPageData('home');
        }else if($controller == 'product') {
            return $this->_getProductData();
        }else if(Mage::registry('current_category')) {
            return $this->_getCategoryDataContent('category');
        }else if(Mage::registry('current_landingpage')) {
            return $this->_getPageData('landingpage');
        }else if($controller == 'result') {
            return $this->_getSearchResultsData('searchresults');
        }else if($controller == 'account') {
            return $this->_getCustomerPage('account');
        }else if($module == 'cms' && $controller == 'page') {
            return $this->_getPageData('institucional');
        }

        return $data;
    }

    /**
     * Get transaction data for use in the data layer.
     *
     * @link https://developers.google.com/tag-manager/reference
     * @return array
     */
    protected function _getTransactionDataCart() {

        $products = array();

        $request = $this->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        $cart = Mage::getModel('checkout/cart')->getQuote();
        $cartsku = array();

        foreach ($cart->getAllVisibleItems() as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $cartsku[] = $item->getSku();
            $products[] = array(
                'name' => $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($item->getName())),
                'sku' => $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($item->getProduct()->getData('sku'))),
                'id' => $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($product->getId())),
                'category' => $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($product->getGoogleCategory())),
                'price' => (float)$this->formatNumber($item->getBasePrice()),
                'quantity' => (int) $item->getQty(),
                'brand' => $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($product->getBrand())),
            );
            $productsFacebook[] = array(
                'id' => $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($item->getProduct()->getData('sku'))),
                'item_price' => (float)$this->formatNumber($item->getBasePrice()),
                'quantity' => (int) $item->getQty(),
                'delivery_category' => 'home_delivery'
            );
        }

        if($module == 'checkout' && $controller == 'onepage' && $action == 'success'){
            $event_name = 'transaction';
            $ec_event_name = 'purchase';
        }

        if($module == 'checkout' && $controller == 'onepage' && $action == 'index'){
            $event_name = 'cart';
            $ec_event_name = 'checkout';
        }

        if($module == 'checkout' && $controller == 'cart'){
            $event_name = 'cart';
            $ec_event_name = 'cart';
        }

        $data = array(
            'event' => $event_name,
            'google_tag_params' => array()
        );

        $skus_for_remarketing = array();
        foreach ($products as $product) {
            $skus_for_remarketing[] = $product['sku'];
        }

        $google_tag_params = array();
        $google_tag_params['ecomm_pagetype'] = $ec_event_name;

        $google_tag_params['transactionId'] = "";
        $google_tag_params['transactionDate'] = date("Y-m-d");
        $google_tag_params['transactionType'] = Mage::helper('googletagmanager')->getTransactionType();
        $google_tag_params['transactionAffiliation'] = Mage::helper('googletagmanager')->getTransactionAffiliation();
        $google_tag_params['transactionTotal'] = (float)($this->formatNumber($cart->getBaseGrandTotal()));
        $google_tag_params['transactionShipping'] = (float)$this->formatNumber($cart->getShippingAddress()->getBaseShippingAmount());
        $google_tag_params['transactionTax'] = (float)$this->formatNumber($cart->getBaseTaxAmount());
        $google_tag_params['transactionCurrency'] = "BRL";
        $google_tag_params['transactionPromoCode'] = ($cart->getCouponCode() ? $cart->getCouponCode() : "");
        $google_tag_params['transactionProducts'] = $products;
        $google_tag_params['impressions'] = $products;
        $google_tag_params['ecomm_prodid'] = $skus_for_remarketing;
        $google_tag_params['ecomm_totalvalue'] = (float)($this->formatNumber($cart->getBaseGrandTotal()));

        $customer = Mage::getSingleton('customer/session');
        if ($customer->getCustomerId()){
            $google_tag_params['user_id'] = (string) $customer->getCustomerId();
        }

        if($this->IsReturnedCustomer()){
            $google_tag_params['returnCustomer'] =  'true';
        }
        else {
            $google_tag_params['returnCustomer'] =  'false';
        }

        $data['google_tag_params'] = $google_tag_params;


        /* Facebook Conversion API Code */
        $custom_product = $productsFacebook;
        $num_items = count($products);
        $custom_data = array(
                        "content" => $custom_product,
                        "content_type" => "product",
                        "value" => (float)($this->formatNumber($cart->getBaseGrandTotal())),
                        "currency" => "BRL",
                        "num_items" => $num_items
                        );

        switch ($ec_event_name) {
            case "cart":
                if($num_items >= 1){
                    $this->createFacebookRequest("AddToCart", [], [], $custom_data);
                }
                break;
            case "checkout":
                $this->createFacebookRequest("InitiateCheckout", [], [], $custom_data);
                break;
        }
        /* End Facebook Conversion API Code */

        // Trim empty fields from the final output.
        foreach ($data as $key => $value) {
            if (!is_numeric($value) && empty($value))
                unset($data[$key]);
        }

        return $data;
    }

    /**
     * Get visitor data for use in the data layer.
     *
     * @link https://developers.google.com/tag-manager/reference
     * @return array
     */
    protected function _getVisitorData() {
        $data = array();
        $customer = Mage::getSingleton('customer/session');
        $customerInfo = $customer->getCustomer();
        $primaryAddress = $customerInfo->getPrimaryShippingAddress();
        if ($customer->getCustomerId()){
            $data['visitorId'] = (string) $customer->getCustomerId();
            $customerEmail = $customerInfo->getEmail();
            $data['hashedEmail'] = hash('sha256', $customerEmail);
       }

        $data['visitorLoginState'] = ($customer->isLoggedIn()) ? 'Logged in' : 'Logged out';
        $data['visitorType'] = (string) Mage::getModel('customer/group')->load($customer->getCustomerGroupId())->getCode();
        
        $orders = Mage::getResourceModel('sales/order_collection')->addFieldToSelect('*')->addFieldToFilter('customer_id', $customer->getId());
        $ordersTotal = 0;

        foreach ($orders as $order) {
            $ordersTotal += $order->getGrandTotal();
        }

        if ($customer->isLoggedIn()) {
            $data['visitorLifetimeValue'] = round($ordersTotal, 2);
        } else {
            $orderData = $this->_getTransactionData();
            if (!empty($orderData) && isset($orderData['transactionTotal'])) {
                $data['visitorLifetimeValue'] = $orderData['transactionTotal'];
            } else {
                $data['visitorLifetimeValue'] = 0;
            }
        }
        
        $data['visitorExistingCustomer'] = ($ordersTotal > 0) ? 'Yes' : 'No';
        
        if (array_key_exists("HTTP_X_FORWARDED_FOR", $_SERVER)){
            $real_customer_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $real_customer_ip = Mage::helper('core/http')->getRemoteAddr();
        }
        $data['external_id'] = $this->hashField($real_customer_ip);

        $this->createFacebookRequest("PageView");

        return $data;
    }

    /**
     * Get identify page for use in the data layer.
     *
     * @link https://developers.google.com/tag-manager/reference
     * @return array
     */
    protected function _getPageData($page) {
        $data = array();

        if (!$page) return array();

        if($page == 'institucional'){
            $event = 'institucional';
            $ec_pagetype = $page;
            $ecomm_pagetype = 'other';
        } else if ($page == 'landingpage'){
            $event = 'landingpage';
            $ec_pagetype = $page;
            $ecomm_pagetype = 'category';
        } else if ($page == 'home'){
            $event = $page;
            $ec_pagetype = $page;
            $ecomm_pagetype = $page;
        } else {
            $event = $page;
            $ec_pagetype = $page;
            $ecomm_pagetype = $page;   
        }

        $data = array(
            'event' => $event,
            'google_tag_params' => array()
        );

        $google_tag_params = array();
        $google_tag_params['ecomm_pagetype'] = $ecomm_pagetype;        

        $customer = Mage::getSingleton('customer/session');
        if ($customer->getCustomerId()){
            $google_tag_params['user_id'] = (string) $customer->getCustomerId();    
        }
        if($this->IsReturnedCustomer()){
            $google_tag_params['returnCustomer'] =  'true';
        }
        else {
            $google_tag_params['returnCustomer'] =  'false';
        }
        $data['google_tag_params'] = $google_tag_params;

        /* Facebook Conversion API Code */
        $this->createFacebookRequest("PageView");
        /* End Facebook Conversion API Code */

        return $data;
    }

    /**
     * Get identify page for use in the data layer.
     *
     * @link https://developers.google.com/tag-manager/reference
     * @return array
     */
    protected function _getResultsPage($page) {
        $data = array();
        if (!$page) return array();

        $data = array(
            'event' => $page,
            'ecomm_pagetype' => $page,
            'ec_pagetype' => $page,
            'google_tag_params' => array()
        );

        $google_tag_params = array();
        $google_tag_params['ecomm_pagetype'] = $page;        
        $google_tag_params['ec_pagetype'] = $page;     
        $data['google_tag_params'] = $google_tag_params;

        return $data;
    }

    /**
     * Get identify page for use in the data layer.
     *
     * @link https://developers.google.com/tag-manager/reference
     * @return array
     */
    protected function _getCustomerPage($page) {
        $data = array();
        if (!$page) return array();

        $request = $this->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        $pagetype = '';
        $ec_event_name = '';

        if($action == 'login'){
            $pagetype = 'login';
            $ec_event_name = 'login';
        }
        if($action == 'create'){
            $pagetype = 'create';
            $ec_event_name = 'create';
        }

        $data = array(
            'event' => $page,
            'google_tag_params' => array()
        );

        $google_tag_params = array();
        $google_tag_params['ecomm_pagetype'] = $pagetype;        
        $google_tag_params['ec_pagetype'] = $ec_event_name;
        if($this->IsReturnedCustomer()){
            $google_tag_params['returnCustomer'] =  'true';
        }
        else {
            $google_tag_params['returnCustomer'] =  'false';
        }  
        $data['google_tag_params'] = $google_tag_params;

        $this->createFacebookRequest("PageView");

        return $data;
    }

    /**
     * DataLayer for Results page
     *
     * @link https://developers.google.com/tag-manager/reference
     * @return array
     */

    protected function _getSuccessData($data) {
        $data['event'] = 'purchase';
        $order_id = Mage::getSingleton('checkout/session')->getLastOrderId();
        $order_info = Mage::getModel('sales/order')->load($order_id);
        $increment_id = $order_info->getIncrementId();
        $products = [];

        foreach ($order_info->getAllVisibleItems() as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $products[] = array(
                'name' => $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($item->getName())),
                'sku' => $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($item->getProduct()->getData('sku'))),
                'id' => $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($item->getProduct()->getData('sku'))),
                'price' => $this->formatNumber($item->getBasePrice()),
                'quantity' => (int) $item->getQtyOrdered()
            );
            $productsFacebook[] = array(
                'id' => $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($item->getProduct()->getData('sku'))),
                'item_price' => (float)$this->formatNumber($item->getBasePrice()),
                'quantity' => (int) $item->getQty(),
                'delivery_category' => 'home_delivery'
            );
        }
        
        $skus_for_remarketing = array();
        foreach ($products as $product) {
            $skus_for_remarketing[] = $product['sku'];
        }
        
        $data['google_tag_params']['ecomm_pagetype'] = 'purchase';
        $data['google_tag_params']['transactionId'] = $increment_id;
        $data['google_tag_params']['transactionType'] = Mage::helper('googletagmanager')->getTransactionType();
        $data['google_tag_params']['transactionAffiliation'] = Mage::helper('googletagmanager')->getTransactionAffiliation();
        $data['google_tag_params']['transactionDate'] = date("Y-m-d");
        $data['google_tag_params']['transactionTotal'] = (float)$this->formatNumber($order_info->getBaseGrandTotal());
        $data['google_tag_params']['transactionShipping'] = (float)$this->formatNumber($order_info->getBaseShippingAmount());
        $data['google_tag_params']['transactionTax'] = (float)$this->formatNumber($order_info->getBaseTaxAmount());
        $data['google_tag_params']['transactionCurrency'] = "BRL";
        $data['google_tag_params']['transactionPromoCode'] = ($order_info->getCouponCode() ? $order_info->getCouponCode() : "");
        $data['google_tag_params']['transactionProducts'] = $products;
        $data['google_tag_params']['revenue'] = (float)$this->formatNumber($order_info->getBaseGrandTotal());
        $data['google_tag_params']['ecomm_totalvalue'] = (float)($this->formatNumber($order_info->getGrandTotal()));
        $data['google_tag_params']['impressions'] = $products;
        $data['google_tag_params']['ecomm_prodid'] = $skus_for_remarketing;


        $customer = Mage::getSingleton('customer/session');
        if ($customer->getCustomerId()){
            $data['google_tag_params']['user_id'] = (string) $customer->getCustomerId();
        }
        if($this->IsReturnedCustomer()){
            $data['google_tag_params']['returnCustomer'] = 'true';
        }
        else {
            $data['google_tag_params']['returnCustomer'] = 'false';
        }

        /* Facebook Conversion API Code */
        $custom_product = $productsFacebook;
        $custom_data = array(
                        "content" => $custom_product,
                        "content_type" => "product",
                        "value" => (float)$this->formatNumber($order_info->getBaseGrandTotal()),
                        "currency" => "BRL",
                        "num_items" => count($products),
                        "order_id" => $increment_id
                        );

        $this->createFacebookRequest("Purchase", [], [], $custom_data);
        /* End Facebook Conversion API Code */

        return $data;
        
    }


    /**
     * DataLayer for Results page
     *
     * @link https://developers.google.com/tag-manager/reference
     * @return array
     */

    protected function _getSearchResultsData($page) {

        $data = array();
        if (!$page) return array();

        $products = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->setPageSize(25)
            ->load();
        
        $cartsku = array();
        $impression = [];
        $impressions = [];
        $count = 0;
        $end = 32;

        foreach ($products as $item) {
            $count++;
            $cartsku[] = $item->getSku();
            $impression['id'] = $item->getSku();
            $impression['name'] = $item->getName();
            $impression['brand'] = $item->getBrand();
            $impression['category'] = "";
            $impression['list'] = "";
            $impression['position'] = $count;
            $impressions[] = $impression;
            if ($count == $end) break;
        }

        $data = array(
            'event' => $page,
            'google_tag_params' => array()
        );

        $google_tag_params = array();
        $requestParams = $this->getRequest()->getParams();
        
        $google_tag_params['ecomm_pagetype'] = $page;  
        $google_tag_params['ecomm_prodid'] =  $cartsku;
        $google_tag_params['impressions'] =  $impressions;
        
        if($this->IsReturnedCustomer()){
            $google_tag_params['returnCustomer'] =  'true';
        }
        else {
            $google_tag_params['returnCustomer'] =  'false';
        }
        $data['google_tag_params'] = $google_tag_params;

        /* Facebook Conversion API Code */
        $custom_data = array(
                            "content_ids" => $cartsku,
                            "content_type" => "product",
                            "search_string" => $requestParams['q']
                        );
        $this->createFacebookRequest("Search", [], [], $custom_data);
        /* End Facebook Conversion API Code */

        return $data;
    }


    /**
     * Get current category name and the pattern
     *
     * @return array
     */
    protected function _getCategoryDataContent() {

        $categoryName = Mage::registry('current_category')->getName();
        $_cat = new Mage_Catalog_Block_Navigation();
        $curent_cat = $_cat->getCurrentCategory();
        $curent_cat_id = $curent_cat->getId();
        $parentId = Mage::getModel('catalog/category')->load($curent_cat_id)->getParentId();
        $parent = Mage::getModel('catalog/category')->load($parentId);
        $categorydaddy = $parent->getName();

        $category_id = Mage::getModel('catalog/layer')->getCurrentCategory()->getId();
        $category = Mage::getModel('catalog/category')->load($category_id);
        $products = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addCategoryFilter($category)
            ->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
            ->load();

        $cartsku = array();
        $data = array();
        $impression = [];
        $impressions = [];
        $count = 0;
        $end = 32;
        foreach ($products as $item) {
            $count++;
            $cartsku[] = $item->getSku();
            $impression['id'] = $item->getSku();
            $impression['name'] = $item->getName();
            $impression['brand'] = $item->getBrand();
            $impression['category'] = $categoryName;
            $impression['list'] = $categoryName;
            $impression['position'] = $count;
            $impressions[] = $impression;
            if ($count == $end) break;
        }

        $data['event'] = 'category';

        $google_tag_params = array();
        $google_tag_params['ecomm_pagetype'] = 'category';
        $google_tag_params['ecomm_category'] = $categoryName;
        $google_tag_params['ecomm_daddy'] = $categorydaddy;
        $google_tag_params['ecomm_prodid'] =  $cartsku;
        $google_tag_params['impressions'] =  $impressions;

        if($this->IsReturnedCustomer()){
            $google_tag_params['returnCustomer'] =  'true';
        }
        else {
            $google_tag_params['returnCustomer'] =  'false';
        }
        $data['google_tag_params'] = $google_tag_params;

        $data = array(
            'event' => 'category',
            'google_tag_params' => $google_tag_params
        );

        /* Facebook Conversion API Code */
        $custom_data = array(
                "content_ids" => $cartsku,
                "content_type" => "product",
                "content_category" => $categoryName,
                );
        $this->createFacebookRequest("ViewCategory", [], [], $custom_data);
        /* End Facebook Conversion API Code */
        
        return $data;
    }



    /**
     * Get identify page for use in the data layer.
     *
     * @link https://developers.google.com/tag-manager/reference
     * @return array
     */
    protected function _getProductData() {


        $cat_id = Mage::getModel('catalog/layer')->getCurrentCategory()->getId();
        $category = Mage::getModel('catalog/category')->load($cat_id);
        $data['event'] = 'product';

        $finalPrice = $this->getFinalPriceDiscount();
        if($finalPrice):
            $google_tag_params = array();
            $google_tag_params['ecomm_prodid'] = $this->getProduct()->getSku();
            $google_tag_params['name'] = $this->getProduct()->getName();
            $google_tag_params['brand'] = $this->getProduct()->getBrand();
            $google_tag_params['ecomm_pagetype'] = 'product';
            $google_tag_params['ecomm_category'] = $category->getName();
            $google_tag_params['ecomm_totalvalue'] = (float)$this->formatNumber($finalPrice);
            
            $customer = Mage::getSingleton('customer/session');
            if ($customer->getCustomerId()){
                $google_tag_params['user_id'] = (string) $customer->getCustomerId();    
            }
            if($this->IsReturnedCustomer()){
                $google_tag_params['returnCustomer'] =  'true';
            }
            else {
                $google_tag_params['returnCustomer'] =  'false';
            }

            $data['google_tag_params'] = $google_tag_params;

            /* Facebook Conversion API Code */
            $custom_data = array(
                            "content_name" => $this->getProduct()->getName(),
                            "content_ids" => [$this->getProduct()->getSku()],
                            "content_category" => $category->getName(),
                            "content_type" => "product",
                            "value" => (float)$this->formatNumber($finalPrice),
                            "currency" => "BRL"
                            );
            $this->createFacebookRequest("ViewContent", [], [], $custom_data);
            /* End Facebook Conversion API Code */

        endif;

        return $data;
    }

    public function isHomepage() {
        return (Mage::getSingleton('cms/page')->getIdentifier() == 'home' ? true : false);
    }

    public function IsReturnedCustomer() {
        $customer = Mage::getSingleton('customer/session');
        if($customer->isLoggedIn()){
            $orders = Mage::getModel('sales/order')->getCollection()->addAttributeToFilter('customer_id',Mage::getSingleton('customer/session')->getCustomer()->getId());
            $countOrders = $orders->count();
            if($countOrders != 0){
                return true;
            }
            else{
                return false;
            }
        } else {
            return false;
        }
        
    }

    /**
     * Render Google Tag Manager code
     *
     * @return string
     */
    protected function _toHtml() {
        if (!Mage::helper('googletagmanager')->isGoogleTagManagerAvailable())
            return '';
        return parent::_toHtml();
    }

    /**
     * Retrieve current product model
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('product');
    }

    protected function getGroupedMinimalPrice() {
        $product = Mage::getModel('catalog/product')->getCollection()
            ->addMinimalPrice()
            ->addFieldToFilter('entity_id',$this->getProduct()->getId())
            ->getFirstItem();
        return Mage::helper('tax')->getPrice($product, $product->getMinimalPrice(), $includingTax = true);
    }

    protected function getFinalPriceDiscount() {
        $_product = $this->getProduct();
        $finalPrice = $this->helper('tax')->getPrice($_product, $_product->getFinalPrice(), true);
        $_minimalPriceInclTax = $_maximalPriceInclTax = false;
        $_priceModel = $_product->getPriceModel();
        if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            list($_minimalPriceInclTax, $_maximalPriceInclTax) = $_priceModel->getPrices($_product, null, true, false);
        } elseif ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            $_minimalPriceValue = $_product->getMinimalPrice();
        }
        if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE):
            if ($_minimalPriceInclTax ==$_maximalPriceInclTax){
                $finalPrice = $_minimalPriceInclTax;
            }else{
                $finalPrice = $_maximalPriceInclTax;
            }
        elseif ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED):
            $finalPrice = $_minimalPriceValue;
        endif;

        return $finalPrice;
    }

    public function formatNumber($amount)
    {
        if(strrpos($amount, "$")){
            $amount = str_replace('R$','',trim($amount));
        }

        return number_format($amount, 2, '.', '');
    }

    public function hashField($field)
    {
        $hashed = hash('sha256', $field);

        return $hashed;
    }

    public function getUserData(){
        $data = array();
        if (array_key_exists("HTTP_X_FORWARDED_FOR", $_SERVER)){
            $real_customer_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $real_customer_ip = Mage::helper('core/http')->getRemoteAddr();
        }
        $data['client_user_agent'] = Mage::helper('core/http')->getHttpUserAgent();
        $data['client_ip_address'] = $real_customer_ip;
        $customer = Mage::getSingleton('customer/session');
        if ($customer->getCustomerId()){
            $customerInfo = $customer->getCustomer();
            $primaryAddress = $customerInfo->getPrimaryShippingAddress();
            $data['external_id'] = (string) $customer->getCustomerId();
            $data['fn'] = $this->hashField($customerInfo->getFirstname());
            $data['ln'] = $this->hashField($customerInfo->getLastname());
            $data['em'] = $this->hashField($customerInfo->getEmail());
            if($primaryAddress){
                $data['ph'] = $this->hashField($primaryAddress->getTelephone());    
                $data['ct'] = $this->hashField($primaryAddress->getCity());
                $data['zp'] = $this->hashField($primaryAddress->getPostcode());
                $data['country'] = $this->hashField("br");
            }
            
        } else {
           $visitor_id = "";
           if (array_key_exists("visitor_data", $_SESSION['core'])){
                $visitor_id = $_SESSION['core']['visitor_data']['visitor_id'];
            }
            if (!empty($visitor_id) || $visitor_id !== "") {
                $data['external_id'] = $visitor_id;
            } else {
                $data['external_id'] = "visitor";
            }
            if($real_customer_ip) {
                $data['external_id'] = $this->hashField($real_customer_ip);    
            }
        }

        $_fbp = $this->getFbpCookie();
        if($_fbp) {
            $data['fbp'] = $_fbp;
        }

        return $data;
    }

    public function createFacebookRequest($event_name, $user_data = [], $contents = [], $custom_data = [], $event_source_url = "") {

        $userData = $this->getUserData();
        $event_time = time();
        $event_id = $userData['external_id'];

        $request = array(
            "event_name" => $event_name,
            "event_id" => $event_id,
            "event_time" => $event_time,
            "action_source" => "website",
            "event_source_url" => $event_source_url ? $event_source_url : Mage::helper('core/url')->getCurrentUrl(),
            "user_data" => $userData,
            "contents" => $contents,
            "custom_data" => $custom_data
       );


        $message = "createFacebookRequest - cleaned";
        $this->logFacebook($message, array_filter($request));

        $request = array_filter($request);

        $this->sendDataForFacebook($request);                

        return true;
    }

    public function sendDataForFacebook($request) {

        $graphUrl = Mage::helper('googletagmanager')->getFacebookApiGraphUrl();
        $pixelID = Mage::helper('googletagmanager')->getFacebookApiPixelId();
        $testCode = Mage::helper('googletagmanager')->getFacebookApiTestcode();
        $fullGraphUrl = $graphUrl.$pixelID.'/events';
        $accessToken = Mage::helper('googletagmanager')->getFacebookApiAccessToken();

        if($graphUrl) {
            try {
                $curl = curl_init();

                if($testCode) {
                    curl_setopt_array($curl, [
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_URL => $fullGraphUrl,
                        CURLOPT_POST => 1,
                        CURLOPT_POSTFIELDS => [
                            access_token => $accessToken,
                            data => "[".json_encode($request)."]",
                            test_event_code => $testCode
                        ]
                    ]);
                    $message = "sendDataForFacebook - test_event_code enabled";
                    $this->logFacebook($message);    
                } else {
                    curl_setopt_array($curl, [
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_URL => $fullGraphUrl,
                        CURLOPT_POST => 1,
                        CURLOPT_POSTFIELDS => [
                            "access_token" => $accessToken,
                            "data" => "[".json_encode($request)."]"
                        ]
                    ]);
                    $message = "sendDataForFacebook - test_event_code disabled";
                    $this->logFacebook($message);    
                }
                
                $response = curl_exec($curl);
                $message = "sendDataForFacebook";
                $this->logFacebook($message, json_encode($response));

                curl_close($curl);

                return true;
            } catch (Error $e) {
                $message = "sendDataForFacebook exception";
                $this->logFacebook($message, json_encode($e));
                return true;
            }
        }

        return false;

    }

    public function logFacebook($message, $data = array()) {
        if(Mage::helper('googletagmanager')->isFacebookApiLogEnabled()){
            Mage::log("###################################", null, self::FACEBOOK_LOG_FILE, true);
            Mage::log($message, null, self::FACEBOOK_LOG_FILE, true);
            Mage::log(json_encode($data), null, self::FACEBOOK_LOG_FILE, true);    
        }
        return true;
    }

    public function getFbpCookie() {
        $name = "_fbp";
        $cookie = Mage::getModel('core/cookie')->get($name);
        if($cookie) {
            return $cookie;
        }
        return false;
    }

}
