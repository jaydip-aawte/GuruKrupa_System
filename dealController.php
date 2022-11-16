<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BizmanTransaction;
use App\BizmanTrip;
use App\DriverAbsentee;
use App\Deduction;
use App\bizman;
use Carbon\Carbon;
use Session;
use DB;
use Lang;


class dealController extends Controller
{
    //
    function getAbentDays(Request $req){
        if($req->isMethod('POST')){
            $driverid=$req->input('drivername');
            $data=DB::table('driver_absentee')
              ->select('driver_absentee.driver_id','absentee_date')
              ->where('driver_absentee.driver_id',$driverid)
              ->where('driver_absentee.is_absent','=',1)
              ->orderby('driver_absentee.driver_id','asc')
              ->get();
            // print_r($driverid);
            $driver=DB::table('driver')
                    ->where('driver_id','=',$driverid)
                    ->where('is_deleted','=',0)
                    ->get();
            return view('driverTimesheet',['data'=>$data,'driver'=>$driver]);
        }
        else{
            $driver=DB::table('driver')
                    ->where('is_deleted','=',0)
                    ->get();
            return view('dealPanel',['driver'=>$driver]);
        }
    }

    function saveAbsentee(Request $req){

        $dateValue=$req->input('datePick');
        $datePick=implode(',',$dateValue);
        $dt=explode(',',$datePick);
        $driver_id=$req->input('driverName');
        // print_r($driver_id);
        $data=DB::table('driver_absentee')
              ->select('driver_absentee.driver_id','absentee_date')
              ->where('driver_absentee.driver_id',$driver_id)
              ->where('driver_absentee.is_absent','=',1)
              ->orderby('driver_absentee.driver_id','asc')
              ->get()->toArray();
        $inputdatesArray=[];
        foreach($dt as $row){
            $d=date('Y-m-d', strtotime($row));
            array_push($inputdatesArray,$d);
        }
        $dbDatesArray=[];
            foreach($data as $row){
                $d=$row->absentee_date;
                array_push($dbDatesArray,$d);
            }
            //This will give newly added dates
            $datesToSave=array_diff($inputdatesArray,$dbDatesArray);
            $datesToSave = array_values($datesToSave);

            //This will give removed dates
            $datesToRemove=array_diff($dbDatesArray,$inputdatesArray);
            $datesToRemove = array_values($datesToRemove);


        $deductionAmount=$req->input('deduction_amount');
        try{
            if($deductionAmount>0){
                $deduction=new Deduction;
                $carbon=new Carbon($dt[0]);
                $deduction->driver_id=$driver_id;
                $deduction->year=$carbon->format('Y');
                $deduction->month=$carbon->format('m');
                $deduction->deduction_amount=$deductionAmount;
                $deduction->save();
            }
            for($i=0;$i< count($datesToSave);$i++){
                $absentee=new DriverAbsentee;
                $carbon=new Carbon($datesToSave[$i]);
                $absentee->absentee_date=$carbon->format('Y-m-d');
                $absentee->driver_id=$driver_id;
                $absentee->save();
            }
            for($i=0;$i< count($datesToRemove);$i++){
                DriverAbsentee::where(['driver_id'=>$driver_id,'absentee_date'=>$datesToRemove[$i]])->update(['is_absent'=>0]);
            }
            return redirect('home/timesheet')->with('message', Lang::get('saved'));

        }
        catch(Exception $e){
            print_r($e);
        }
    }

}




