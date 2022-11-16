<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CarrierModel;
use Lang;
use DB;
class Carrier extends Controller
{
    //

    public function saveNewCarrier(Request $req){
        $carrier=new CarrierModel;
        $carrier->carrier_name=$req->carrierName;
        $carrier->carrier_address=$req->carrierAddress;
        $carrier->carrier_phn_no=$req->carrierPhnNo;
        $carrier->carrier_alt_phn_no=$req->carrierAltPhnNo;
        $carrier->carrier_bhatta=$req->carrierBhatta;
        try{
            $data=DB::select('select * from carrier where carrier_phn_no ='.$req->carrierPhnNo);
            if($data != null){
                return redirect('home/carrier')->with('message',Lang::get('already_present'));
            }
            else{
                $carrier->save();
                return redirect('home/carrier')->with('message',Lang::get('saved'));
            }
            
        }
        catch(Exception $e){
            return redirect('home/carrier')->with('message',Lang::get('error'));
            // print_r($e);
        }
    }

        public function getAllCarriers(Request $req){
            $uname=$req->input('carrier_name');
            $phn=$req->input('phn');
        // print_r($uname);
        if($uname==null && $phn==null){
            try{
                $carrier = DB::select('select * from carrier where is_deleted= 0');
                return view('carrierPanel',['carrier'=>$carrier]);
            }
            catch(Exception $e){
                print_r($e);
            }
        }
        else{
            try{
                $merchant = carrier::select('*')
                ->where('carrier_name', 'LIKE', '%'.$uname.'%')
                ->where('carrier_phn_no', 'LIKE','%'.$phn.'%' )
                // ->where('created_on', '>=','%'.$fromdate.'%' )
                // ->where('created_on', '<=','%'.$todate.'%' )
                ->where('is_deleted', '=',0)
                ->get();
                return view('carrierPanel',['carrier'=>$carrier]);
            }
            catch(Exception $e){
                print_r($e);
            }
        }
    }
    public function setSelectedCarrier($carrierid){
        $carrier = DB::select('select * from carrier where carrier_id='.$carrierid);
            return view('editCarrier',['carrier'=>$carrier]);
        // }
       
    }

    public function editCarrier(Request $req,$carrierid){
        $name=$req->input('carrierName');
        $address=$req->input('carrierAddr');
        $phn=$req->input('carrierPhnNo');
        $altphn=$req->input('carrierAltPhnNo');
        $bhatta=$req->input('carrierBhatta');
        try{
            DB::update('update carrier set carrier_name = ? ,carrier_address = ? ,carrier_phn_no = ? ,carrier_alt_phn_no = ?  ,carrier_bhatta = ? where carrier_id = ?',[$name,$address,$phn,$altphn,$bhatta,$carrierid]);
            return redirect('home/carrier')->with('message',Lang::get('updated'));
        }
        catch(Exception $e){
            return redirect('home/carrier')->with('message',Lang::get('error'));
            // print_r($e);
        }

    }

    public function deleteCarrier($carrierid){
        try{
        DB::update('update carrier set is_deleted=1 where carrier_id='.$carrierid);
        return redirect('home/carrier')->with('message',Lang::get('deleted'));
        }
        catch(Exception $e){
            return redirect('home/carrier')->with('message',Lang::get('error'));

            }
    }

