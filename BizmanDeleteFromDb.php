<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Lang;
use DB;

class BizmanDeleteFromDb extends Controller
{
    //
    public function deleteBizman(Request $req,$id){
        try{
            DB::update('update merchant set is_deleted=1 where merchant_id='.$id);
            return redirect('home/merchant')->with('message',Lang::get('deleted'));
        }
        catch(Exception $e){
            return redirect('home/merchant')->with('message',Lang::get('error'));

        }
        //return redirect is mandatory bcoz if u r using return view merchantsPanel then
        // each time we'll have to send $merchant with view otherwise it will throw error

}
}
