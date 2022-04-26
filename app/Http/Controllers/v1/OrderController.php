<?php

namespace App\Http\Controllers\v1;

use App\Facades\DataFetcher;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderInvoice;
use App\Models\OrderTax;
use App\Models\Post;
use App\Models\TaxRate;
use App\Models\Wallet;
use App\Models\WpCourierTracking;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    //

    public function migrate(){
        $id=0;
        DB::beginTransaction();
        try{

            $wp_orders=$this->prepareOrderData();

            foreach($wp_orders as $wp_order){
                $order = Order::create($wp_order);

                $order->orderHistory()->createMany($wp_order['comments']);
                $order->billingAddress()->create($wp_order['billing_address']);
                $order->shippingAddress()->create($wp_order['shipping_address']);

                $sub_total = 0;
                foreach($wp_order['products'] as $item){
                    $sub_total+=$item['total'];
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

                $addon_total = 0;
                foreach($wp_order['addons'] as $item){

                    $addon_total+=$item['total'];
                    $_addon_item = $order->addons()->create($item);
                    $unserialized = unserialize($item['tax_data'])['total'];
                    foreach(array_keys($unserialized) as $tax_rates){
                        $tax = TaxRate::where('tax_rate_id',$tax_rates)->first();
                        $tax_rate_id = $tax->id;
                        $tax_rate = $tax->rate;
                        $amount = $unserialized[$tax_rates];
                        OrderTax::create([
                            'order_item_id'=>$_addon_item->id,
                            'tax_rate_id'=>$tax_rate_id,
                            'rate'=>$tax_rate,
                            'tax_amount'=>$amount,
                        ]);
                    }
                }
            }

            //Transaction (primary)
            if($wp_order['transactions']['payment_method_id'] == 1)
                $wp_order['transactions']['transaction_date'] = $order->date;
            $order->transactions()->create($wp_order['transactions']);

            //Wallet Tranctions (secondary)
            $order->transactions()->create($wp_order['wallet_transaction']);

            if($order->status=="Hold"){
                $order->custom_note()->create([
                    'type' => $wp_order['order_hold_reason'],
                    'note' => $wp_order['order_hold_details']
                ]);
            }

            if($order->status=="Cancelled"){
                $order->custom_note()->create([
                    'type' => $wp_order['order_cancel_reason'],
                    'note' => $wp_order['order_cancel_details']
                ]);
            }

            //Order Discounts
            $order->discounts()->createMany($wp_order['discounts']);

            //Shipments and Invoices
            if($order->status != 'Unpaid' && $order->status != 'Cancelled'){

                $wp_courier_tracking = WpCourierTracking::where('order_id', $order->id)->first();

                if($wp_courier_tracking){
                    $shipment_array = [
                        'order_id' => $wp_order['id'],

                        'status' => match($wp_order['order_admin_status']){
                                'ready' => 'Created',
                                'invoiced' => 'Created',
                                'todespatch' => 'Ready to Ship',
                                'packed' => 'Packed',
                                'shipped' => 'Shipped',
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
                        $wp_order['invoice_no'] = "-";
                    }

                    $order_invoice = OrderInvoice::create([
                        'shipment_id' => $shipment->id,
                        'order_id' => $order->id,
                        'invoice_no' => $wp_order['invoice_no'],
                        'invoice_date' => $shipment->date_time
                    ]);

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
                            'rate' =>  $product->price,
                            'total_amount' => $product->total
                        ];
                        $shipment->items()->create($shipment_items);
                        $order_invoice->items()->create($invoice_items);
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success','Migration completed successfully.');
        }catch(Exception $ex){
            DB::rollBack();
            return  $id.$ex;
        }
    }

    private function prepareOrderData(){

        $orders = Post::where('post_type','shop_order')
            ->whereDate('post_date','>=',date('Y-m-d',strtotime(request()->start_date)))
            ->whereDate('post_date','<=',date('Y-m-d',strtotime(request()->end_date)))
            ->orderBy('post_date')
            ->with('meta')
            ->whereHas('items',function($q){
                $q->where('order_item_name','!=','Wallet Topup');
            })->with(['items'=>function($q){
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
            $array['status'] = DataFetcher::getOrderStatus($order->post_status);
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
                    case "_order_hold_reason" : $array['order_hold_reason'] = $meta->meta_value;break;
                    case "_order_canceled_reason" : $array['order_cancel_reason'] = $meta->meta_value;break;
                    case "_order_hold_details" : $array['order_hold_details'] = $meta->meta_value;break;
                    case "_order_canceled_details" : $array['order_cancel_details'] = $meta->meta_value;break;
                    case "_invoice_no" : $array['invoice_no'] = $meta->meta_value;break;

                    case "_billing_first_name" :$order_billing_array['first_name']=$meta->meta_value;break;
                    case "_billing_last_name" :$order_billing_array['last_name']=$meta->meta_value;break;
                    case "_billing_address_1" :$order_billing_array['address_1']=$meta->meta_value;break;
                    case "_billing_address_2" :$order_billing_array['address_2']=$meta->meta_value;break;
                    case "_billing_city" :$order_billing_array['city']=$meta->meta_value;break;
                    case "_billing_state" :$order_billing_array['state_id']= DataFetcher::getStateByCode($meta->meta_value)->id;break;
                    case "_billing_postcode" :$order_billing_array['zip']=$meta->meta_value;break;

                    case "_shipping_first_name" :$order_shipping_array['first_name']=$meta->meta_value;break;
                    case "_shipping_last_name" :$order_shipping_array['last_name']=$meta->meta_value;break;
                    case "_shipping_address_1" :$order_shipping_array['address_1']=$meta->meta_value;break;
                    case "_shipping_address_2" :$order_shipping_array['address_2']=$meta->meta_value;break;
                    case "_shipping_city" :$order_shipping_array['city']=$meta->meta_value;break;
                    case "_shipping_state" :$order_shipping_array['state_id']= DataFetcher::getStateByCode($meta->meta_value)->id;break;
                    case "_shipping_postcode" :$order_shipping_array['zip']=$meta->meta_value;break;

                    case "_paid_date" : $order_transaction['transaction_date'] = $meta->meta_value;break;
                    case "_transaction_id" : $order_transaction['transaction_no'] = $meta->meta_value;break;
                    case "_payment_method" : $order_transaction['payment_method_id'] = DataFetcher::getPaymentMethod($meta->meta_value);break;

                }
            }

            $order_billing_array['country_id']=1;
            $order_billing_array['address_type']="Billing";

            $order_shipping_array['country_id']=1;
            $order_shipping_array['phone']=$array['phone'];
            $order_shipping_array['address_type']="Shipping";

            $order_shipping_array['delivery_type']='Home';

            $line_items = $order->items->where('order_item_type','line_item');

            $fee = $order->items->where('order_item_type','fee');

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

            $order_transaction['parent_id'] = $array['id'];
            $order_transaction['parent_type'] = 'order';
            $order_transaction['amount'] = $array['grand_total'];
            $order_transaction['mode'] = "in";
            $order_transaction['isPrimary'] = true;

            $order_products = $this->prepareOrderProducts($line_items,$array);
            $addons = $this->prepareOrderAddons($fee,$array);
            $discounts = $this->prepareOrderDiscounts($fee,$array,$order_products[0]['tax_percentage']);

            $wallet_transaction = $this->prepareSecondaryOrderTrasanction($fee,$array);

            $array['products'] = $order_products;
            $array['addons'] = $addons;
            $array['transactions'] = $order_transaction;
            $array['billing_address']  = $order_billing_array;
            $array['shipping_address']  = $order_shipping_array;
            $array['wallet_transaction'] = $wallet_transaction;
            $array['discounts'] = $discounts;

            $array['products'] = $this->updateOrderItemDiscounts($array['products'],$array['discounts']);

            $array['discount_amount'] = array_sum(array_column($discounts,'amount'));

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

        return $orders_array;

    }

    private function prepareOrderProducts($line_items,$order){
        $items =[];
        foreach($line_items as $item){
            $item_array=[];
            $item_array['order_id'] = $item->order_id;
            foreach($item->meta as $meta){
                switch($meta->meta_key){
                    case '_variation_id': $item_array['product_id'] = DataFetcher::getProduct($meta->meta_value);break;
                    case '_product_id': $item_array['parent_id'] = DataFetcher::getProduct($meta->meta_value);break;
                    case '_line_tax' : $item_array['tax_amount']=$meta->meta_value;break;
                    case '_tax_class' : $item_array['tax_percentage']=DataFetcher::getTaxPercentage($meta->meta_value,$order['shipping_address']['state_id'],$order['date']);break;
                    case '_line_total' : $item_array['taxable_amount']=$meta->meta_value;break;
                    case '_qty' : $item_array['quantity']=$meta->meta_value;break;
                    case '_line_tax_data' : $item_array['tax_data'] = $meta->meta_value;break;
                }
            }
            $item_array['price'] = ($item_array['tax_amount'] + $item_array['taxable_amount'])/ $item_array['quantity'] ;
            $item_array['total'] = $item_array['price'] * $item_array['quantity'];
            $item_array['type']  = 'product';
            $item_array['created_at'] = $order['created_at'];
            $item_array['updated_at'] = $order['updated_at'];
            array_push($items, $item_array);
        }
        return $items;
    }

    private function prepareOrderAddons($items,$order){
        $items =[];
        foreach($items as $item){
            if($item->order_item_name!="Via wallet" && $item->order_item_name!="Discount" ){
                $item_array=[];
                $item_array['order_id'] = $item->order_id;
                foreach($item->meta as $meta){
                    switch($meta->meta_key){
                        case '_line_tax' : $item_array['tax_amount']=$meta->meta_value;break;
                        case '_line_total' : $item_array['taxable_amount']=$meta->meta_value;break;
                        case '_line_tax_data' : $item_array['tax_data'] = $meta->meta_value;break;
                        case '_tax_class' : $item_array['tax_percentage']=DataFetcher::getTaxPercentage($meta->meta_value,$order['shipping_address']['state_id'],$order['date']);break;
                    }
                }
                $item_array['_product_id'] = 1;
                $item_array['quantity']=1;
                $item_array['price'] = ($item_array['tax_amount'] + $item_array['taxable_amount'])/ $item_array['quantity'];
                $item_array['total'] = $item_array['price'] * $item_array['quantity'];
                $item_array['type']  = 'add_on_item';
                $item_array['created_at'] = $order['created_at'];
                $item_array['updated_at'] = $order['updated_at'];
                array_push($items, $item_array);
            }
        }
        return $items;
    }

    public function prepareSecondaryOrderTrasanction($items,$order){
        $order_transaction =[];
        foreach($items as $item){
            if($item->order_item_name == "Via wallet"){
                foreach($item->meta as $meta){
                    switch($meta->meta_key){
                        case '_line_total' : $item_array['amount']= abs($meta->meta_value);break;
                    }
                }
                $order_transaction['transaction_date'] =$order['date'];
                $order_transaction['transaction_no'] = $this->walletTransactionFinder($order,$item_array['amount']);
                $order_transaction['payment_method_id'] = 6;
                $order_transaction['parent_id'] = $order['id'];
                $order_transaction['parent_type'] = 'order';
                $order_transaction['mode'] = "in";
                $order_transaction['isPrimary'] = false;
                $order_transaction['status'] = "success";
            }
        }
        return $order_transaction;
    }

    public function walletTransactionFinder($order,$amount){
        $wallet = Wallet::where('remarks','like ','%'.$order['id'].'%')
            ->where('customer_id',$order['customer'])
            ->where('amount',$amount)
            ->where('type','Debit')
            ->first();

        if($wallet){
            return $wallet->id;
        }
        else null;
    }

    public function prepareOrderDiscounts($items,$order,$tax_percentage){

        $discounts =[];
        foreach($items as $item){
            $discount = [];
            if($item->order_item_name == "Discount"){
                foreach($item->meta as $meta){
                    switch($meta->meta_key){
                        case '_line_total' : $discount['discount_value']= abs($meta->meta_value);break;
                    }
                }
                $discount['order'] = $order['id'];
                $discount['type'] = "Fixed Whole Cart";
                $discount['amount'] = $discount['discount_value'];
                $discount['taxable_discount_amount'] = $discount['amount']/(($tax_percentage/100)+1);
                $discount['parent_id'] = null;
                $discount['parent_type'] = 'other';
                array_push($discounts,$discount);
            }
        }
        return $discounts;
    }

    public function updateOrderItemDiscounts($products,$discounts){
        $discount_amount = array_sum(array_column($discounts,'amount'));
        $divident = 0;
        foreach($products as $product){
            $divident += $product['quantity'];
        }

        foreach($products as $index=>$product){
            $products[$index]['discount'] =  ($discount_amount/ $divident)*$product['quantity'];
            $products[$index]['taxable_discount'] =  $products[$index]['discount']/(($product['tax_percentage']/100)+1);
        }
        return $products;
    }
}
