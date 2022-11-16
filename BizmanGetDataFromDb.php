<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BizmanTransaction;
use App\BizmanTrip;
use App\bizman;
use Session;
use DB;
class BizmanGetDataFromDb extends Controller
{
    //
    public function getBizmanData(Request $req){
        $uname=$req->input('searchkeyname');
        $filter=$req->input('filter');
        // print_r($filter);
        $trip= DB::table('merchant')
        ->join('trip','trip.merchant_id','=','merchant.merchant_id')
        ->select('trip.merchant_id', DB::raw('count(*) as total'))
        ->groupby('trip.merchant_id','merchant.merchant_id')
        ->get();

        $totalAmtAllMerchant=DB::table('merchant_trip')
            ->select('merchant_trip.merchant_id as bizid')
            ->selectRaw('sum(merchant_total_amount) as merchant_total_amount')
            ->where('merchant_trip.is_deleted','=',0)
            ->groupby('merchant_trip.merchant_id')
            ->orderby('merchant_trip.merchant_id','asc')
            ->get();

        $paidAmtAllMerchant=DB::table('merchant_transaction')
            ->select('merchant_transaction.merchant_id as bizid')
            ->where('merchant_transaction.is_deleted','=',0)
            ->selectRaw('sum(transaction_amount) as paid_amount')
            ->groupby('merchant_transaction.merchant_id')
            ->orderby('merchant_transaction.merchant_id','asc')
            ->get();

        $result=[];
        if(count($totalAmtAllMerchant) > count($paidAmtAllMerchant)){
            for($i=0;$i< count($totalAmtAllMerchant);$i++){
                $flag=false;
                for($j=0;$j< count($paidAmtAllMerchant);$j++){
                    if($totalAmtAllMerchant[$i]->bizid==$paidAmtAllMerchant[$j]->bizid){
                        $flag=true;
                        $result[$totalAmtAllMerchant[$i]->bizid]= $totalAmtAllMerchant[$i]->merchant_total_amount-$paidAmtAllMerchant[$j]->paid_amount;
                    }
                }
                if($flag==false){
                    $result[$totalAmtAllMerchant[$i]->bizid]=$totalAmtAllMerchant[$i]->merchant_total_amount;
                }
            }

        }
        else if(count($totalAmtAllMerchant) < count($paidAmtAllMerchant)){
            for($i=0;$i< count($paidAmtAllMerchant);$i++){
                $flag=false;
                for($j=0;$j< count($totalAmtAllMerchant);$j++){
                    if($totalAmtAllMerchant[$j]->bizid==$paidAmtAllMerchant[$i]->bizid){
                        $flag=true;
                        $result[$paidAmtAllMerchant[$i]->bizid]= $totalAmtAllMerchant[$j]->merchant_total_amount-$paidAmtAllMerchant[$i]->paid_amount;
                    }

                }
                if($flag==false){
                    $result[$paidAmtAllMerchant[$i]->bizid]=-$paidAmtAllMerchant[$i]->paid_amount;
                }
            }
        }
        // print_r(count($totalAmtAllMerchant));
        // print_r(count($paidAmtAllMerchant));
        // print_r($result);

        if($req->isMethod('GET')){
            try{
                $merchant = DB::select('select * from merchant where is_deleted= 0');

                return view('merchantsPanel',['merchant'=>$merchant,'result'=>$result]);
            }
            catch(Exception $e){
                print_r($e);
            }
        }
        else if($req->isMethod('POST')){
            try{
                if($filter==2){
                    $merchant = bizman::select('*')
                    ->where('is_deleted', '=',0)
                    ->get();

                }
                else if($filter==3){
                    $merchant=DB::table('merchant')
                    ->select('*')
                    ->whereNotIn('merchant_id',function($query){
                        $query->select('merchant_id')->from('merchant_trip');
                    })
                    ->where('merchant_name', 'LIKE', '%'.$uname.'%')
                    ->get();
                    // print_r(json_encode($customer));
                }
                else if($filter==1){
                    $merchant = bizman::select('*')
                    ->where('is_deleted', '=',0)
                    ->whereIn('merchant_id',function($query){
                        $query->select('merchant_id')->from('merchant_trip');
                    })
                    ->where('status','=', 1)
                    ->where('merchant_name', 'LIKE', '%'.$uname.'%')
                    ->get();
                    }
                    else{
                        $merchant=bizman::select('*')
                                  ->where('is_deleted','=',0)
                                  ->where('status','=', 0)
                                  ->where('merchant_name', 'LIKE', '%'.$uname.'%')
                                  ->get();
                    }
                // print_r("Hii");
                return view('merchantsPanel',['merchant'=>$merchant,'result'=>$result]);
            }
            catch(Exception $e){
                print_r($e);
            }
        }

    }


