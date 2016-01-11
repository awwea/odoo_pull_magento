<?php

class Bizilent_Magerpsync_Adminhtml_Magerpsync_TestController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('magerpsync/magerpsync')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Test Manager'), Mage::helper('adminhtml')->__('Test Manager'));
		
		return $this;
	}
 
	public function indexAction($order_id) {
	  $helper = Mage::helper('magerpsync/connection');
   	$userId = Mage::getSingleton('adminhtml/session')->getUserId();
		$helper = Mage::helper('magerpsync/connection');
	  $context = $helper->getOdooContext();
	  $client = $helper->getClientConnect();
    $order_id = $this->getRequest()->getParam('order_id');
    $sale_order = array();
    // $key = array();
    $key1 = array( new xmlrpcval($order_id, 'int'));
    $key = array(new xmlrpcval(
     array( new xmlrpcval('id' , "string"), 
       new xmlrpcval('in',"string"),
       new xmlrpcval($key1,"array")),"array"),
    );
    $msg_ser = new xmlrpcmsg('execute');
    $msg_ser->addParam(new xmlrpcval($helper::$odoo_db, "string"));
    $msg_ser->addParam(new xmlrpcval($userId, "int"));
    $msg_ser->addParam(new xmlrpcval($helper::$odoo_pwd, "string"));
    $msg_ser->addParam(new xmlrpcval("sale.order", "string"));
    $msg_ser->addParam(new xmlrpcval("search", "string"));
    $msg_ser->addParam(new xmlrpcval($key, "array"));
    $resp0 = $client->send($msg_ser);
    $value_array = $resp0->value()->scalarval();
    $count = count($value_array); 
    for($x=0;$x<$count;$x++){


      $field_list = array(
        new xmlrpcval("id", "string"),
        new xmlrpcval("origin", "string"),
        new xmlrpcval("create_date", "string"),
        new xmlrpcval("date_order", "string"),
        new xmlrpcval("amount_untaxed", "string"),
        new xmlrpcval("company_id", "string"),
        new xmlrpcval("state", "string"),
        new xmlrpcval("pricelist_id", "string"),
        new xmlrpcval("amount_tax", "string"),
        new xmlrpcval("validity_date", "string"),
        new xmlrpcval("payment_term_id", "string"),
        new xmlrpcval("write_date", "string"),
        new xmlrpcval("partner_invoice_id", "string"),
        new xmlrpcval("user_id", "string"),
        new xmlrpcval("amount_total", "string"),
        new xmlrpcval("invoice_status", "string"),
        new xmlrpcval("name", "string"),
        new xmlrpcval("partner_shipping_id", "string"),
        new xmlrpcval("warehouse_id", "string"),
        new xmlrpcval("carrier_id", "string"),
        new xmlrpcval("delivery_price", "string"),
        new xmlrpcval("invoice_shipping_on_delivery", "string"),
        new xmlrpcval("payment_method", "string"),
      );
      $msg_ser1 = new xmlrpcmsg('execute');
      $msg_ser1->addParam(new xmlrpcval($helper::$odoo_db, "string"));
      $msg_ser1->addParam(new xmlrpcval($userId, "int"));
      $msg_ser1->addParam(new xmlrpcval($helper::$odoo_pwd, "string"));
      $msg_ser1->addParam(new xmlrpcval("sale.order", "string"));
      $msg_ser1->addParam(new xmlrpcval("read", "string"));
      $msg_ser1->addParam(new xmlrpcval($value_array, "array"));
      $msg_ser1->addParam(new xmlrpcval($field_list, "array"));
      $msg_ser1->addParam(new xmlrpcval($context, "struct"));
      $resp1 = $client->send($msg_ser1);
      $val = $resp1->value()->me['array'][$x];
    
      $sale_order['id'] = $val->me['struct']['id']->me['int'];
      $sale_order['origin'] = $val->me['struct']['origin']->me['string'];
      $sale_order['create_date'] = $val->me['struct']['create_date']->me['string'];
      $sale_order['amount_untaxed'] = $val->me['struct']['amount_untaxed']->me['double'];
      $sale_order['amount_tax'] = $val->me['struct']['amount_tax']->me['double'];
      $sale_order['company_name'] = $val->me['struct']['company_id']->me['array'][1]->me['string'];
      $sale_order['state'] = $val->me['struct']['state']->me['string'];
      $sale_order['pricelist_name'] = $val->me['struct']['pricelist_id']->me['array'][1]->me['string'];
      $sale_order['validity_date'] = $val->me['struct']['validity_date']->me['boolean'];
      $sale_order['payment_term_id'] = $val->me['struct']['payment_term_id']->me['boolean'];
      $sale_order['write_date'] = $val->me['struct']['write_date']->me['string'];
      $sale_order['partner_invoice_id'] = $val->me['struct']['partner_invoice_id']->me['array'][0]->me['int'];
      $sale_order['user_id'] = $val->me['struct']['user_id']->me['boolean'];
      $sale_order['amount_total'] = $val->me['struct']['amount_total']->me['double'];
      $sale_order['invoice_status'] = $val->me['struct']['invoice_status']->me['string'];
      $sale_order['name'] = $val->me['struct']['name']->me['string'];
      $sale_order['partner_shipping_name'] = $val->me['struct']['partner_shipping_id']->me['array'][1]->me['string'];
      $sale_order['warehouse_name'] = $val->me['struct']['warehouse_id']->me['array'][1]->me['string'];
      $sale_order['carrier_name'] = $val->me['struct']['carrier_id']->me['array'][1]->me['string'];
      $sale_order['delivery_price'] = $val->me['struct']['delivery_price']->me['double'];
      $sale_order['invoice_shipping_on_delivery'] = $val->me['struct']['invoice_shipping_on_delivery']->me['boolean'];
      $sale_order['payment_method'] = $val->me['struct']['payment_method']->me['boolean'];

      $key1 = array( new xmlrpcval($order_id, 'int'));
      $key = array(new xmlrpcval(
       array( new xmlrpcval('order_id' , "string"), 
         new xmlrpcval('in',"string"),
         new xmlrpcval($key1,"array")),"array"),
      );
      $msg_ser = new xmlrpcmsg('execute');
      $msg_ser->addParam(new xmlrpcval($helper::$odoo_db, "string"));
      $msg_ser->addParam(new xmlrpcval($userId, "int"));
      $msg_ser->addParam(new xmlrpcval($helper::$odoo_pwd, "string"));
      $msg_ser->addParam(new xmlrpcval("sale.order.line", "string"));
      $msg_ser->addParam(new xmlrpcval("search", "string"));
      $msg_ser->addParam(new xmlrpcval($key, "array"));
      $resp0 = $client->send($msg_ser);
      $value_array = $resp0->value()->scalarval();
      $count = count($value_array);  
      for($y=0;$y<$count;$y++){
        $field_list = array(
          new xmlrpcval("id", "string"),
          new xmlrpcval("product_uom", "string"),
          new xmlrpcval("sequence", "string"),
          new xmlrpcval("price_unit", "string"),
          new xmlrpcval("product_uom_qty", "string"),
          new xmlrpcval("price_subtotal", "string"),
          new xmlrpcval("price_tax", "string"),
          new xmlrpcval("state", "string"),
          new xmlrpcval("order_partner_id", "string"),
          new xmlrpcval("discount", "string"),
          new xmlrpcval("price_reduce", "string"),
          new xmlrpcval("qty_delivered", "string"),
          new xmlrpcval("price_total", "string"),
          new xmlrpcval("name", "string"),
          new xmlrpcval("salesman_id", "string"),
          new xmlrpcval("product_id", "string"),
          new xmlrpcval("route_id", "string"),
          new xmlrpcval("is_delivery", "string"),
          new xmlrpcval("qty_to_invoice", "string"),
          // new xmlrpcval("invoice_shipping_on_delivery", "string"),
          // new xmlrpcval("payment_method", "string"),
        );
        $msg_ser1 = new xmlrpcmsg('execute');
        $msg_ser1->addParam(new xmlrpcval($helper::$odoo_db, "string"));
        $msg_ser1->addParam(new xmlrpcval($userId, "int"));
        $msg_ser1->addParam(new xmlrpcval($helper::$odoo_pwd, "string"));
        $msg_ser1->addParam(new xmlrpcval("sale.order.line", "string"));
        $msg_ser1->addParam(new xmlrpcval("read", "string"));
        $msg_ser1->addParam(new xmlrpcval($value_array, "array"));
        $msg_ser1->addParam(new xmlrpcval($field_list, "array"));
        $msg_ser1->addParam(new xmlrpcval($context, "struct"));
        $resp1 = $client->send($msg_ser1);
        $val = $resp1->value()->me['array'][$y];
        // echo"<pre>";print_r($val);die;
        $sale_order[$y]['sequence']= $val->me['struct']['sequence']->me['int'];
        $sale_order[$y]['product_id']= $val->me['struct']['product_id']->me['array'][0]->me['int'];
        $sale_order[$y]['name']= $val->me['struct']['name']->me['string'];
        $sale_order[$y]['product_uom']= $val->me['struct']['product_uom']->me['array'][1]->me['string'];
        $sale_order[$y]['product_uom_qty']= $val->me['struct']['product_uom_qty']->me['double'];
        $sale_order[$y]['qty_to_invoice']= $val->me['struct']['qty_to_invoice']->me['double'];
        $sale_order[$y]['qty_delivered']= $val->me['struct']['qty_delivered']->me['double'];
        $sale_order[$y]['price_unit']= $val->me['struct']['price_unit']->me['double'];
        $sale_order[$y]['price_tax']= $val->me['struct']['price_tax']->me['double'];
        $sale_order[$y]['price_reduce']= $val->me['struct']['price_reduce']->me['double'];
        $sale_order[$y]['discount']= $val->me['struct']['discount']->me['double'];
        $sale_order[$y]['price_subtotal']= $val->me['struct']['price_subtotal']->me['double'];
        $sale_order[$y]['price_total']= $val->me['struct']['price_total']->me['double'];
        $sale_order[$y]['salesman_id']= $val->me['struct']['salesman_id']->me['boolean'];
        $sale_order[$y]['order_partner_id']= $val->me['struct']['order_partner_id']->me['array'][0]->me['int'];
        $sale_order[$y]['order_partner_name']= $val->me['struct']['order_partner_id']->me['array'][1]->me['string'];
        $sale_order[$y]['route_id']= $val->me['struct']['route_id']->me['boolean'];
        $sale_order[$y]['is_delivery']= $val->me['struct']['is_delivery']->me['boolean'];
        $sale_order[$y]['state']= $val->me['struct']['state']->me['string'];
        
        
      }  

    }   

    echo "<pre>";
    print_r($sale_order);
	  
	}

}