<?php

namespace App\Services;

use App\Models\Product;
use App\Models\State;
use App\Models\User;
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
            'wallet_gateway' => 6,
            'other' => 3
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
            'wc-exchange-approve' => 'Created',
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
            $order_date = date('Y-m-d', strtotime($date));
            $order_date = Carbon::createFromFormat('Y-m-d',$order_date);
            
            
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

    public function getRmaReasonType($type){
        return match($type){
            'Damaged Product' => 'Damaged Product',
            'Size Issue' => 'Size Issue',
            'Different Color' => 'Colour Issue',
            default => $type
        };
    }

    public function getWooAdminUser($id){
        $admin_id = null;
        $wp_admin=[
            1   =>  'customercare@happenstance.com',
            2	=>	'rijumosons@gmail.com',
            3	=>	'ratheesh@happenstance.com',
            4	=>	'zerlim@mosonsgroup.in.com',
            5	=>	'nived@happenstance.com',
            6	=> 	'hafeel@happenstance.com',
            7	=>	'yadu@happenstance.com',
            8	=>	'sabina@happenstance.com',
            9	=>	'sneha@happenstance.com',
            10	=> 	'daniel@happenstance.com',
            11	=>	'sruthi@happenstance.com',
            12	=>	'amaljith@happenstance.com',
            13	=>	'anshif@happenstance.com',
            14	=>	'naseefmohammed9@gmail.com',
            15	=>	'adarsh@happenstance.com',
            16	=>	'amal@happenstance.com',
            17	=>	'aswin@happenstance.com',
            18	=>	'arshak@gmail.com',
            19	=>	'Anjay@myemail.com'
        ];

        if(isset($wp_admin[$id])){
            $user = User::where('email',$wp_admin[$id])->first();
            if($user){
                $admin_id = $user->id;
            }
        }
        return $admin_id;
    }

}
