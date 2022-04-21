<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MigrateController extends Controller
{
    //

    public function user(){
        //Migration code here...
        session()->flash('success','user migration completed');
        return redirect()->back();
    }

    public function migrate(){
        //Migration code here...
        session()->flash('success','migration completed');
        return redirect()->back();
    }
}
