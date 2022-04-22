<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    //

    public function linking(){
        $id=0;
        DB::beginTransaction();
        try{
            $wp_products = DB::select("SELECT
                wp_posts.ID,
                wp_posts.post_title as variation
                FROM
                wp_posts
                WHERE wp_posts.post_type = 'product_variation'
                and wp_posts.post_status = 'publish'");

            foreach($wp_products as $wp){
                $exploded = explode("-",str_replace(" - ","-",$wp->variation));
                if(sizeof($exploded)){
                    $variation_exploded = explode(",",str_replace(", ",",",$exploded[1]));
                    $size = is_numeric($variation_exploded[0])?$variation_exploded[0]:$variation_exploded[1];
                    $color =  is_numeric($variation_exploded[0])?$variation_exploded[1]:$variation_exploded[0];
                    Product::where('name',$exploded[0]." ".$color." ".$size)->update([
                        'variation_id' => $wp->ID
                    ]);
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
