<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Post;
use App\Models\User;
use App\Services\DataFetcher;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class UserController extends Controller
{
    //

    public function migrateUser(){
        $res = DB::select("SELECT
        wp_users.ID,
        wp_users.user_login,
        wp_users.user_pass,
        wp_users.user_nicename,
        wp_users.user_email,
        wp_users.user_url,
        wp_users.user_registered,
        wp_users.user_activation_key,
        wp_users.user_status,
        wp_users.display_name,
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_first_name' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_first_name',
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_last_name' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_last_name',
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_email' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_email',
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_phone' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_phone',
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_address_1' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_address_1',
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_address_2' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_address_2',
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_city' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_city',
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_postcode' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_postcode',
        max( CASE WHEN ( wp_usermeta.meta_key = 'billing_state' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'billing_state',
        max( CASE WHEN ( wp_usermeta.meta_key = 'user_registration_date_of_birth' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'user_registration_date_of_birth',
        max( CASE WHEN ( wp_usermeta.meta_key = 'user_registration_gender' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'user_registration_gender',
        max( CASE WHEN ( wp_usermeta.meta_key = 'user_registration_mobile_number' ) THEN wp_usermeta.meta_value ELSE NULL END ) AS 'user_registration_mobile_number'
        FROM
        wp_users
        INNER JOIN wp_usermeta ON wp_usermeta.user_id = wp_users.ID
        WHERE wp_users.ID > 1
        GROUP BY
            wp_users.ID LIMIT ".request('offset').", ".request('limit'));
        $id=0;

        Config::set('database.connections.mysql', Config::get('database.connections.platoshop_mysql'));
        DB::purge('mysql');
        DB::reconnect('mysql');

        DB::beginTransaction();

        try{

            foreach($res as $result){
                $id = $result->ID;
                $user = DB::table('users')->insert(
                    array(
                        'id'     =>   $result->ID,
                        'first_name'   =>   $result->billing_first_name,
                        'last_name'   =>   $result->billing_last_name,
                        'email' => $result->user_email,
                        'password' => $result->user_pass,
                        'phone' => (new DataFetcher())->trimMobile($result->user_registration_mobile_number ? $result->billing_phone:$result->user_registration_mobile_number),
                        'gender' => $result->user_registration_gender,
                        'dob' => date('Y-m-d',strtotime($result->user_registration_date_of_birth)),
                        'created_at' => $result->user_registered
                    )
               );

               $role = DB::table('role_users')->insert([
                   'user_id' => $result->ID,
                   'role_id' => 2,
                   'created_at' => $result->user_registered
               ]);

               if($result->billing_address_1 && $result->billing_postcode){
                    $address = DB::table('addresses')->insert([
                        'customer_id' => $result->ID,
                        'first_name'   =>   $result->billing_first_name,
                        'last_name'   =>   $result->billing_last_name,
                        'address_1' => $result->billing_address_1,
                        'address_2' => $result->billing_address_2,
                        'city' => $result->billing_city,
                        'state_id' => (new DataFetcher())->getStateByCode($result->billing_state)->id,
                        'zip' => $result->billing_postcode,
                        'country_id' => 1,
                        'delivery_type' => 'Home',
                        'phone' => (new DataFetcher())->trimMobile($result->user_registration_mobile_number ? $result->billing_phone:$result->user_registration_mobile_number),
                        'created_at' => $result->user_registered
                    ]);
               }

               //Activation
               $activation = DB::table('activations')->insert([
                   'user_id' => $result->ID,
                   'code' => md5($result->ID),
                   'completed'=>1,
                   'updated_at' => $result->user_registered,
                   'completed_at' => $result->user_registered,
                   'created_at' => $result->user_registered
               ]);
            }
            DB::commit();
            return redirect()->back()->with('success','Migration completed successfully.');
        }catch(Exception $ex){
            DB::rollBack();
            return  $id.$ex;
        }
    }
    public function migrateUserPhone(){

        $id = 0;

        $orders = Post::where('post_type','shop_order')
            ->whereDate('post_date','>=',date('Y-m-d',strtotime(request()->start_date)))
            ->whereDate('post_date','<=',date('Y-m-d',strtotime(request()->end_date)))
            ->orderBy('post_date')
            ->with('meta')
            ->whereHas('items',function($q){
                $q->where('order_item_name','!=','Wallet Topup');
            })->get();

            DB::beginTransaction();
            try{
                foreach($orders as $order){
                    $id=$order->ID;
                    $array=[];
                    foreach($order->meta as $meta){
                        switch($meta->meta_key){
                            case "_billing_email" : $array['email'] = $meta->meta_value;break;
                            case "_billing_phone" : $array['phone'] = $meta->meta_value;break;
                            case "_customer_user":$array['customer_id']=$meta->meta_value;break;
                        }
                    }
                    if(isset($array['customer_id']) && isset($array['phone'])){
                        $user = User::find($array['customer_id']);

                        if($user && ($user->phone == null || $user->phone == "") ){
                            $user->phone = $array['phone'];
                            $user->save();
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
