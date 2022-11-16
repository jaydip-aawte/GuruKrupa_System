<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\vehicle;
use Lang;
use DB;

class VehicleController extends Controller
{
    //
    public function saveVehicle(Request $req){
        $vehicle=new Vehicle;
        $vehicle->vehicle_number=$req->input('vehicleNumber');
        try{
            $vehicle->save();
            return redirect('home/vehicle')->with('message',Lang::get('saved'));
        }
        catch(Exception $e){
            return redirect('home/vehicle')->with('message',Lang::get('error'));
            print_r($e);
        }

    }


    public function getVehicleData(Request $req){
        $vehicleNo=$req->get('vehicleFilter');
        // print_r($vehicleNo);
        if($vehicleNo==null){
            try{
                 $vehicle = DB::select('select * from vehicle where is_deleted= 0');
                 $dropvehicle = DB::select('select * from vehicle where is_deleted= 0');
                return view('vehiclepanel',['vehicle'=>$vehicle,'dropvehicle'=>$dropvehicle]);
            }
            catch(Exception $e){
                print_r($e);
            }
        }
        else{
            try{
                $vehicle = vehicle::select('*')
                ->where('vehicle_number', 'LIKE', '%'.$vehicleNo.'%')
                ->where('is_deleted', '=',0)
                ->get();
                $dropvehicle = DB::select('select * from vehicle where is_deleted= 0');

                return view('vehiclepanel',['vehicle'=>$vehicle,'dropvehicle'=>$dropvehicle]);
            }
            catch(Exception $e){
                print_r($e);

            }
        }


    }

    public function setVehicleData(Request $req,$id){
        $vehicle = DB::select('select * from vehicle where vehicle_id='.$id);
       return view('editVehicle',['vehicle'=>$vehicle]);
       // print_r($bizman);
   }
    public function editVehicleData(Request $req,$id){
        $vehicle_number=$req->input('vehicle_number');
        $vehicle_status=$req->input('vehicle_status');
        try{
            $query=DB::update('update vehicle set vehicle_number = ? ,vehicle_status = ? where vehicle_id = ?',[$vehicle_number,$vehicle_status,$id]);
            if($query){
            return redirect('home/vehicle')->with('message',Lang::get('updated'));
        }
        else{
            return redirect('home/vehicle')->with('message',Lang::get('nothingToUpdate'));
            }
        }
        catch(Exception $e){
            return redirect('home/vehicle')->with('message',Lang::get('error'));
            // print_r($e);
            }
}

