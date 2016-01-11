<?php

    include("lib/xmlrpc.inc");
    include("lib/xmlrpcs.inc");
    include("lib/xmlrpc_wrappers.inc");

    $orderNumber = $_GET['orderNumber'];  
    $user = 'admin';
    $password = 'topvalue';
    $dbname = 'magento_dev';
    $server_url = 'http://52.77.224.23:8069';
    $client = new xmlrpc_client($server_url . "/xmlrpc/common");
    $client->setSSLVerifyPeer(0);

    $c_msg = new xmlrpcmsg('login');
    $c_msg->addParam(new xmlrpcval($dbname, "string"));
    $c_msg->addParam(new xmlrpcval($user, "string"));
    $c_msg->addParam(new xmlrpcval($password, "string"));
    $c_response = $client->send($c_msg);
    $uid = $c_response->value()->scalarval();
    $client = new xmlrpc_client($server_url . "/xmlrpc/object");
    $client->setSSLVerifyPeer(0);
    
    $key1 = array( new xmlrpcval($orderNumber, 'int'));
    $key = array(new xmlrpcval(
     array( new xmlrpcval('origin' , "string"), 
       new xmlrpcval('in',"string"),
       new xmlrpcval($key1,"array")),"array"),
    );
    $msg_ser = new xmlrpcmsg('execute');
    $msg_ser->addParam(new xmlrpcval($dbname, "string"));
    $msg_ser->addParam(new xmlrpcval($uid, "int"));
    $msg_ser->addParam(new xmlrpcval($password, "string"));
    $msg_ser->addParam(new xmlrpcval("sale.order", "string"));
    $msg_ser->addParam(new xmlrpcval("search", "string"));
    $msg_ser->addParam(new xmlrpcval($key, "array"));
    $resp0 = $client->send($msg_ser);
    $value_array = $resp0->value()->me['array'];
    // $value_array = $resp0->value()->scalarval();
    $order_id = $value_array[0]->me['int'];
    // echo"<pre>";print_r($order_id);die;
    $key1 = array( new xmlrpcval($order_id, 'int'));
    $key = array(new xmlrpcval(
     array( new xmlrpcval('id' , "string"), 
       new xmlrpcval('in',"string"),
       new xmlrpcval($key1,"array")),"array"),
    );
    $msg_ser = new xmlrpcmsg('execute');
    $msg_ser->addParam(new xmlrpcval($dbname, "string"));
    $msg_ser->addParam(new xmlrpcval($uid, "int"));
    $msg_ser->addParam(new xmlrpcval($password, "string"));
    $msg_ser->addParam(new xmlrpcval("sale.order", "string"));
    $msg_ser->addParam(new xmlrpcval("search", "string"));
    $msg_ser->addParam(new xmlrpcval($key, "array"));
    $resp0 = $client->send($msg_ser);
    // $value_array = $resp0->value()->me['array'];
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
      $msg_ser1->addParam(new xmlrpcval($dbname, "string"));
      $msg_ser1->addParam(new xmlrpcval($uid, "int"));
      $msg_ser1->addParam(new xmlrpcval($password, "string"));
      $msg_ser1->addParam(new xmlrpcval("sale.order", "string"));
      $msg_ser1->addParam(new xmlrpcval("read", "string"));
      $msg_ser1->addParam(new xmlrpcval($value_array, "array"));
      $msg_ser1->addParam(new xmlrpcval($field_list, "array"));
      $msg_ser1->addParam(new xmlrpcval($context, "struct"));
      $resp1 = $client->send($msg_ser1);

      $val = $resp1->value()->me['array'][$x];
      $sale_order[$x] = array(
        'id' => $val->me['struct']['id']->me['int'],
        'origin' => $val->me['struct']['origin']->me['string'],
        'create_date' => $val->me['struct']['create_date']->me['string'],
        'amount_untaxed' => $val->me['struct']['amount_untaxed']->me['double'],
        'amount_tax' => $val->me['struct']['amount_tax']->me['double'],
        'company_name' => $val->me['struct']['company_id']->me['array'][1]->me['string'],
        'state' => $val->me['struct']['state']->me['string'],
        'pricelist' => $val->me['struct']['pricelist_id']->me['array'][1]->me['string'],
        'validity_date' => $val->me['struct']['validity_date']->me['boolean'],
        'payment_term_id' => $val->me['struct']['payment_term_id']->me['boolean'],
        'write_date' => $val->me['struct']['write_date']->me['string'],
        'partner_invoice_id' => $val->me['struct']['partner_invoice_id']->me['array'][0]->me['int'],
        'user_id' => $val->me['struct']['user_id']->me['boolean'],
        'amount_total' => $val->me['struct']['amount_total']->me['double'],
        'invoice_status' => $val->me['struct']['invoice_status']->me['string'],
        'name' => $val->me['struct']['name']->me['string'],
        'partner_shipping_name' => $val->me['struct']['partner_shipping_id']->me['array'][1]->me['string'],
        'warehouse_name' => $val->me['struct']['warehouse_id']->me['array'][1]->me['string'],
        'carrier_name' => $val->me['struct']['carrier_id']->me['array'][1]->me['string'],
        'delivery_price' => $val->me['struct']['delivery_price']->me['double'],
        'invoice_shipping_on_delivery' => $val->me['struct']['invoice_shipping_on_delivery']->me['boolean'],
        'payment_method' => $val->me['struct']['payment_method']->me['boolean'],

      );

      $key1 = array( new xmlrpcval($order_id, 'int'));
      $key = array(new xmlrpcval(
       array( new xmlrpcval('order_id' , "string"), 
         new xmlrpcval('in',"string"),
         new xmlrpcval($key1,"array")),"array"),
      );
      $msg_ser = new xmlrpcmsg('execute');
      $msg_ser->addParam(new xmlrpcval($dbname, "string"));
      $msg_ser->addParam(new xmlrpcval($uid, "int"));
      $msg_ser->addParam(new xmlrpcval($password, "string"));
      $msg_ser->addParam(new xmlrpcval("sale.order.line", "string"));
      $msg_ser->addParam(new xmlrpcval("search", "string"));
      $msg_ser->addParam(new xmlrpcval($key, "array"));
      $resp0 = $client->send($msg_ser);
      $value_array = $resp0->value()->scalarval();
      $count2 = count($value_array);  
      for($y=0;$y<$count2;$y++){
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
        $msg_ser1->addParam(new xmlrpcval($dbname, "string"));
        $msg_ser1->addParam(new xmlrpcval($uid, "int"));
        $msg_ser1->addParam(new xmlrpcval($password, "string"));
        $msg_ser1->addParam(new xmlrpcval("sale.order.line", "string"));
        $msg_ser1->addParam(new xmlrpcval("read", "string"));
        $msg_ser1->addParam(new xmlrpcval($value_array, "array"));
        $msg_ser1->addParam(new xmlrpcval($field_list, "array"));
        $msg_ser1->addParam(new xmlrpcval($context, "struct"));
        $resp1 = $client->send($msg_ser1);
        $val = $resp1->value()->me['array'][$y];
        // echo"<pre>";print_r($val);die;
        $sale_order[$x]['product'][$y] = array(
          'sequence' => $val->me['struct']['sequence']->me['int'],
          'product_id' => $val->me['struct']['product_id']->me['array'][0]->me['int'],
          'name' => $val->me['struct']['name']->me['string'],
          'product_uom' => $val->me['struct']['product_uom']->me['array'][1]->me['string'],
          'product_uom_qty' => $val->me['struct']['product_uom_qty']->me['double'],
          'qty_to_invoice' => $val->me['struct']['qty_to_invoice']->me['double'],
          'qty_delivered' => $val->me['struct']['qty_delivered']->me['double'],
          'price_unit' => $val->me['struct']['price_unit']->me['double'],
          'price_tax' => $val->me['struct']['price_tax']->me['double'],
          'price_reduce' => $val->me['struct']['price_reduce']->me['double'],
          'discount' => $val->me['struct']['discount']->me['double'],
          'price_subtotal' => $val->me['struct']['price_subtotal']->me['double'],
          'price_total' => $val->me['struct']['price_total']->me['double'],
          'salesman_id' => $val->me['struct']['salesman_id']->me['boolean'],
          'order_partner_id' => $val->me['struct']['order_partner_id']->me['array'][0]->me['int'],
          'order_partner_name' =>$val->me['struct']['order_partner_id']->me['array'][1]->me['string'],
          'route_id' => $val->me['struct']['route_id']->me['boolean'],
          'is_delivery' => $val->me['struct']['is_delivery']->me['boolean'],
          'state' => $val->me['struct']['state']->me['string'],

        );
      }  
    }   
    $data_final = json_encode($sale_order[0]);
    print_r($data_final);

  
    
?>