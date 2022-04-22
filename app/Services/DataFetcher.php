<?php

namespace App\Services;

use App\Models\State;

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
            //hdfcccavenue => 1,
            //razorpay => 1,
            //payg => 1,
            //wallet => 1,
            //wallet_gateway => 1
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

}
