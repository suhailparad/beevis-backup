<?php

namespace App\Http\Controllers\v1;

use App\Facades\DataFetcher;
use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use App\Models\WpWocommerceTaxRate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TaxController extends Controller
{
    //

    public function linking(){

        $id=0;

        $wp_tax_rates = WpWocommerceTaxRate::get();

        Config::set('database.connections.mysql', Config::get('database.connections.platoshop_mysql'));
        DB::purge('mysql');
        DB::reconnect('mysql');

        DB::beginTransaction();
        try{

            foreach($wp_tax_rates as $wp_rates){
                $state_id = DataFetcher::getStateByCode($wp_rates->tax_rate_state)->id;

                $tax_class = null;
                if($wp_rates->tax_rate_class=="")
                    $tax_class=1;
                else if($wp_rates->tax_rate_class=="reduced-rate"){
                    $tax_class=2;
                }

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

            DB::commit();
            return redirect()->back()->with('success','Migration completed successfully.');
        }catch(Exception $ex){
            DB::rollBack();
            return  $id.$ex;
        }
    }
}
