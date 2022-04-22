<?php

namespace App\Http\Controllers\v1;

use App\Facades\DataFetcher;
use App\Http\Controllers\Controller;
use App\Models\Post;
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

            $orders=$this->prepareOrderData();

            DB::commit();
            return redirect()->back()->with('success','Migration completed successfully.');
        }catch(Exception $ex){
            DB::rollBack();
            return  $id.$ex;
        }
    }

    private function prepareOrderData(){

        $orders = Post::where('post_type','shop_order')
            ->whereDate('post_date','>=',request()->start_date)
            ->whereDate('post_date','<=',request()->end_date)
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

            // $fee = $order->items->where('order_item_type','fee');

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

            $array['products'] = $order_products;
            $array['addons'] = $addons;
            $array['transactions'] = $order_transaction;
            $array['billing_address']  = $order_billing_array;
            $array['shipping_address']  = $order_shipping_array;

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
                    case '_tax_class' : $item_array['tax_percentage']=$this->getTaxPercentage($meta->meta_value);break;
                    case '_line_total' : $item_array['taxable_amount']=$meta->meta_value;break;
                    case '_qty' : $item_array['quantity']=$meta->meta_value;break;
                    case '_line_tax_data' : $item_array['tax_data'] = $meta->meta_value;break;
                }
            }
            $item_array['price'] = ($item_array['tax_amount'] + $item_array['taxable_amount'])/2;
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
            $item_array['price'] = ($item_array['tax_amount'] + $item_array['taxable_amount'])/2;
            $item_array['total'] = $item_array['price'] * $item_array['quantity'];
            $item_array['type']  = 'add_on_item';
            $item_array['created_at'] = $order['created_at'];
            $item_array['updated_at'] = $order['updated_at'];
            array_push($items, $item_array);
        }
        return $items;
    }
}
