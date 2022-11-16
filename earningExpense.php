<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CustomerTrip;
use App\Customer;
use App\CustomerTransaction;
use App\BizmanTransaction;
use App\OtherEarnings;
use App\MineCustomerTransaction;
use App\VehicleExpense;
use App\DriverExpense;
use App\CarrierExpense;
use App\MineExpense;
use App\OtherExpenses;
use App\MineWorkerExpense;
use Lang;
use DB;

class earningExpense extends Controller
{
//Earning Module Starts Here
        public function saveCustEarning(Request $req){
            $cust_earning=new CustomerTransaction;
            $date=$req->input('earningDateCust');
            $custid=$req->input('customer_name');
            $amount=$req->input('amount');
            $comment=$req->input('comment');
            try{

                $cust_earning->customer_id=$custid;
                $cust_earning->transaction_date=$date;
                $cust_earning->transaction_amount=$amount;
                $cust_earning->comment=$comment;
                $cust_earning->save();
                // print_r($custid);
                $status=new CheckStatus;
                $status->updateCustStatus($custid);
                return redirect('home/earning')->with('message',Lang::get('saved'));
            }
            catch(Exception $e){
                return redirect('home/earning')->with('message',Lang::get('error'));
                // print_r($e);
            }

        }


        public function saveMineCustEarning(Request $req){

            $mineCust_earning=new MineCustomerTransaction;
             $date=$req->input('earningDateMineCust');
             $mineCustid=$req->input('mineCust_name');
            $amount=$req->input('mineCust_amount');
            $comment=$req->input('mineCust_comment');
            try{
                $mineCust_earning->mine_customer_id=$mineCustid;
                $mineCust_earning->transaction_date=$date;
                $mineCust_earning->transaction_amount=$amount;
                $mineCust_earning->comment=$comment;
                $mineCust_earning->save();
                $status=new CheckStatus;
                $status->updateMineCustStatus($mineCustid);
                return redirect('home/earning')->with('message',Lang::get('saved'));
            }
            catch(Exception $e){
                return redirect('home/earning')->with('message',Lang::get('error'));
            }

        }

        public function saveOtherEarning(Request $req){

            $other_earning=new OtherEarnings;
            $date=$req->input('othersEarningDate');
            $name=$req->input('othersEarningName');
            $amount=$req->input('othersEarningAmt');
            $comment=$req->input('othersEarningComment');
            try{
                $other_earning->name=$name;
                $other_earning->date=$date;
                $other_earning->amount=$amount;
                $other_earning->comment=$comment;
                $other_earning->save();
                // print_r($other_earning);
                return redirect('home/earning')->with('message',Lang::get('saved'));
            }
            catch(Exception $e){
                return redirect('home/earning')->with('message',Lang::get('error'));
                // print_r($e);
            }

        }



        public function getDetailEarning(Request $req){
            $mineCust = DB::table('mine_customer')
                    ->where('is_deleted','=',0)
                    ->select(array('customer_id','customer_name'))->get();

            $customer = DB::table('customer')
                        ->where('is_deleted','=',0)
                        ->select(array('customer_id','customer_name'))
                        ->get();

                return view('earningPanel',['customer'=>$customer,'mineCust'=>$mineCust]);

        }


    //Earning module ends here

        public function getDetailExpense(Request $req){

            $bizman = DB::table('merchant')
                  ->select(array('merchant_id','merchant_name','merchant_address','merchant_phn_no'))
                  ->where('is_deleted','=',0)
                  ->get();
            $customer = DB::table('customer')
                        ->select(array('customer_id','customer_name','customer_address','customer_phn_no'))
                        ->where('is_deleted','=',0)
                        ->get();
            $vehicle = DB::table('vehicle')
                        ->select(array('vehicle_id','vehicle_number'))
                        ->where('is_deleted','=',0)
                        ->get();
            $driver = DB::table('driver')
                        ->select(array('driver_id','driver_name','driver_phn_no'))
                        ->where('is_deleted','=',0)
                        ->get();
            $carrier = DB::table('carrier')
                        ->select(array('carrier_id','carrier_name'))
                        ->where('is_deleted','=',0)
                        ->get();
           $mineworker = DB::table('mine_worker')
                        ->select(array('worker_id','worker_name'))
                        ->where('is_deleted','=',0)
                        ->get();

                return view('expensePanel',['customer'=>$customer,
                'bizman'=>$bizman,'vehicle'=>$vehicle,'carrier'=>$carrier,'driver'=>$driver,'mine_worker'=>$mineworker]);

            }





