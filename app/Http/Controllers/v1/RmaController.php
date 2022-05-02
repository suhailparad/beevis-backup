<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Post;
use App\Models\RmaRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class RmaController extends Controller
{

    public function migrate(){  
        Config::set('database.connections.mysql', Config::get('database.connections.platoshop_mysql'));
        DB::purge('mysql');
        DB::reconnect('mysql');
        
        $id=0;
        DB::beginTransaction();
        try{
            $orders = Order::whereDate('date','>=',date('Y-m-d',strtotime(request()->start_date)))
                    ->whereDate('date','<=',date('Y-m-d',strtotime(request()->end_date)))
                    ->get();
            foreach($orders as $order){
                $rma_requests = $order->rma->where('total_amount','>',0)->get();
                foreach($rma_requests as $rma){
                    if($rma->child_order_id){

                        $exchange_order = Order::find($rma->child_order_id);

                        if($exchange_order){
                            foreach($exchange_order->transactions as $transaction){
                                if($transaction->status=="success"){
                                    $rma->transactions()->create([
                                        'parent_id' => $rma->id,
                                        'parent_type' => 'rma',
                                        'transaction_date'=>$transaction->transaction_date,
                                        'transaction_no' => $transaction->transaction_no,
                                        'payment_method_id'=>$transaction->payment_method_id,
                                        'amount' => $transaction->amount,
                                        'mode' => 'in',
                                        'status' => 'success',
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            DB::commit();
            return redirect()->back()->with('success','Migration completed successfully.');
        }catch(Exception $ex){
            DB::rollBack();
            return($id.$ex);
        }
    }
}