        public function getVehicleTransaction(Request $req,$vehicleid){
            $vehicle = DB::select('select * from vehicle where vehicle_id='.$vehicleid);
            $month=$req->get('monthName');
            $vehicleNumber=$vehicle[0]->vehicle_number;
            if($req->isMethod('GET') || $month==0){
                try{
                     $trip=DB::table('merchant_trip')
                     ->join('customer_trip','merchant_trip.trip_id','customer_trip.trip_id')
                     ->where('merchant_trip.vehicle_number','=',$vehicleNumber)
                     ->select('customer_trip.trip_id','trip_date','product_quantity',
                     'loading_first_carrier','loading_second_carrier','unloading_first_carrier','unloading_second_carrier'
                     ,DB::raw('concat(delivery_district,",", delivery_taluka," ,", delivery_village) as delivery_address'),
                     DB::raw('sum(customer_total_amount) as totalBillAmt'),
                     DB::raw('merchant_total_amount as merchant_total_amount'))
                     ->groupby('customer_trip.trip_id')
                     ->orderby('merchant_trip.trip_id')
                     ->get();

                    //  $expense=DB::table('vehicle_expense')
                    //          ->where('vehicle_number','=',$vehicleNumber)
                    //          ->where('is_deleted', '=',0)
                    //          ->select(DB::raw('sum(diesel_expense) as diesel_expense'),
                    //          DB::raw('sum(maintainance_expense) as maintainance_expense'),
                    //          'expense_comment as expense_comment','expense_date','expense_id')
                    //          ->groupby('expense_id')
                    //          ->get();
                    $expense=DB::table('vehicle_expense')
                     ->where('vehicle_number','=',$vehicleNumber)
                     ->where('is_deleted', '=',0)
                     ->select('diesel_expense',
                     'maintainance_expense',
                     'expense_comment','expense_date','expense_id')
                     ->groupby('expense_id')
                     ->get();

                    // print_r($expense);
                    $dieselExpense=DB::table('vehicle_expense')
                                    ->where('vehicle_number','=',$vehicleNumber)
                                    ->selectRaw('sum(diesel_expense) as totalDiesel')
                                    ->get();
                    $maintainanceExpense=DB::table('vehicle_expense')
                                    ->where('vehicle_number','=',$vehicleNumber)
                                    ->selectRaw('sum(maintainance_expense) as totalMaintainance')
                                    ->get();

                    // print_r($maintainanceExpense);
                    $driverbhatta=DB::table('merchant_trip')
                                ->leftjoin('driver_bhatta','driver_bhatta.trip_id','=','merchant_trip.trip_id')
                                ->where('vehicle_number','=',$vehicleNumber)
                                ->select('merchant_trip.trip_id','driver_bhatta.bhatta')
                                ->groupby('merchant_trip.trip_id')
                                ->orderby('merchant_trip.trip_id')
                                ->get();
                    $carrierbhatta=DB::table('merchant_trip')
                                ->leftjoin('carrier_bhatta','carrier_bhatta.trip_id','=','merchant_trip.trip_id')
                                ->where('vehicle_number','=',$vehicleNumber)
                                ->select('merchant_trip.trip_id',DB::raw('sum(carrier_bhatta.bhatta) as bhatta'))
                                ->groupby('merchant_trip.trip_id')
                                ->orderby('merchant_trip.trip_id')
                                ->get();
                                // print_r($carrierbhatta);
                    $carrierhamali=DB::table('merchant_trip')
                                ->leftjoin('carrier_salary','carrier_salary.trip_id','=','merchant_trip.trip_id')
                                ->where('vehicle_number','=',$vehicleNumber)
                                ->selectRaw('sum(hamali) as hamali, merchant_trip.trip_id as trip_id')
                                ->groupby('merchant_trip.trip_id')
                                ->orderby('merchant_trip.trip_id')
                                ->get();
                    $totalVehicleIncome=0;
                    for($i=0; $i< count($trip); $i++){
                        if($trip[$i]->trip_id==$driverbhatta[$i]->trip_id){
                            $trip[$i]->totalBillAmt-=$driverbhatta[$i]->bhatta;
                        }
                        if($trip[$i]->trip_id==$carrierbhatta[$i]->trip_id){
                            $trip[$i]->totalBillAmt-=$carrierbhatta[$i]->bhatta;
                        }
                        if($trip[$i]->trip_id==$carrierhamali[$i]->trip_id){
                            $trip[$i]->totalBillAmt-= $carrierhamali[$i]->hamali;
                        }
                        $trip[$i]->totalBillAmt-=$trip[$i]->merchant_total_amount;
                        $totalVehicleIncome+=$trip[$i]->totalBillAmt;
                    }
                    // $totalBenefit=DB::table('merchant_trip')

                    return view('vehicleTransaction',['trip'=>$trip,'expense'=>$expense,'vehicle'=>$vehicle,
                    'dieselExpense'=>$dieselExpense,'maintainanceExpense'=>$maintainanceExpense,
                    'totalVehicleIncome'=>$totalVehicleIncome]);

                }
                catch(Exception $e){
                    print_r($e);
                }
            }
            else if($req->isMethod('POST')){
                try{
                    // print_r($month);
                    $trip=DB::table('merchant_trip')
                     ->join('customer_trip','merchant_trip.trip_id','customer_trip.trip_id')
                     ->where('merchant_trip.vehicle_number','=',$vehicleNumber)
                     ->whereMonth('merchant_trip.trip_date',$month)
                     ->select('customer_trip.trip_id','trip_date','product_quantity',
                     'loading_first_carrier','loading_second_carrier','unloading_first_carrier','unloading_second_carrier'
                     ,DB::raw('concat(delivery_district,",", delivery_taluka," ,", delivery_village) as delivery_address'),
                     DB::raw('sum(customer_total_amount) as totalBillAmt'),
                     DB::raw('merchant_total_amount as merchant_total_amount'))
                     ->groupby('customer_trip.trip_id')
                     ->orderby('merchant_trip.trip_id')
                     ->get();


                    // $expense=DB::table('vehicle_expense')
                    //  ->where('vehicle_number','=',$vehicleNumber)
                    //  ->whereMonth('vehicle_expense.expense_date',$month)
                    //  ->select(DB::raw('sum(diesel_expense) as diesel_expense'),
                    //  DB::raw('sum(maintainance_expense) as maintainance_expense'),
                    //  'expense_comment as expense_comment','expense_date','expense_id')
                    //  ->groupby('expense_date')
                    //  ->get();

                    $expense=DB::table('vehicle_expense')
                     ->where('vehicle_number','=',$vehicleNumber)
                     ->where('is_deleted', '=',0)
                     ->whereMonth('vehicle_expense.expense_date',$month)
                     ->select('diesel_expense',
                     'maintainance_expense',
                     'expense_comment','expense_date','expense_id')
                     ->groupby('expense_id')
                     ->get();


                     $driverbhatta=DB::table('merchant_trip')
                                ->leftjoin('driver_bhatta','driver_bhatta.trip_id','=','merchant_trip.trip_id')
                                ->where('vehicle_number','=',$vehicleNumber)
                                ->whereMonth('merchant_trip.trip_date',$month)
                                ->select('merchant_trip.trip_id','driver_bhatta.bhatta')
                                ->groupby('merchant_trip.trip_id')
                                ->orderby('merchant_trip.trip_id')
                                ->get();
                    $carrierbhatta=DB::table('merchant_trip')
                                ->leftjoin('carrier_bhatta','carrier_bhatta.trip_id','=','merchant_trip.trip_id')
                                ->where('vehicle_number','=',$vehicleNumber)
                                ->whereMonth('merchant_trip.trip_date',$month)
                                ->select('merchant_trip.trip_id',DB::raw('sum(carrier_bhatta.bhatta) as bhatta'))
                                ->groupby('merchant_trip.trip_id')
                                ->orderby('merchant_trip.trip_id')
                                ->get();


                    $carrierhamali=DB::table('merchant_trip')
                                ->leftjoin('carrier_salary','carrier_salary.trip_id','=','merchant_trip.trip_id')
                                ->where('vehicle_number','=',$vehicleNumber)
                                ->whereMonth('merchant_trip.trip_date',$month)
                                ->selectRaw('sum(hamali) as hamali, merchant_trip.trip_id as trip_id')
                                ->groupby('merchant_trip.trip_id')
                                ->orderby('merchant_trip.trip_id')
                                ->get();
                    $totalVehicleIncome=0;
                    for($i=0; $i< count($trip); $i++){
                        if($trip[$i]->trip_id==$driverbhatta[$i]->trip_id){
                            $trip[$i]->totalBillAmt-=$driverbhatta[$i]->bhatta;
                        }
                        if($trip[$i]->trip_id==$carrierbhatta[$i]->trip_id){
                            $trip[$i]->totalBillAmt-=$carrierbhatta[$i]->bhatta;
                        }
                        if($trip[$i]->trip_id==$carrierhamali[$i]->trip_id){
                            $trip[$i]->totalBillAmt-= $carrierhamali[$i]->hamali;
                        }
                        $trip[$i]->totalBillAmt-=$trip[$i]->merchant_total_amount;
                        $totalVehicleIncome+=$trip[$i]->totalBillAmt;
                    }
                    $dieselExpense=DB::table('vehicle_expense')
                                    ->where('vehicle_number','=',$vehicleNumber)
                                    ->whereMonth('vehicle_expense.expense_date',$month)
                                    ->selectRaw('sum(diesel_expense) as totalDiesel')
                                    ->get();
                    $maintainanceExpense=DB::table('vehicle_expense')
                                    ->where('vehicle_number','=',$vehicleNumber)
                                    ->whereMonth('vehicle_expense.expense_date',$month)
                                    ->selectRaw('sum(maintainance_expense) as totalMaintainance')
                                    ->get();
                    // print_r($dieselExpense);
                    // print_r($maintainanceExpense);
                    return view('vehicleTransaction',['trip'=>$trip,'expense'=>$expense,'vehicle'=>$vehicle,
                    'dieselExpense'=>$dieselExpense,'maintainanceExpense'=>$maintainanceExpense,
                    'totalVehicleIncome'=>$totalVehicleIncome]);
                }
                catch(Exception $e){
                    print_r($e);

                }
            }
        }



}