        public function saveVehicleExpense(Request $req){
            $expense=new VehicleExpense;

            $expense->expense_date=$req->input('vehicleExpenseDate');
            $expense->vehicle_number=$req->input('vehicle_number');
            $expense->diesel_expense=$req->input('vehicleDiesel');
            $expense->maintainance_expense=$req->input('vehicleMaintainance');
            
            $expense->expense_comment=$req->input('vehicleExpenseComment');
            try{
                $expense->save();
                return redirect('home/expense')->with('message',Lang::get('saved'));
            }
            catch(Exception $e){
                return redirect('home/expense')->with('message',Lang::get('error'));
                // print_r($e);
            }


        }
        public function saveDriverExpense(Request $req){
            $expense=new DriverExpense;

            $expense->expense_date=$req->input('driverExpenseDate');
            $expense->driver_id=$req->input('driver_name');
            $expense->paid_amount=$req->input('driverExpenseAmt');
            $expense->comment=$req->input('driverExpenseComment');
            try{
                $expense->save();
                return redirect('home/expense')->with('message',Lang::get('saved'));
            }
            catch(Exception $e){
                return redirect('home/expense')->with('message',Lang::get('error'));
                // print_r($e);
            }



        }
        public function saveCarrierExpense(Request $req){
            $expense=new CarrierExpense;

            $expense->expense_date=$req->input('carrierExpenseDate');
            $expense->carrier_id=$req->input('carrier_name');
            $expense->paid_amount=$req->input('carrierExpenseAmt');
            $expense->comment=$req->input('carrierExpenseComment');
            try{
                $expense->save();
                return redirect('home/expense')->with('message',Lang::get('saved'));
            }
            catch(Exception $e){
                return redirect('home/expense')->with('message',Lang::get('error'));
                // print_r($e);
            }

        }
        public function saveMerchantExpense(Request $req){
            $expense=new BizmanTransaction;

            $expense->transaction_date=$req->input('transaction_date');
            $merchantid=$req->input('merchant_name');
            $expense->merchant_id=$merchantid;
            if($req->input('merchantExpenseAmt')!==null){
                $expense->transaction_amount=$req->input('merchantExpenseAmt');
            }
            $expense->comment=$req->input('merchantExpenseComment');

            try{
                $expense->save();
                $status=new CheckStatus;
                $status->updateMerchantStatus($merchantid);
                return redirect('home/expense')->with('message',Lang::get('saved'));
            }
            catch(Exception $e){
                return redirect('home/expense')->with('message',Lang::get('error'));
            }

        }
        public function saveMineExpense(Request $req){

            $mineExpenseDate=$req->input('mineExpenseDate');
            $dieselexpense=$req->input('dieselexpense');
            $machinecomment=$req->input('dieselcomment');
            $royaltyamount=$req->input('royaltyamount');
            $royaltycomment=$req->input('royaltycomment');
            $generatoramount=$req->input('generatoramount');
            $generatorcomment=$req->input('generatorcomment');
            $horizontalamount=$req->input('horizontalamount');
            $horizontalcomment=$req->input('horizontalcomment');
            $powertraileramount=$req->input('powertraileramount');
            $powertrailercomment=$req->input('powertrailercomment');
            $othersamount=$req->input('othersamount');
            $otherscomment=$req->input('otherscomment');

            $mineExpense=new MineExpense;
            $mineExpense->transaction_date=$mineExpenseDate;
            $mineExpense->diesel_expense=$dieselexpense;
            $mineExpense->diesel_comment=$machinecomment;
            $mineExpense->royalty=$royaltyamount;
            $mineExpense->royalty_comment=$royaltycomment;
            $mineExpense->generator=$generatoramount;
            $mineExpense->generator_comment=$generatorcomment;
            $mineExpense->horizontal_machine=$horizontalamount;
            $mineExpense->horizontal_machine_comment=$horizontalcomment;
            $mineExpense->power_trailor=$powertraileramount;
            $mineExpense->power_trailor_comment=$powertrailercomment;
            $mineExpense->other=$othersamount;
            $mineExpense->other_comment=$otherscomment;

            try{
                // print_r($mineExpenseDate);
                $mineExpense->save();
                return redirect('home/expense')->with('message',Lang::get('saved'));

            }
            catch(Exception $e){
                // print_r($e);
                return redirect('home/earning')->with('message',Lang::get('error'));
            }

        }

public function saveMineEmployeeExpense(Request $req){
            $mineWorkerexpense=new MineWorkerExpense;
            $mineWorkerexpense->worker_id=$req->input('worker_id');
            $mineWorkerexpense->transaction_date=$req->input('paymentDateWorker');
            $mineWorkerexpense->transaction_amount=$req->input('mineWorkerAmount');
            $mineWorkerexpense->comment=$req->input('mineWorkercomment');
        try{
            $mineWorkerexpense->save();
            return redirect('home/expense')->with('message',Lang::get('saved'));
        }
        catch(Exception $e)
        {
            print_r($e);
        }
     }


public function saveOtherExpense(Request $req){
           $otherexpense=new OtherExpenses;
           $otherexpense->date=$req->input('otherExpenseDate');
           $otherexpense->name=$req->input('othername');
           $otherexpense->amount=$req->input('otherExpenseAmount');
           $otherexpense->comment=$req->input('otherExpenseNote');
           try{
               $otherexpense->save();
               return redirect('home/expense')->with('message',Lang::get('saved'));
           }
           catch(Exception $e){
               print_r($e);
           }

        }
       
    }
