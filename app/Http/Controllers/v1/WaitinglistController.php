<?php

namespace App\Http\Controllers\v1;

use App\Facades\DataFetcher;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Waitinglist;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaitinglistController extends Controller
{
    //

    public function migrate(){

        $id=0;
        DB::beginTransaction();
        try{

            $wp_products = DB::select("SELECT
                wp_posts.ID,
                wp_posts.post_title as variation
                max( CASE WHEN ( wp_postmeta.meta_key = '_yith_wcwtl_users_list' ) THEN wp_postmeta.meta_value ELSE NULL END ) AS 'users',
                FROM
                wp_posts
                INNER JOIN wp_postmeta ON wp_postmeta.post_id = wp_posts.ID
                WHERE wp_posts.post_type = 'product_variation'
                and wp_posts.post_status = 'publish' GROUP BY
	            wp_posts.ID");

                foreach($wp_products as $product){
                    $id = $product->ID;
                    $unserialzed  = unserialize($product->users);
                    if(count($unserialzed)>0){
                        foreach($unserialzed as $email){
                            $list =['email'=>$email,'is_guest'=>true,'user_id'=>null,'product_id' => DataFetcher::getProduct($product->ID)];
                            $user = User::where('email',$email)->first();
                            if($user){
                                $list['is_guest']=false;
                                $list['user_id']=$user->id;
                            }
                            Waitinglist::create($list);
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
