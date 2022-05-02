<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductChannel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class ProductController extends Controller
{
    //

    public function linking(){

        $wp_products = DB::select("SELECT
                wp_posts.ID,
                wp_posts.post_title as variation
                FROM
                wp_posts
         
                WHERE wp_posts.post_type = 'product_variation'
                and wp_posts.post_status = 'publish'"
                );

        $id=0;

        Config::set('database.connections.mysql', Config::get('database.connections.platoshop_mysql'));
        DB::purge('mysql');
        DB::reconnect('mysql');

        DB::beginTransaction();
        try{
            foreach($wp_products as $wp){
                $exploded = explode("-",str_replace(" - ","-",$wp->variation));
                if(sizeof($exploded) > 0){
                   
                    $variation_exploded = explode(",",str_replace(", ",",",$exploded[1]));
                   
                    if(count($variation_exploded) > 1){
                        $size = is_numeric($variation_exploded[0])?$variation_exploded[0]:$variation_exploded[1];
                        $color =  is_numeric($variation_exploded[0])?$variation_exploded[1]:$variation_exploded[0];
                    
                        Product::where('name',$exploded[0]." ".$color." ".$size)->update([
                            'variation_id' => $wp->ID
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

    public function createProductChannel(){

        Config::set('database.connections.mysql', Config::get('database.connections.platoshop_mysql'));
        DB::purge('mysql');
        DB::reconnect('mysql');
        
        $products = Product::all();
        
        DB::beginTransaction();
        try{

            $product_channel = ProductChannel::create([
                'product_id' => 1,
                'channel_id' => 1,
                'origin_country_id' => 1,
                'tax_class_id' => 1,
                'price' => 1499,
                'selling_price' => 1499,
                'is_active' => 1,
                'visibility' => 'not_visible_individually',
                'store_front_stock' => 0
            ]);

            foreach($products as $product){
                $price = 0;

                \Log::info("Getting parent price : parent_id".$product->id);
                if($product->parent_id){
                    $parent = Product::find($product->parent_id);
                    if($parent->channel){
                        $price = $parent->channel->price;
                    }
                }

                \Log::info($product->channel);
                if(!$product->channel){
                    $product_channel = ProductChannel::create([
                        'product_id' => $product->id,
                        'channel_id' => 1,
                        'origin_country_id' => 1,
                        'tax_class_id' => 1,
                        'price' => $price,
                        'is_active' => 1,
                        'visibility' => 'visible',
                        'selling_price' => $price,
                        'store_front_stock' => 0
                    ]);
                }
                
            }
            DB::commit();
            return redirect()->back()->with('success','Product channel created successfully.');
        }catch(\Exception $ex){
            DB::rollBack();
            return $ex;
        }
        
    }
}