        public function getAllCarrierTrips(Request $req,$carrierid){
            $carrier = DB::select('select * from carrier where carrier_id='.$carrierid);
            $product=DB::select('select * from product');
            if($req->isMethod('POST')){
                $fromdate=$req->input('fromdate');
                $todate=$req->input('todate');  
                Session::put('fromdateCar',$fromdate);
                Session::put('todateCar',$todate);
                $trip=DB::table('merchant_trip')
                        ->leftjoin('carrier_salary','merchant_trip.trip_id','=','carrier_salary.trip_id')
                        ->where('carrier_salary.carrier_id','=',$carrierid)
                        ->where('is_deleted','=',0)
                        ->select(DB::raw('sum(carrier_salary.hamali) as hamali'),'carrier_salary.carrier_id','merchant_trip.trip_id','merchant_trip.trip_date',
                        'merchant_trip.vehicle_number','merchant_trip.product_quantity','merchant_trip.product_id')
                        ->whereBetween('merchant_trip.trip_date',[$fromdate,$todate])
                        ->groupby('merchant_trip.trip_id')
                        ->orderby('merchant_trip.trip_id')
                        ->get(); 
                $carrierBhatta=DB::table('merchant_trip')
                    ->leftjoin('carrier_bhatta','merchant_trip.trip_id','=','carrier_bhatta.trip_id')
                    ->where('carrier_bhatta.carrier_id','=',$carrierid)
                    ->where('is_deleted','=',0)
                    ->select('carrier_bhatta.bhatta','carrier_bhatta.carrier_id','carrier_bhatta.trip_id')
                    ->whereBetween('merchant_trip.trip_date',[$fromdate,$todate])
                    ->groupby('merchant_trip.trip_id')
                    ->orderby('merchant_trip.trip_id')
                    ->get();

                $sumOfChira=DB::table('merchant_trip')
                        ->join('carrier_salary','merchant_trip.trip_id','=','carrier_salary.trip_id')
                        ->where('carrier_salary.carrier_id','=',$carrierid)
                        ->where('is_deleted','=',0)
                        ->whereBetween('merchant_trip.trip_date',[$fromdate,$todate])
                        ->where('product_id','=',1)
                        ->selectRaw('sum(product_quantity/2) as totalProduct,merchant_trip.trip_id as trip_id')
                        ->get();
                    // print_r(json_encode($sumOfChira));
                $sumOfValu=DB::table('merchant_trip')
                        ->join('carrier_salary','merchant_trip.trip_id','=','carrier_salary.trip_id')
                        ->where('carrier_salary.carrier_id','=',$carrierid)
                        ->whereBetween('merchant_trip.trip_date',[$fromdate,$todate])
                        ->where('is_deleted','=',0)
                        ->where('product_id','=',2)
                        ->selectRaw('sum(product_quantity) as totalProduct')
                        ->get();
                        $transaction=DB::table('carrier_expense')
                                    ->where('carrier_id','=',$carrierid)
                                    ->where('is_deleted','=',0)
                                    ->whereBetween('expense_date',[$fromdate,$todate])
                                    ->get();
           
                
                // print_r($totalCarrierPaidBill);
                
        
                    return view('carrierTrips',['carrier'=>$carrier,'trip'=>$trip,'product'=>$product,
                    'transaction'=>$transaction,'sumOfChira'=>$sumOfChira,'sumOfValu'=>$sumOfValu,
                    'carrierBhatta'=>$carrierBhatta]);
            }
            else{
                $trip = DB::table('merchant_trip')
                ->leftjoin('carrier_salary','merchant_trip.trip_id','=','carrier_salary.trip_id')
                ->where('carrier_salary.carrier_id','=',$carrierid)
                ->where('merchant_trip.is_deleted','=',0)
                ->select(DB::raw('sum(carrier_salary.hamali) as hamali'),'carrier_salary.carrier_id','merchant_trip.trip_id','merchant_trip.trip_date',
                'merchant_trip.vehicle_number','merchant_trip.product_quantity','merchant_trip.product_id')
                ->groupby('merchant_trip.trip_id')
                ->get();

                $carrierBhatta=DB::table('merchant_trip')
                    ->leftjoin('carrier_bhatta','merchant_trip.trip_id','=','carrier_bhatta.trip_id')
                    ->select('carrier_bhatta.bhatta','carrier_bhatta.carrier_id','merchant_trip.trip_id')
                    ->where('merchant_trip.is_deleted','=',0)
                    ->groupby('merchant_trip.trip_id')
                    ->orderby('merchant_trip.trip_id')
                    ->get();

                    
                $sumOfChira=DB::table('merchant_trip')
                ->join('carrier_salary','merchant_trip.trip_id','=','carrier_salary.trip_id')
                ->where('carrier_salary.carrier_id','=',$carrierid)
                ->where('merchant_trip.is_deleted','=',0)
                ->where('product_id','=',1)
                ->selectRaw('sum(product_quantity/2) as totalProduct')
                ->get();
                $sumOfValu=DB::table('merchant_trip')
                ->join('carrier_salary','merchant_trip.trip_id','=','carrier_salary.trip_id')
                ->where('carrier_salary.carrier_id','=',$carrierid)
                ->where('merchant_trip.is_deleted','=',0)
                ->where('product_id','=',2)
                ->selectRaw('sum(product_quantity) as totalProduct')
                ->get();

                
                $transaction=DB::table('carrier_expense')
                            ->where('carrier_id','=',$carrierid)
                            ->where('is_deleted','=',0)
                            ->get();
              
                
                // print_r(json_encode($sumOfChira));
                return view('carrierTrips',['carrier'=>$carrier,'trip'=>$trip,'product'=>$product,
                'transaction'=>$transaction,'sumOfChira'=>$sumOfChira,'sumOfValu'=>$sumOfValu
                ,'carrierBhatta'=>$carrierBhatta]);
            }
        }
    


}
