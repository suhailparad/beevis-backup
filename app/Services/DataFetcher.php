<?php

namespace App\Services;

use App\Models\Product;
use App\Models\State;
use Carbon\Carbon;

class DataFetcher{

    public function getStateByCode($code){
        if($code == 'TS'){
            $code = 'TG';
        }
        if($code == 'UK'){
            $code = 'UT';
        }
        return State::where('code',$code)->first();
    }

    public function trimMobile($number){
        $number= str_replace('+91','',$number);
        $number= str_replace(' ','',$number);
       return  $number;
    }

    public function getPaymentMethod($method){
        return match($method){
            'cod'=>1,
            'instamojo'=>2,
            'hdfcccavenue' => 3,
            'razorpay' => 4,
            'payg' => 5,
            'wallet' => 6,
            'wallet_gateway' => 6
        };

    }

    public function getOrderStatus($wp_status){
        return match($wp_status){
            'wc-return-requested' => 'Completed',
            'wc-return-approved' => 'Completed',
            'wc-refunded' => 'Completed',
            'wc-return-cancelled' => 'Completed',
            'wc-dokan-refunded' => 'Completed',
            'wc-partial-cancel' => 'Completed',
            'wc-exchange-request' => 'Completed',
            'wc-exchange-cancel' => 'Completed',
            'wc-exchange-req' => 'Completed',
            'wc-exchange-approve' => 'Completed',
            'wc-exchange-complt' => 'Completed',
            'wc-completed'=> 'Completed',
            'wc-cancelled'=> 'Cancelled',
            'wc-failed' => 'Unpaid',
            'wc-on-hold' => 'Hold',
            'wc-pending' => 'Unpaid',
            'wc-processing' => 'Processing'
        };
    }

    public function getProduct($variation){
        $product = Product::where('variation_id',$variation)->first();
        if($product)
            return $product->id;
        else
            return null;
    }

    public function getTaxPercentage($tax_class,$state_id,$date){

        if(empty($tax_class)){

            $order_date = Carbon::createFromFormat('Y-m-d',$date);

            $kfc_end_date = Carbon::createFromFormat('Y-m-d','2021-08-01');

            $has_kfc = $order_date->lt($kfc_end_date);

            if($has_kfc && $state_id==18)
                return 19;
            return 18;
        }else if ($tax_class=="reduced_rate"){
            return 5;
        }
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
            //change in happenstance
            if($kfc_enabled){
                $tax_percentage = 12; // change to 18
            }else{
                $tax_percentage  =5;
            }
        }else{
            $tax_percentage = 12;
        }
        return $tax_percentage;
    }

}
