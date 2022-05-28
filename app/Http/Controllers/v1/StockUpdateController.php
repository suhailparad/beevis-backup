<?php

namespace App\Http\Controllers\v1;

use App\Facades\DataFetcher;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductChannel;
use App\Models\ProductQtyWarehouse;
use App\Models\TaxRate;
use App\Models\WpWocommerceTaxRate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Maatwebsite\Excel\Facades\Excel;
use App\Export\StockExport;

class StockUpdateController extends Controller{
    
    public function update(Request $request){

        $wp_stocks = DB::select("SELECT *from stock");

        $id=0;

        Config::set('database.connections.mysql', Config::get('database.connections.platoshop_mysql'));
        DB::purge('mysql');
        DB::reconnect('mysql');
        
        $data=[];
        try{
            $count = 1;
            foreach($wp_stocks as $wp){
                if($wp->stock>0){
                    $product = Product::where('variation_id',$wp->variation_id)->first();
                    if($product){
                        $count++;
                        array_push($data,[
                            'Barcode' => '',
                            'Sku' => '',
                            'Product Name'=>$product->name,
                            'Quantity' =>$wp->stock
                        ]);
                    }
                }
            }

            //
            $export = new StockExport($data, $count);
            return Excel::download($export, 'file.xlsx');
        }catch(Exception $ex){
            return  $id.$ex;
        }

    }
}