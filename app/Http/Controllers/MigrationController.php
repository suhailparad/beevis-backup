<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderInvoice;
use App\Models\OrderTax;
use App\Models\Post;
use App\Models\PostMeta;
use App\Models\Product;
use App\Models\State;
use App\Models\TaxRate;
use App\Models\UserRole;
use App\Models\WpCourierTracking;
use App\Models\WpWocommerceTaxRate;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MigrationController extends Controller
{
    //

    public function migrateUser(){
        $res = DB::select("SELECT
        wp_users.ID,
        wp_users.user_login,
        wp_users.user_pass,
        wp_users.user_nicename,
        wp_users.user_email,
        wp_users.user_url,
        wp_users.user_registered,
        wp_users.user_activation_key,
        wp_users.user_status,
        wp_users.display_name,
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_first_name' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_first_name',
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_last_name' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_last_name',
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_email' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_email',
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_phone' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_phone'
        FROM
        wp_users
        INNER JOIN wp_usermeta ON wp_usermeta.user_id = wp_users.ID
        GROUP BY
            wp_users.ID");

        DB::beginTransaction();
        try{
            foreach($res as $result){
                $user = DB::table('users')->insert(
                    array(
                        'id'     =>   $result->ID,
                        'first_name'   =>   $result->billing_first_name,
                        'last_name'   =>   $result->billing_last_name,
                        'email' => $result->user_email,
                        'password' => $result->user_pass,
                        'phone' => $result->billing_phone,
                        'gender' => null,
                        'dob' => null,
                        'created_at' => $result->user_registered
                    )
               );
               //Insert into user roles table also
            }
            DB::commit();
        }catch(Exception $ex){
            DB::rollBack();
            return $ex->getMessage();
        }
    }

    public function migrateOrder(){

        $orders = Post::where('post_type','shop_order')
            ->whereDate('post_date','>=','2021-12-01')
            ->whereDate('post_date','<=','2021-12-15')
            ->orderBy('post_date')
            ->with('meta')
            ->with(['items'=>function($q){
                $q->with('meta');
            }])->with(['comments'=>function($q){
                $q->with('meta');
            }])->get();

        $orders_array = [];
        foreach($orders as $order){
            $array=[];
            $order_billing_array =[];
            $order_shipping_array =[];

            $order_transaction = [];

            $array['id'] = $order->ID;
            $array['date'] = $order->post_date;
            $array['created_at'] = $order->post_date;
            $array['updated_at'] = $order->post_date;
            $array['is_guest'] = false;
            $array['channel_id'] = 1;
            $array['priority'] = 'Normal';
            $array['platform'] = 'Online';
            $array['status'] = $this->getStatus($order->post_status);
            $array['event_tracked'] = true;

            $order_address_array['order_id'] = $order->ID;

            foreach($order->meta as $meta){
                switch($meta->meta_key){
                    case "_order_key":$array['token']=$meta->meta_value;break;
                    case "_customer_user":$array['customer_id']=$meta->meta_value;break;
                    case "_order_tax":$array['tax_total']=$meta->meta_value;break;
                    case "_order_total":$array['grand_total']=$meta->meta_value;break;
                    case "_billing_email" : $array['email'] = $meta->meta_value;break;
                    case "_billing_phone" : $array['phone'] = $meta->meta_value;break;
                    case "_order_admin_status" : $array['order_admin_status'] = $meta->meta_value;break;
                    case "_invoice_no" : $array['invoice_no'] = $meta->meta_value;break;

                    case "_billing_first_name" :$order_billing_array['first_name']=$meta->meta_value;break;
                    case "_billing_last_name" :$order_billing_array['last_name']=$meta->meta_value;break;
                    case "_billing_address_1" :$order_billing_array['address_1']=$meta->meta_value;break;
                    case "_billing_address_2" :$order_billing_array['address_2']=$meta->meta_value;break;
                    case "_billing_city" :$order_billing_array['city']=$meta->meta_value;break;
                    case "_billing_state" :$order_billing_array['state_id']=$this->getStateId($meta->meta_value);break;
                    case "_billing_postcode" :$order_billing_array['zip']=$meta->meta_value;break;

                    case "_shipping_first_name" :$order_shipping_array['first_name']=$meta->meta_value;break;
                    case "_shipping_last_name" :$order_shipping_array['last_name']=$meta->meta_value;break;
                    case "_shipping_address_1" :$order_shipping_array['address_1']=$meta->meta_value;break;
                    case "_shipping_address_2" :$order_shipping_array['address_2']=$meta->meta_value;break;
                    case "_shipping_city" :$order_shipping_array['city']=$meta->meta_value;break;
                    case "_shipping_state" :$order_shipping_array['state_id']=$this->getStateId($meta->meta_value);break;
                    case "_shipping_postcode" :$order_shipping_array['zip']=$meta->meta_value;break;

                    case "_paid_date" : $order_transaction['transaction_date'] = $meta->meta_value;break;
                    case "_transaction_id" : $order_transaction['transaction_no'] = $meta->meta_value;break;
                    case "_payment_method" : $order_transaction['payment_method_id'] = $this->getPaymentMethod($meta->meta_value);break;
                }
            }

            $order_billing_array['country_id']=1;
            $order_billing_array['address_type']="Billing";

            $order_shipping_array['country_id']=1;
            $order_shipping_array['phone']=$array['phone'];
            $order_shipping_array['address_type']="Shipping";

            $order_shipping_array['delivery_type']='Home';

            $line_items = $order->items->where('order_item_type','line_item'); //Refactor

            $fee = $order->items->where('order_item_type','fee'); //Refactor

            $array['items_count'] = $line_items->count();

            $sub_total = 0;

            foreach($line_items as $item){

                foreach($item->meta as $meta){
                    switch($meta->meta_key){
                        case '_line_total': $sub_total+=$meta->meta_value;break;
                    }
                }
            }

            $array['sub_total'] = $sub_total;

            $order_products = $this->makeOrderItems($line_items,$array);
            $array['products'] = $order_products;
            $array['billing_address']  = $order_billing_array;
            $array['shipping_address']  = $order_shipping_array;
            $addons = $this->makeOrderAddontems($fee,$array);

            $order_transaction['parent_id'] = $array['id'];
            $order_transaction['parent_type'] = 'order';
            $order_transaction['amount'] = $array['grand_total'];
            $order_transaction['mode'] = "in";
            $order_transaction['isPrimary'] = true;

            $array['addons'] = $addons;
            $array['transactions'] = $order_transaction;

            $history = [];
            foreach($order->comments as $comment){
                $type='note';
                $platform="";
                foreach($comment->meta as $meta){
                    if($meta->meta_key=="order_note_type" && $meta->meta_value=="communication"){
                        $type='communication';
                    }else if($meta->meta_key=="order_note_type" && $meta->meta_value=="history"){
                        $type='history';
                    }
                    if($meta->meta_key=="order_communication_platform"){
                        $platform= $meta->meta_value;
                    }
                }
                $order_comment = [
                    'order_id' => $order->ID,
                    'type' => $type,
                    'note' => $comment->comment_content,
                    'date' => $comment->comment_date,
                    'user_id' => 1,
                    'platform' => $platform
                ];
                array_push($history,$order_comment);
            }

            $array['comments'] = $history;

            array_push($orders_array,$array);
        }
        return $this->migrateToDb($orders_array);
    }

    public function getStatus($wp_status){
        return match($wp_status){
            'wc-completed'=> 'Completed',
            'wc-cancelled'=> 'Cancelled',
            'wc-failed' => 'Unpaid',
            'wc-on-hold' => 'Hold',
            'wc-pending' => 'Unpaid',
            'wc-processing' => 'Processing'
        };
    }

    public function makeOrderItems($line_items,$order){
       // \Log::info($order['id']);
        $items =[];
        foreach($line_items as $item){
            $item_array=[];
            $item_array['order_id'] = $item->order_id;
            foreach($item->meta as $meta){
                switch($meta->meta_key){
                    case '_variation_id': $item_array['product_id']=$this->getProductId($meta->meta_value);break;
                    case '_product_id': $item_array['parent_id']=$this->getProductId($meta->meta_value);break;
                    case '_line_tax' : $item_array['tax_amount']=$meta->meta_value;break;
                    case '_tax_class' : $item_array['tax_percentage']=$this->getTaxPercentage($meta->meta_value);break;
                    case '_line_total' : $item_array['taxable_amount']=$meta->meta_value;break;
                    case '_qty' : $item_array['quantity']=$meta->meta_value;break;
                    case '_line_tax_data' : $item_array['tax_data'] = $meta->meta_value;break;
                }
            }
            $item_array['price'] = $item_array['tax_amount'] + $item_array['taxable_amount'];
            $item_array['total'] = $item_array['price'] * $item_array['quantity'];
            $item_array['type']  = 'product';
            $item_array['created_at'] = $order['created_at'];
            $item_array['updated_at'] = $order['updated_at'];

            // $unserialized = unseri

            array_push($items, $item_array);
        }
        return $items;
    }

    public function makeOrderAddontems($fee,$order){

        $items =[];
        foreach($fee as $item){
            $item_array=[];
            $item_array['order_id'] = $item->order_id;
            foreach($item->meta as $meta){
                switch($meta->meta_key){
                    case '_line_tax' : $item_array['tax_amount']=$meta->meta_value;break;
                    case '_line_total' : $item_array['taxable_amount']=$meta->meta_value;break;
                    case '_line_tax_data' : $item_array['tax_data'] = $meta->meta_value;break;
                }
            }
            $item_array['_product_id'] = 2;
            $item_array['tax_percentage']=$this->getAddonTaxPercentage($order);
            $item_array['quantity']=1;
            $item_array['price'] = $item_array['tax_amount'] + $item_array['taxable_amount'];
            $item_array['total'] = $item_array['price'] * $item_array['quantity'];
            $item_array['type']  = 'add_on_item';
            $item_array['created_at'] = $order['created_at'];
            $item_array['updated_at'] = $order['updated_at'];
            array_push($items, $item_array);
        }
        return $items;
    }

    public function getTaxPercentage($tax_class){
        return match($tax_class){
            'gst12' => 12,
            'gst18' => 18,
            'gst5' => 5,
        };
    }

    public function getAddonTaxPercentage($order){
        $items = $order['products'];
        $kfc_enabled=false;
        $kerala_state_id = 18;

        foreach($items as $item){
            if($item['tax_percentage']==12){
                $kfc_enabled=true;
            }
        }
        if($kerala_state_id == $order['shipping_address']['state_id']){
            if($kfc_enabled){
                $tax_percentage = 12;
            }else{
                $tax_percentage  =5;
            }
        }else{
            $tax_percentage = 12;
        }

        return $tax_percentage;
    }

    public function getStateId($code){
        $state = State::where('code',$code)->first();
        if($state)
            return $state->id;
        else
            return null;
    }
    public function getPaymentMethod($method){
        return match($method){
            'cod'=>1,
            'instamojo'=>2,
        };

    }

    public function migrateTaxRate(){
        foreach(WpWocommerceTaxRate::where('tax_rate_id','>',198)->get() as $wp_rates){
            $state_id = $this->getStateId($wp_rates->tax_rate_state);

            $tax_class = null;
            if($wp_rates->tax_rate_class=="gst5")
                $tax_class=1;
            else if($wp_rates->tax_rate_class=="gst12")
                $tax_class=2;
            else if($wp_rates->tax_rate_class=="gst18")
                $tax_class=3;

            if($state_id){

                $rate = TaxRate::where('state_id',$state_id)
                    ->where('priority',$wp_rates->tax_rate_priority)
                    ->where('rate',$wp_rates->tax_rate)
                    ->where('tax_class_id',$tax_class)
                    ->first();

                if($rate){
                    $rate->update([
                        'tax_rate_id'=>$wp_rates->tax_rate_id
                    ]);
                }
            }
        }
    }

    public function getProductId($variation_id){

        $product = Product::where('variation_id',$variation_id)->first();
        if($product)
            return $product->id;
        else
            return null;
    }

    /* MIGRATE */
    public function migrateToDb($prepared_order){
       // return $prepared_order;
        DB::beginTransaction();
        try{
            foreach($prepared_order as $wp_order){
                $order = Order::create($wp_order);

                $order->orderHistory()->createMany($wp_order['comments']);
                $order->billingAddress()->create($wp_order['billing_address']);
                $order->shippingAddress()->create($wp_order['shipping_address']);

                $sub_total = 0;
                $addon_total = 0;
                foreach($wp_order['products'] as $item){
                    $sub_total+=$item['total'];
                   // \Log::info($item);
                    if(!$item['product_id'])
                        $item['product_id'] = $item['parent_id'];

                    $_item = $order->products()->create($item);

                    $unserialized = unserialize($item['tax_data'])['total'];
                    foreach(array_keys($unserialized) as $tax_rates){
                        $tax = TaxRate::where('tax_rate_id',$tax_rates)->first();
                        $tax_rate_id = $tax->id;
                        $tax_rate = $tax->rate;
                        $amount = $unserialized[$tax_rates];

                        OrderTax::create([
                            'order_item_id'=>$_item->id,
                            'tax_rate_id'=>$tax_rate_id,
                            'rate'=>$tax_rate,
                            'tax_amount'=>$amount,
                        ]);
                    }
                }

                foreach($wp_order['addons'] as $item){

                    $addon_total+=$item['total'];
                    $_addon_item = $order->addons()->create($item);
                    $unserialized = unserialize($item['tax_data'])['total'];
                    foreach(array_keys($unserialized) as $tax_rates){
                        $tax = TaxRate::where('tax_rate_id',$tax_rates)->first();
                        // if(!$tax){
                            // if($wp_order['status'] == 'Unpaid' || $wp_order['status'] == 'Cancelled'){
                            //     $tax_rate_id = 57;
                            //     $tax_rate = 12;
                            // }
                       // }
                        // else {
                            $tax_rate_id = $tax->id;
                            $tax_rate = $tax->rate;
                        //}

                        $amount = $unserialized[$tax_rates];

                        OrderTax::create([
                            'order_item_id'=>$_addon_item->id,
                            'tax_rate_id'=>$tax_rate_id,
                            'rate'=>$tax_rate,
                            'tax_amount'=>$amount,
                        ]);
                    }
                }

                if($order->status != 'Unpaid' && $order->status != 'Cancelled'){
                    //Transaction
                   // \Log::info($wp_order['transactions']);
                    if($wp_order['transactions']['payment_method_id'] == 1)
                    $wp_order['transactions']['transaction_date'] = $order->date;
                    $order->transactions()->create($wp_order['transactions']);

                    $wp_courier_tracking = WpCourierTracking::where('order_id', $order->id)->first();

                    if($wp_courier_tracking){
                        \Log::info($wp_order['id']);
                        $shipment_array = [
                            'order_id' => $wp_order['id'],

                            'status' => match($wp_order['order_admin_status']){
                                    'ready' => 'Created',
                                    'invoiced' => 'Created',
                                    'todespatch' => 'Packed',
                                    'packed' => 'Packed',
                                    'shipped' => 'Shipped'
                                },
                            'warehouse_id' => 1,
                            'courier_id' => $wp_courier_tracking->courier_id,
                            'length' => $wp_courier_tracking->qd_pack_length,
                            'breadth' => $wp_courier_tracking->qd_pack_breadth,
                            'height' => $wp_courier_tracking->qd_pack_height,
                            'weight' => $wp_courier_tracking->qd_pack_weight,
                            'waybill_no' => $wp_courier_tracking->AWBNo,
                            'shipment_type' => 'forward',
                            'date_time' => $wp_courier_tracking->qd_pack_pick_up_date." 16:00:00",
                            'sub_total' => $sub_total,
                            'addon_total' => $addon_total,
                            'grand_total' => $order->grand_total
                        ];

                        $shipment = $order->shipments()->create($shipment_array);
                        if(!isset($wp_order['invoice_no'])){
                            if($wp_order['id'] == 29851){
                                $wp_order['invoice_no'] = 7423;
                            }
                            else if($wp_order['id'] == 29853){
                                $wp_order['invoice_no'] = 7422;
                            }
                            else if($wp_order['id'] == 29854){
                                $wp_order['invoice_no'] = 7421;
                            }
                        }
                        $order_invoice = OrderInvoice::create([
                            'shipment_id' => $shipment->id,
                            'order_id' => $order->id,
                            'invoice_no' => $wp_order['invoice_no'],
                            'invoice_date' => $shipment->date_time
                        ]);

                       // $order_invoice = OrderInvoice::create($invoice_master);
                        foreach($order->products as $product){
                            $shipment_items =[
                                'shipment_id' => $shipment->id,
                                'product_id' => $product->product_id,
                                'order_item_id' => $product->id,
                                'quantity' => $product->quantity,
                                'price' => $product->price,
                                'total' => $product->total
                            ];

                            $invoice_items =[
                                'order_invoice_id' => $order_invoice->id,
                                'product_id' => $product->product_id,
                                'quantity' => $product->quantity,
                                'total_amount' => $product->total
                            ];
                            $shipment->items()->create($shipment_items);
                            $order_invoice->items()->create($invoice_items);
                        }
                    }
                }
            }
            DB::commit();
            echo "done";
        }catch(Exception $ex){
            DB::rollBack();
            return $ex->getMessage().' '.$ex->getLine();
        }
    }

    public function migrateUserRoles(){
        DB::beginTransaction();
        try{
            foreach (User::where('id','>',1)->get() as $user) {
                UserRole::create([
                    'user_id' => $user->id,
                    'role_id' => 2
                ]);
            }
            DB::commit();
            echo "done";
        }catch(Exception $ex){
            DB::rollBack();
            return $ex->getMessage().' '.$ex->getLine();
        }
    }

}
