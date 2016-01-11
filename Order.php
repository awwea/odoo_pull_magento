<?php
class Bizilent_Magerpsync_Model_Order extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('magerpsync/order');
    }

    public function orderMapping($data) {
      $created_by = 'Odoo';
      if($data['created_by']) $created_by = $data['created_by'];

      $ordermap = Mage::getModel('magerpsync/order');
      $ordermap->setmage_order($data['mage_order']);
      $ordermap->seterp_order_id($data['erp_order_id']);
      $ordermap->seterp_cus_id($data['erp_cus_id']);
      $ordermap->setmage_order_id($data['mage_order_id']);
      $ordermap->seterp_order_line_id($data['erp_order_line_id']);
      $ordermap->seterp_order_name($data['erp_order_name']);
      $ordermap->setcreated_by($data['created_by']);
      $ordermap->save();
    }

    public function exportSpecificOrder($mage_order_id)
    {
    $This_order = Mage::getModel('sales/order')->load($mage_order_id);
    $currency_code = $This_order->getOrderCurrencyCode();
    $pricelist_id = Mage::getModel('magerpsync/currency')->sync_currency($currency_code);
    if(!$pricelist_id){
      return array(0,0,$this->__("Odoo pricelist id not found"));
    }



    $increment_id = $This_order->getIncrementId();
    $erpAddressArray = $this->getErpOrderAddresses($This_order);
    if(count(array_filter($erpAddressArray)) == 3){
      $lineids = '';
      $partner_id = $erpAddressArray[0];
      $odoo_order = $this->createOdooOrder($This_order, $pricelist_id, $erpAddressArray);
      $odoo_id = $odoo_order[0];
      $order_name = $odoo_order[1];      
      if($odoo_id){
        $lineids = $this->createOdooOrderLine($This_order, $odoo_id);

        if ($This_order['discount_amount'] != 0){
          $voucher_line_id = $this->createOdooOrderVoucherLine($This_order, $odoo_id);
          $lineids .= $voucher_line_id;
        }
        Mage::dispatchEvent('odoo_order_sync_after', array(            
            'mage_order_id' => $mage_order_id,
            'odoo_order_id' => $odoo_id,
          ));
        if ($This_order->getShippingDescription()){
          $shipping_line_id = $this->createOdooOrderShippingLine($This_order, $odoo_id);
          $lineids .= $shipping_line_id;
        }
        /* Creating Order Mapping At both End..*/
        $this->createOrderMapping($This_order, $odoo_id, $order_name, $partner_id, $lineids);

        $commonsocket = Mage::getModel("magerpsync/commonsocket");
        $draft_state = Mage::getStoreConfig('magerpsync/magerpsync2/draftstate');
        $auto_invoice = Mage::getStoreConfig('magerpsync/magerpsync2/auto_invoice');
        $auto_shipment = Mage::getStoreConfig('magerpsync/magerpsync2/auto_shipment');
        if(!$draft_state){
          $commonsocket->confirmOdooOrder($odoo_id);
          if($This_order->hasInvoices() && $auto_invoice==1){
            $commonsocket->invoiceOdooOrder($This_order, $odoo_id, $partner_id);
          }

          if($This_order->hasShipments() && $auto_shipment == 1){
            $commonsocket->deliverOdooOrder($This_order, $odoo_id);
          }
        }
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }

  public function createOrderMapping($This_order, $odoo_id, $order_name, $partner_id, $lineids='')
  {
    $mageOrderId = $This_order->getId();
    $increment_id = $This_order->getIncrementId();
    $helper = Mage::helper('magerpsync/connection');
    $mapping_data = array(
                'mage_order'=>$increment_id,
                'erp_order_id'=>$odoo_id,
                'erp_cus_id'=>$partner_id,
                'mage_order_id'=>$mageOrderId,
                'erp_order_line_id'=>rtrim($lineids,","),
                'erp_order_name'=>$order_name,
                'created_by'=>$helper::$mage_user,
            );
      $this->orderMapping($mapping_data);
  }

  public function createOdooOrder($This_order, $pricelist_id, $erpAddressArray)
  {
    $odoo_order = array();
    $extra_field_array = array();
    $partner_id = $erpAddressArray[0];
    $partner_invoice_id = $erpAddressArray[1];
    $partner_shipping_id = $erpAddressArray[2];
    $mage_order_id = $This_order->getId();
    Mage::getSingleton('adminhtml/session')->setExtraFieldArray($extra_field_array);
    Mage::dispatchEvent('odoo_order_sync_before', array(
        'mage_order_id' => $mage_order_id,
      ));
    $helper = Mage::helper('magerpsync/connection');
    $helper->getSocketConnect();
    $userId = Mage::getSingleton('adminhtml/session')->getUserId();
    $extra_field_array = Mage::getSingleton('adminhtml/session')->getExtraFieldArray();
      $increment_id = $This_order->getIncrementId();
    $helper = Mage::helper('magerpsync/connection');
    $client = $helper->getClientConnect();
    $context = $helper->getOdooContext();
    $userId = Mage::getSingleton('adminhtml/session')->getUserId();
    $warehouse_id = Mage::getSingleton('adminhtml/session')->getErpWarehouse();
    $order_array =  array(
          'partner_id'=>new xmlrpcval($partner_id,"int"),
          'partner_invoice_id'=>new xmlrpcval($partner_invoice_id,"int"),
          'partner_shipping_id'=>new xmlrpcval($partner_shipping_id,"int"),
          'pricelist_id'=>new xmlrpcval($pricelist_id,"int"),
          'date_order'=>new xmlrpcval($This_order->getCreatedAt(),"string"),
          'origin'=>new xmlrpcval($increment_id,"string"),
          'warehouse_id'=>new xmlrpcval($warehouse_id, "int"),
          'ecommerce_channel'=>new xmlrpcval('magento', "string"),
          'ecommerce_order_id'=>new xmlrpcval($This_order->getId(), "int"),
        );

    //START BY OH
    
    // $key1 = array(  new xmlrpcval($This_order->getId(), 'int'),
    //       ); //fix value with 195
    $temp = array();
    $items = $This_order->getAllItems();
    foreach ($items as $key => $value) {
    	echo "<pre>";print_r($value);
    	die;
      $temp[] = new xmlrpcval($value->getProductId(), 'int');
      // echo $value->getPrice();
   		

    }
    die;
    // $data = array();
    // $data[] = $temp;
    // $data['test'][] = array(1,2,3,4);
    // echo "<pre>";print_r($data);echo "</pre>";exit;

  //   $key1 = array(	new xmlrpcval(905, 'int'),
		// );

  //   $key = array(new xmlrpcval(
  //         array(  new xmlrpcval('product_id' , "string"), 
  //             new xmlrpcval('in',"string"),
  //             new xmlrpcval($key1,"array")),"array"),
  //     );

    

    // $key = array();
    $msg_ser = new xmlrpcmsg('execute');
    $msg_ser->addParam(new xmlrpcval($helper::$odoo_db, "string"));
    $msg_ser->addParam(new xmlrpcval($userId, "int"));
    $msg_ser->addParam(new xmlrpcval($helper::$odoo_pwd, "string"));
    $msg_ser->addParam(new xmlrpcval("sale.order.line", "string"));
    $msg_ser->addParam(new xmlrpcval("search", "string"));
    $msg_ser->addParam(new xmlrpcval($key, "array"));
    $resp0 = $client->send($msg_ser);
    if ($resp0->faultCode()) {
      array_push($Product, array('label' => Mage::helper('magerpsync')->__('Not Available(Error in Fetching)'), 'value' => ''));
      return $Product;
    }else{
       $field_list = array(
            new xmlrpcval("id", "string"),
            new xmlrpcval("create_date", "string"),
            new xmlrpcval("product_id", "string"),
       
        );
      $val = $resp0->value()->me['array'];
      echo "<pre>";
      print_r($val);
      die;
      $msg_ser1 = new xmlrpcmsg('execute');
      $msg_ser1->addParam(new xmlrpcval($helper::$odoo_db, "string"));
      $msg_ser1->addParam(new xmlrpcval($userId, "int"));
      $msg_ser1->addParam(new xmlrpcval($helper::$odoo_pwd, "string"));
      $msg_ser1->addParam(new xmlrpcval("sale.order.line", "string"));
      $msg_ser1->addParam(new xmlrpcval("read", "string"));
      $msg_ser1->addParam(new xmlrpcval($val, "array"));
      $msg_ser1->addParam(new xmlrpcval($field_list, "array"));
      $msg_ser1->addParam(new xmlrpcval($context, "struct"));
      $resp1 = $client->send($msg_ser1);
      $val = $resp1->value()->me['array'];
      print_r($val);
    }



    print_r($resp0);
    die;
    //END BY OH



    /* Adding Shipping Information*/
    if($This_order->getShippingMethod()){
      $shipping_method = $This_order->getShippingMethod();
      $shipping_code = explode('_', $shipping_method);
      if($shipping_code){
        $shipping_code = $shipping_code[0];
        $erp_carrier_id =  Mage::getModel('magerpsync/carrier')->checkSpecificCarrier($shipping_code);
        if($erp_carrier_id > 0){
          $order_array['carrier_id'] = new xmlrpcval($erp_carrier_id, "int");
        }
      }
    }

    
    /* Adding Payment Information*/
    $payment_method = $This_order->getPayment()->getMethodInstance()->getTitle();
    if($payment_method){
      $payment_info = 'Payment Information:- '.$payment_method;
      $order_array['note'] = new xmlrpcval($payment_info, "string");
    }

    /* Adding Extra Fields*/
    foreach($extra_field_array as $field => $value) {
        $order_array[$field]= new xmlrpcval($value, "int");
    }
    $msg = new xmlrpcmsg('execute');
    $msg->addParam(new xmlrpcval($helper::$odoo_db, "string"));
    $msg->addParam(new xmlrpcval($userId, "int"));
    $msg->addParam(new xmlrpcval($helper::$odoo_pwd, "string"));
    $msg->addParam(new xmlrpcval("wk.skeleton", "string"));
    $msg->addParam(new xmlrpcval("create_order", "string"));
    $msg->addParam(new xmlrpcval($order_array, "struct"));
    $msg->addParam(new xmlrpcval($context, "struct"));
    $resp = $client->send($msg);
    if($resp->faultcode()){
      $error = "Export Error, Order ".$increment_id." >>".$resp->faultString();
      Mage::log($error, null, 'odoo_connector.log');
    }else{
      $odoo_order_id = $resp->value()->me["struct"]["order_id"]->me["int"];
      $odoo_order_name = $resp->value()->me["struct"]["order_name"]->me["string"];
      array_push($odoo_order, $odoo_order_id);
      array_push($odoo_order, $odoo_order_name);
    }
    return $odoo_order;
  }

  public function createOdooOrderLine($This_order, $odoo_id)
  {
    $line_ids = '';
    $items = $This_order->getAllItems();
    if(!$items){
      return false;
    }
    /* Odoo Conncetion Data*/
    $helper = Mage::helper('magerpsync/connection');
    $context = $helper->getOdooContext();
    $client = $helper->getClientConnect();
    $userId = Mage::getSingleton('adminhtml/session')->getUserId();
    
    $mageOrderId = $This_order->getId();
    $increment_id = $This_order->getIncrementId();
    $prefix = Mage::getConfig()->getTablePrefix();
    $write = Mage::getSingleton("core/resource")->getConnection("core_write");
    $price_includes_tax = Mage::getStoreConfig('tax/calculation/price_includes_tax');
    $shipping_includes_tax = Mage::getStoreConfig('tax/calculation/shipping_includes_tax');
    
    foreach($items as $item){
      $item_id = $item->getId();
      $item_desc = $item->getName();
      $product_id = $item->getProductId();
      if ($price_includes_tax) {
        $BasePrice = $item->getPriceInclTax(); 
      }else{
        $BasePrice = $item->getPrice();
      }
      $ItemTaxPercent = $item->getTaxPercent();
      $item_type = $item->getProductType();
      if($item_type == 'configurable'){
        continue;
      }
      if($item_type == 'bundle'){
        $p = Mage::getModel('catalog/product')->load($product_id);
        $price_type = $p->getPriceType();
        if(!$price_type)
          $BasePrice = 0;
      }
      if($item->getParentItemId() != Null){
        $parent_id = $item->getParentItemId();
        $parent = Mage::getModel('sales/order_item')->load($parent_id);
        if($parent->getProductType() == 'configurable'){
          if ($price_includes_tax) {
            $BasePrice = $parent->getPriceInclTax();
          }else{
            $BasePrice = $parent->getPrice();
          }
          $ItemTaxPercent = $parent->getTaxPercent();
        }
        $item_id = $parent_id;
      }
      /*
        Fetching Odoo Product Id
      */
      $ordered_qty = $item->getQtyOrdered();
      $mappingcollection =  Mage::getModel('magerpsync/products')->getCollection()
                    ->addFieldToFilter('mage_pro_id',array('eq'=>$product_id));
      if(count($mappingcollection) > 0){
        foreach($mappingcollection as $map){
          $erp_product_id = $map->getErpProId();
        }
      }
      else{
        $erp_product_id = Mage::getModel("magerpsync/observer")->sync_Product($product_id, $ordered_qty);
      }
      if(!$erp_product_id){
        $error = "Odoo Product Not Found For Order ".$increment_id." Product id = ".$product_id;
        Mage::log($error, null, 'odoo_connector.log');
        continue;
      }
      $helper = Mage::helper('magerpsync/connection');
          $userId = Mage::getSingleton('adminhtml/session')->getUserId();
          $client = $helper->getClientConnect();
      $Order_line_array =  array(
            'order_id'=>new xmlrpcval($odoo_id,"int"),
            'product_id'=>new xmlrpcval($erp_product_id,"int"),
            'price_unit'=>new xmlrpcval($BasePrice,"string"),
            'product_uom_qty'=>new xmlrpcval($ordered_qty,"string"),
            'name'=>new xmlrpcval(urlencode($item_desc),"string")
          );
    /**************** checking tax applicable & getting mage tax id per item ************/
      if($ItemTaxPercent > 0){
        $item_taxes = array();
        $tax_item = $write->query("SELECT * FROM ".$prefix."sales_order_tax_item WHERE item_id='".$item_id."'");
        $tax_items = $tax_item->fetchAll();
        if($tax_items){
          foreach ($tax_items as $item_tax) {
            $erp_tax_id = 0;
            $order_tax = $write->query("SELECT code FROM ".$prefix."sales_order_tax WHERE tax_id='".$item_tax['tax_id']."' AND order_id= '".$mageOrderId."'");
            $tax_code_result = $order_tax->fetch();
            
            $tax_code = $tax_code_result["code"];
            $erp_tax_id = $this->getOdooTaxId($tax_code);

            /******************** getting erp tax id ******************/
            if($erp_tax_id){
              array_push($item_taxes, new xmlrpcval($erp_tax_id,"int"));
            }
          }
        }else{
          $order_tax = $write->query("SELECT code FROM ".$prefix."sales_order_tax WHERE order_id= '".$mageOrderId."'");
          $tax_code_result = $order_tax->fetch();
          if($tax_code_result){
            $tax_code = $tax_code_result["code"];
            $erp_tax_id = $this->getOdooTaxId($tax_code);
            if($erp_tax_id){
              array_push($item_taxes, new xmlrpcval($erp_tax_id,"int"));
            }
          }
        }
        $Order_line_array['tax_id'] = new xmlrpcval($item_taxes,"array");
      }
      else{
        $item_taxes = array();
        $tax_rate_data = Mage::getModel('tax/calculation_rate')->getCollection()->addFieldToFilter('rate',0)->getData();
        if (count($tax_rate_data)) {
          foreach($tax_rate_data as $map) {
            $tax_map_data =Mage::getModel('magerpsync/tax')->load($map['tax_calculation_rate_id'],"mage_tax_id")->getData();            
            if(count($tax_map_data)){
              $erp_tax_id = $tax_map_data['erp_tax_id'];
              if($erp_tax_id){
                array_push($item_taxes, new xmlrpcval($erp_tax_id,"int"));
              }
              $Order_line_array['tax_id'] = new xmlrpcval($item_taxes,"array");        
              break;
            }          
          }
        }
      }

      $line_create = new xmlrpcmsg('execute');
      $line_create->addParam(new xmlrpcval($helper::$odoo_db, "string"));
      $line_create->addParam(new xmlrpcval($userId, "int"));
      $line_create->addParam(new xmlrpcval($helper::$odoo_pwd, "string"));
      $line_create->addParam(new xmlrpcval("wk.skeleton", "string"));
      $line_create->addParam(new xmlrpcval("create_sale_order_line", "string"));
      $line_create->addParam(new xmlrpcval($Order_line_array, "struct"));
      $line_create->addParam(new xmlrpcval($context, "struct"));
      $line_resp = $client->send($line_create);
      if ($line_resp->faultCode()){
        $error = "Item Sync Error, Order ".$increment_id.", Product id = ".$product_id.'Error:-'.$line_resp->faultString();
        Mage::log($error, null, 'odoo_connector.log');
        continue;
      }
      $line_id = $line_resp->value()->me["struct"]["order_line_id"]->me["int"];
      $line_ids .= $line_id.",";
    }
    return $line_ids;
  }

  public function getOdooTaxId($tax_code)
  {
    $erp_tax_id = 0;
    if($tax_code){
      $collection = Mage::getModel("tax/calculation_rate")->getCollection()
                        ->addFieldToFilter('code',array('eq'=>$tax_code));
      if(count($collection)){
        foreach ($collection as $rate) {
          $rate_id = $rate->getTaxCalculationRateId();
          $mappingcollection =  Mage::getModel('magerpsync/tax')->getCollection()
                        ->addFieldToFilter('mage_tax_id',array('eq'=>$rate_id));
                        
          if(count($mappingcollection)){
            foreach ($mappingcollection as $mapping) {
              $erp_tax_id = $mapping->getErpTaxId();
            }
          }else{
            $response = Mage::getModel('magerpsync/tax')->create_specific_tax($rate_id);
            if($response['odoo_id']){
              $erp_tax_id = $response['odoo_id'];
            }
          }
        }
      }      
    }
    return $erp_tax_id;
  }

  public function createOdooOrderVoucherLine($This_order, $odoo_id)
  {
    $voucher_line_id = 0;
    $increment_id = $This_order->getIncrementId();
    $helper = Mage::helper('magerpsync/connection');
    $client = $helper->getClientConnect();
    $context = $helper->getOdooContext();
    $userId = Mage::getSingleton('adminhtml/session')->getUserId();

    $discount_amount = $This_order->getDiscountAmount();

    $description = "Discount";
    $coupon_desc = $This_order->getDiscountDescription();
    if($coupon_desc){
      $description .= "-".$coupon_desc;
    }
    $code = $This_order->getCouponCode();
    if($code){
      $description .= " Coupon Code:-".$code;
    }
    
    $voucher_line_array =  array(
        'order_id'=>new xmlrpcval($odoo_id,"int"),
        'name'=>new xmlrpcval('Discount',"string"),
        'price_unit'=>new xmlrpcval($discount_amount,"double"),
        'description'=>new xmlrpcval(urlencode($description),"string")
      );
    $voucher_line_id = $this->syncExtraOdooOrderLine($This_order, $voucher_line_array, $description);

    return $voucher_line_id;
  }

  public function createOdooOrderShippingLine($This_order, $odoo_id)
  {
    $shipping_description = urlencode($This_order->getShippingDescription());
    $shipping_line_array =  array(
        'order_id'=>new xmlrpcval($odoo_id,"int"),
        'name'=>new xmlrpcval('Shipping',"string"),
        'description'=>new xmlrpcval($shipping_description,"string")
      );
    $shipping_includes_tax = Mage::getStoreConfig('tax/calculation/shipping_includes_tax');
    if ($shipping_includes_tax) {
      $shipping_line_array['price_unit'] = new xmlrpcval($This_order->getShippingInclTax(),"double");
    }else{
      $shipping_line_array['price_unit'] = new xmlrpcval($This_order->getShippingAmount(),"double");
    }
    if($This_order->getShippingTaxAmount()>0){
      $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS);
      $CalculationModel = Mage::getSingleton('tax/calculation');
      $request = $CalculationModel->getRateOriginRequest();
      $tax_rates = $CalculationModel->getAppliedRates($request->setProductClassId($shippingTaxClass));
      if(count($tax_rates)){
        $shipping_taxes = array();
        foreach ($tax_rates as $tax) {
          $tax_code = $tax['id'];
          $erp_tax_id = $this->getOdooTaxId($tax_code);
          array_push($shipping_taxes, new xmlrpcval($erp_tax_id,"int"));
        }
        $shipping_line_array['tax_id'] = new xmlrpcval($shipping_taxes,"array");
      }
    }

    $shipping_line_id = $this->syncExtraOdooOrderLine($This_order, $shipping_line_array, $shipping_description);

    return $shipping_line_id;
  }

  public function syncExtraOdooOrderLine($This_order, $extra_line_array, $type="Extra")
  {
    $extra_line_id = '';
    $increment_id = $This_order->getIncrementId();
    $helper = Mage::helper('magerpsync/connection');
    $client = $helper->getClientConnect();
    $context = $helper->getOdooContext();
    $userId = Mage::getSingleton('adminhtml/session')->getUserId();
    $extra_line_array['ecommerce_channel'] = new xmlrpcval("magento","string");    
    $msg = new xmlrpcmsg('execute');
    $msg->addParam(new xmlrpcval($helper::$odoo_db, "string"));
    $msg->addParam(new xmlrpcval($userId, "int"));
    $msg->addParam(new xmlrpcval($helper::$odoo_pwd, "string"));
    $msg->addParam(new xmlrpcval("wk.skeleton", "string"));
    $msg->addParam(new xmlrpcval("create_order_shipping_and_voucher_line", "string"));
    $msg->addParam(new xmlrpcval($extra_line_array, "struct"));
    $msg->addParam(new xmlrpcval($context, "struct"));
    $resp = $client->send($msg);
    if ($resp->faultCode()){
      $error = $type." Line Export Error, For Order ".$increment_id." >>".$resp->faultString();
      Mage::log($error, null, 'odoo_connector.log');
    }else{
      $extra_line_id = $resp->value()->me["struct"]["order_line_id"]->me["int"];
      $extra_line_id = $extra_line_id.",";
    }
    return $extra_line_id;
  }

  public function getErpOrderAddresses($This_order)
  {
    $partner_id = 0;
    $partner_invoice_id = 0;
    $partner_shipping_id = 0;
    $store_id = $This_order->getStoreId();
    $magerpsync = Mage::getModel('magerpsync/magerpsync');
    $commonsocket = Mage::getModel("magerpsync/commonsocket");
    $billing = $This_order->getBillingAddress();
    $shipping = $This_order->getShippingAddress();
    if($billing){
      $billing->setEmail($This_order->getCustomerEmail());
    }
    if($shipping){
      $shipping->setEmail($This_order->getCustomerEmail());
    }
    if($This_order->getCustomerIsGuest() == 1){
      $customer_array =  array(
            'name'=>new xmlrpcval(urlencode($This_order->getCustomerName()),"string"),
            'email'=>new xmlrpcval(urlencode($This_order->getCustomerEmail()),"string"),
            'is_company'=>new xmlrpcval(false,"boolean"),            
          );
      $partner_id = $commonsocket->Erp_customer_create($customer_array, 0, 0, $store_id);

      $isDifferent = $this->checkAddresses($This_order);
      if($isDifferent == true){
        $partner_shipping_id = $this->createErpAddress($shipping, $partner_id, 0, 0, $store_id);
        $partner_invoice_id = $this->createErpAddress($billing, $partner_id, 0, 0, $store_id);
      }else{
        $partner_invoice_id = $this->createErpAddress($billing, $partner_id, 0, 0, $store_id);
        $partner_shipping_id = $partner_invoice_id;
      }      
    }
    $customer_id = $This_order->getCustomerId();
    if($customer_id > 0){
      $mappingcollection = $magerpsync->getCollection()
                    ->addFieldToFilter('mage_cus_id',array('eq'=>$customer_id))
                    ->addFieldToFilter('mage_address_id',array('eq'=>"customer"));

      if(count($mappingcollection)>0){
        foreach($mappingcollection as $map){
          $partner_id = $map->getErpCusId();
          $mapNeedSync = $map->getNeedSync();
        }
        $isDifferent = $this->checkAddresses($This_order);
        $billing_addresss_id =  $billing->getCustomerAddressId();
        if($isDifferent == true){
          $shipping_address_id = $shipping->getCustomerAddressId();
          $partner_shipping_id = $this->createErpAddress($shipping, $partner_id, $customer_id, $shipping_address_id);
          
          $partner_invoice_id = $this->createErpAddress($billing, $partner_id, $customer_id, $billing_addresss_id);
        }else{
          $partner_invoice_id = $this->createErpAddress($billing, $partner_id, $customer_id, $billing_addresss_id);
          $partner_shipping_id = $partner_invoice_id;
        }
      }else{
        $customer_array =  array(
            'name'=>new xmlrpcval(urlencode($This_order->getCustomerName()),"string"),
            'email'=>new xmlrpcval(urlencode($This_order->getCustomerEmail()),"string"),
            'is_company'=>new xmlrpcval(false,"boolean"),
          );
        $partner_id = $commonsocket->Erp_customer_create($customer_array, $customer_id, 'customer');

        $isDifferent = $this->checkAddresses($This_order);
        $billing_addresss_id =  $billing->getCustomerAddressId();
        if($isDifferent == true){
          $shipping_address_id = $shipping->getCustomerAddressId();
          $partner_shipping_id = $this->createErpAddress($shipping, $partner_id, $customer_id, $shipping_address_id);
          
          $partner_invoice_id = $this->createErpAddress($billing, $partner_id, $customer_id, $billing_addresss_id);
        }else{
          $partner_invoice_id = $this->createErpAddress($billing, $partner_id, $customer_id, $billing_addresss_id);
          $partner_shipping_id = $partner_invoice_id;
        }
      }
    }
    /* Customer Vat Synchronization*/
    $tax_vat = $This_order->getCustomerTaxvat();
    if($tax_vat){
      preg_match('/^[a-zA-Z]/', $tax_vat, $matches);
      if(!$matches){
        $tax_vat = $billing->getCountryId().''.$tax_vat;
      }
      $vat_array =  array(
        'vat'=>new xmlrpcval($tax_vat,"string"),
      );
      $commonsocket->Erp_customer_update($customer_id, "Vat Tax", $vat_array, $partner_id);
    }

    return array($partner_id, $partner_invoice_id, $partner_shipping_id);
  }

  public function createErpAddress($flat_address, $parent_id, $mage_customer_id, $mage_address_id, $store_id=0)
  {
    $flag = false;
    $erp_cus_id = 0;
    $address_array = array();
    $model = Mage::getModel('magerpsync/magerpsync');
    $address_array = $this->CustomerAddressArray($flat_address);
    if($mage_address_id > 0){
      $addresscollection =  $model->getCollection()
                    ->addFieldToFilter('mage_cus_id',array('eq'=>$mage_customer_id))
                    ->addFieldToFilter('mage_address_id',array('eq'=>$mage_address_id));
      if(count($addresscollection)>0){
        foreach($addresscollection as $add){
          $mapId = $add->getEntityId();
          $erp_cus_id = $add->getErpCusId();
        }
      }else{
        $flag = true;
      }
    }else{ 
      $flag = true;
    }
    if($flag == true){
      if($address_array){
        $address_array['parent_id'] = new xmlrpcval($parent_id, "int");
        $erp_cus_id = Mage::getModel("magerpsync/commonsocket")->Erp_customer_create($address_array, $mage_customer_id, $mage_address_id, $store_id);
      }
    }
    return $erp_cus_id;
  }

    public function CustomerAddressArray($flat_address)
    {
    $type = '';
    $address_array = array();
    if($flat_address['address_type'] == 'billing'){
      $type = 'invoice';
    }
    if($flat_address['address_type'] == 'shipping')
      $type = 'delivery';
    $streets = $flat_address->getStreet();
    if(count($streets)>1){
      $street = urlencode($streets[0]);
      $street2 = urlencode($streets[1]);
    }else{
      $street = urlencode($streets[0]);
      $street2 = urlencode('');
    }
    $name = urlencode($flat_address->getName());
    $company = urlencode($flat_address->getCompany());
    $email = urlencode($flat_address->getEmail());
    $city = urlencode($flat_address->getCity());
    $region = urlencode($flat_address->getRegion());

    $address_array =  array(
      'name'=>new xmlrpcval($name,"string"),
      'street'=>new xmlrpcval($street,"string"),
      'street2'=>new xmlrpcval($street2,"string"),
      'city'=>new xmlrpcval($city,"string"),
      'email'=>new xmlrpcval($email,"string"),
      'zip'=>new xmlrpcval($flat_address->getPostcode(),"string"),
      'phone'=>new xmlrpcval($flat_address->getTelephone(),"string"),
      'fax'=>new xmlrpcval($flat_address->getFax(),"string"),
      'country_code'=>new xmlrpcval($flat_address->getCountryId(),"string"),
      'region'=>new xmlrpcval($region,"string"),
      'wk_company'=>new xmlrpcval($company,"string"),
      'customer'=>new xmlrpcval(false,"boolean"),
      'wk_address'=>new xmlrpcval(true,"boolean"),
      'type'=>new xmlrpcval($type,"string")
    );
    return $address_array;
  }
  
  public function checkAddresses($This_order)
  {
    $flag = false;    
    if($This_order->getShippingAddressId() && $This_order->getBillingAddressId()){
      $s = $This_order->getShippingAddress();
      $b = $This_order->getBillingAddress();
      
      if($s['street'] != $b['street'])
        $flag = true;
      if($s['postcode'] != $b['postcode'])
        $flag = true;
      if($s['city'] != $b['city'])
        $flag = true;
      if($s['region'] != $b['region'])
        $flag = true;
      if($s['country_id'] != $b['country_id'])
        $flag = true;
      if($s['firstname'] != $b['firstname'])
        $flag = true;
    }    
    return $flag;
  }
}
?>