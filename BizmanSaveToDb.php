<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\BizmanTransaction;
use App\BizmanTrip;
use App\bizman;
use App\Debt;
use Lang;
use DB;
class BizmanSaveToDb extends Controller
{
    function saveBizmanData(Request $req){
        $bizman=new bizman;
        $bizman->merchant_name=$req->bizmanName;
        $bizman->merchant_address=$req->bizmanAddr;
        $bizman->merchant_alt_phn_no=$req->bizmanAltPhnNo;
        $bizman->merchant_mail=$req->bizmanMail;
        $phn=$req->input('bizmanPhnNo');
        $bizman->merchant_phn_no=$phn;
        try{
            $data=DB::select('select * from merchant where merchant_phn_no ='.$phn);
            if($data != null){
                return redirect('home/merchant')->with('message',Lang::get('already_present'));
            }
            else{
                $bizman->save();
                return redirect('home/merchant')->with('message',Lang::get('saved'));
            }

        }
        catch(Exception $e){
            print_r($e);
        }

    }


    public function saveMerchantTrip(Request $req,$id){
        $input=new BizmanTrip;
        $input->merchant_id=$id;
        $input->vehicle_number=$req->get('vehicle_number');   //get is used bcz input is coming from drpdown list
        $input->product_name=$req->get('product_name');
        $input->product_unit=$req->get('product_unit');
        $input->product_quantity=$req->product_quantity;
        $input->product_rate=$req->product_rate;
        $input->total_amount=$req->total_amount;
        $input->created_on=$req->paymentDate;

        try{
            $input->save();
            $trip_id=$input->trip_id;

            $transaction=new BizmanTransaction;
            $transaction->trip_id=$trip_id;
            $transaction->bizman_id=$id;
            $transaction->transaction_date=$req->paymentDate;
            $transaction->transaction_amount_paid=$req->paid_amount;


            $remaining_amount=$req->total_amount-$req->paid_amount;


            $transaction->remaining_amount=$remaining_amount;
            if($remaining_amount==0){
                $transaction->transaction_status==1;
            }
            $transaction->save();
            return redirect('home/merchant/transaction/'.$id)->with('message',Lang::get('saved'));
        }
        catch(Exception $e){
            print_r($e);
        }



    }











}
