<?php

namespace App\Services;

use App\Models\State;

class DataFetcher{

    public function getStateByCode($code){
        if($code == 'TR'){
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

}
