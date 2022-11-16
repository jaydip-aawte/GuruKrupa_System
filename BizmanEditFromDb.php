<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use View;
use Lang;
use DB;

class BizmanEditFromDb extends Controller
{
    //
    public function editBizmanData(Request $req,$id){
        $name=$req->input('bizmanName');
        $address=$req->input('bizmanAddr');
        $phn=$req->input('bizmanPhnNo');
        $altphn=$req->input('bizmanAltPhnNo');
        $mail=$req->input('bizmanMail');
        try{
            $query=DB::update('update merchant set merchant_name = ? ,merchant_address = ? ,merchant_phn_no = ? ,merchant_alt_phn_no = ?  ,merchant_mail = ? where merchant_id = ?',[$name,$address,$phn,$altphn,$mail,$id]);
            if($query){
                return redirect('home/merchant')->with('message',Lang::get('updated'));
            }
            else{
                return redirect('home/merchant')->with('message',Lang::get('nothingToUpdate'));        
            }
        }
        catch(Exception $e){
            return redirect('home/merchant')->with('message',Lang::get('error'));
            // print_r($e);
        }
    }

    public function editBizmanTrip(Request $req,$bizmanid,$tripid){
        $vehicle_number=$req->get('vehicle_number');
        $product=$req->get('productname');
        $productrate=$req->input('productrate');
        $productunit=$req->get('productunit');
        $productquantity=$req->input('productquantity');
        
        try{
            DB::update('update bizman_trip_info set vehicle_number = ? ,product_name = ? ,product_rate = ? ,product_unit = ?  ,product_quantity = ? where trip_id = ?',[$vehicle_number,$product,$productrate,$productunit,$productquantity,$tripid]);
            return redirect('home/merchant/transaction/'.$bizmanid)->with('message',Lang::get('updated'));;
        }
        catch(Exception $e){
            return redirect('home/merchant/transaction/'.$bizmanid)->with('message',Lang::get('error'));
            // print_r($e);
        }
        

    }

}