    public function setBizmanData(Request $req,$id){

        $bizman = DB::select('select * from merchant where merchant_id='.$id);
        $trip = DB::select('select * from merchant_trip where merchant_id='.$id);

        $product=DB::select('select * from product');


        if($req->isMethod('POST')){
            $fromdate=$req->input('fromdate');
            $todate=$req->input('todate');

            Session::put('fromdateMerchant',$fromdate);
            Session::put('todateMerchant',$todate);

            $trip=DB::table('merchant_trip')
                  ->where('merchant_id','=',$id)
                  ->where('is_deleted','=',0)
                  ->whereBetween('trip_date',[$fromdate,$todate])
                  ->get();

            $transaction = DB::table('merchant_transaction')
                            ->where('merchant_id','=',$id)
                            ->where('is_deleted','=',0)
                            ->whereBetween('transaction_date',[$fromdate,$todate])
                            ->get();
            $paidAmtMerchant=DB::table('merchant_transaction')
                ->where('merchant_id','=',$id)
                ->whereBetween('transaction_date',[$fromdate,$todate])
                ->select('merchant_transaction.merchant_id as custid')
                ->selectRaw('sum(transaction_amount) as paid_amount')
                ->groupby('merchant_transaction.merchant_id')
                ->get();


            $sumOfProducts=DB::table('merchant_trip')
                ->join('product','product.product_id','=','merchant_trip.product_id')
                ->where('merchant_id','=',$id)
                ->whereBetween('trip_date',[$fromdate,$todate])
                ->where('is_deleted','=',0)
                ->selectRaw('sum(product_quantity) as totalProduct ,sum(merchant_total_amount) as totalBill,product_name as product_name,product_unit as unit')
                ->groupby('product.product_name')
                ->get();

            return view('merchantTransaction',['bizman'=>$bizman,'trip'=>$trip,'transaction'=> $transaction,
            'sumOfProducts'=>$sumOfProducts,'paidAmtMerchant'=>$paidAmtMerchant,'product'=>$product]);
        }
        else if($req->isMethod('GET') && $req->is('home/merchant/transaction/'.$id)){
            $transaction = DB::table('merchant_transaction')
                            ->where('merchant_id','=',$id)
                            ->where('is_deleted','=',0)
                            ->get();

            $sumOfProducts=DB::table('merchant_trip')
                ->join('product','product.product_id','=','merchant_trip.product_id')
                ->where('merchant_id','=',$id)
                ->selectRaw('sum(product_quantity) as totalProduct ,sum(merchant_total_amount) as totalBill,product_name as product_name,product_unit as unit')
                ->where('merchant_trip.is_deleted','=',0)
                ->groupby('product.product_name')
                ->get();
            $paidAmtMerchant=DB::table('merchant_transaction')
                ->where('merchant_id','=',$id)
                ->select('merchant_transaction.merchant_id as custid')
                ->selectRaw('sum(transaction_amount) as paid_amount')
                ->groupby('merchant_transaction.merchant_id')
                ->get();


            return view('merchantTransaction',['bizman'=>$bizman,'trip'=>$trip,'transaction'=> $transaction,
            'sumOfProducts'=>$sumOfProducts,'paidAmtMerchant'=>$paidAmtMerchant,'product'=>$product]);
        }
        else{
            return view('editBizman',['bizman'=>$bizman]);
        }



    }


    public function getBizmanDetail(Request $req,$id){
        $bizman = DB::select('select * from merchant');
        $product = DB::select('select * from product');
        $vehicle = DB::select('select * from vehicle');

            return view('addMerchantTransaction',['bizman'=>$bizman,'product'=>$product,'vehicle'=>$vehicle]);



    }
    public function getBizmanTripDetail($bizmanid,$tripid){
        $trip=DB::select('select * from bizman_trip_info where trip_id='.$tripid);
        $bizman=DB::select('select * from merchant where bizman_id='.$bizmanid);
        $vehicle=DB::select('select * from vehicle');
        $transaction=DB::select('select * from bizman_transaction where trip_id='.$tripid);
        $product=DB::select('select * from product');
        // print_r($trip);
        $payment= BizmanTransaction::groupBy('trip_id')
        ->selectRaw('sum(transaction_amount_paid) as sum, trip_id')
        ->pluck('sum','trip_id');
    //   print_r($payment);
        if (isset($transaction)){
                return view('merchantTripEdit')->with(['trip'=>$trip,'bizman'=>$bizman,'transaction'=>$transaction,
            'payment'=>$payment,'product'=>$product,'vehicle'=>$vehicle
            ]);
        }
        else{
            return redirect('home/merchant/transaction/'.$bizmanid)->with("success","No transactions happened");
        }
    }


}
