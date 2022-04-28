<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    //

    public function migrateWallet(){
        $res = DB::select("SELECT
                wp_woo_wallet_transactions.transaction_id,
                wp_woo_wallet_transactions.blog_id,
                wp_woo_wallet_transactions.user_id,
                wp_woo_wallet_transactions.type,
                wp_woo_wallet_transactions.amount,
                wp_woo_wallet_transactions.balance,
                wp_woo_wallet_transactions.currency,
                wp_woo_wallet_transactions.details,
                wp_woo_wallet_transactions.deleted,
                wp_woo_wallet_transactions.date
            FROM wp_woo_wallet_transactions");

        $id=0;

        DB::beginTransaction();
        try{

            foreach($res as $result){
                $id=$result->transaction_id;

                $wallet = DB::connection('platoshop_mysql')->table('wallets')->insert(
                    array(
                        'id'     =>   $result->transaction_id,
                        'transaction_date' =>  $result->date,
                        'customer_id' =>  $result->user_id,
                        'type' =>  ucfirst($result->type),
                        'amount' => $result->amount,
                        'balance' =>  $result->balance,
                        'channel_id' => 1,
                        'remarks' => $result->details,
                        'created_at' => $result->date,
                        'token' => md5($result->transaction_id),
                        'payment_status' => true,
                        'isDraft' => true,
                        'created_by' => 1,
                    )
               );
            }
            DB::commit();
            return redirect()->back()->with('success','Migration completed successfully.');
        }catch(Exception $ex){
            DB::rollBack();
            return  $id.$ex;
        }

    }
}
