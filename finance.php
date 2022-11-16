<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CustomerTrip;
use App\Customer;
use App\mine;
use App\carier;
use App\driver;
use Lang;
use Carbon\Carbon;
use DB;

class finance extends Controller
{
    public function getDetailFinance(Request $req){
        $bizman = DB::select('select * from merchant ');
        $customer = DB::select('select * from customer');
        $mine = DB::select('select * from mine');


        $months = array(
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July ',
            'August',
            'September',
            'October',
            'November',
            'December',
        );

        if($req->is('home/finance')){
            $date=Carbon::now()->toDateString();
            if($req->isMethod('POST')){
                $date=$req->input('paymentDate');
            }
            $resultEarning=$this->getDailyEarning($date);
            $resultExpenses=$this->getDailyExpense($date);
            return view('finance',['resultEarning'=>$resultEarning,'resultExpenses'=>$resultExpenses]);
        }
        if($req->is('home/finance/monthly')){
            $month=Carbon::now()->month;
            // print_r("Hi");
            if($req->isMethod('POST')){
                $month=$req->input('monthName');
            }
            $monthlyEarning=$this->getMonthlyEarning($month);
            [$expenseDatesArr,$expenseAmtArr]=$this->getMonthlyExpense($month);
            return view('monthly',['monthlyEarning'=>$monthlyEarning,'expenseDatesArr'=>$expenseDatesArr,'expenseAmtArr'=>$expenseAmtArr]);
        }
        if($req->is('home/finance/yearly')){
            $calYearlyEarn=$this->getYearlyEarning($months);
            $calYearlyExpense=$this->getYearlyExpense($months);
            return view('yearly',['calYearlyEarn'=>$calYearlyEarn,'calYearlyExpense'=>$calYearlyExpense]);
        }
        if($req->is('home/finance/other')){
            if($req->isMethod('POST')){
                $from=$req->input('from');
                $to=$req->input('to');
                    $otherearning=DB::table('other_earnings')
                            ->select(array('other_earnings.name as name','other_earnings.amount as transaction_amount','other_earnings.date as transaction_date','other_earnings.comment'))
                            ->whereBetween('other_earnings.date',[$from,$to])
                            ->get()->toArray();

                    $otherexpense=DB::table('other_expenses')
                            ->select(array('other_expenses.name as name','other_expenses.amount as transaction_amount','other_expenses.date as transaction_date','other_expenses.comment'))
                            ->whereBetween('other_expenses.date',[$from,$to])
                            ->get()->toArray();
            }
            else{
                $otherearning=DB::table('other_earnings')
                    ->select(array('other_earnings.name as name','other_earnings.amount as transaction_amount','other_earnings.date as transaction_date','other_earnings.comment'))
                    ->get()->toArray();

                $otherexpense=DB::table('other_expenses')
                    ->select(array('other_expenses.name as name','other_expenses.amount as transaction_amount','other_expenses.date as transaction_date','other_expenses.comment'))
                    ->get()->toArray();
            }
            return view('other',['otherearning'=>$otherearning,'otherexpense'=>$otherexpense]);

        }
}


    public function getDailyEarning($date){
        //<--------------- Daily Earning Table Queries --------------->
        // $date=$req->input('paymentDate');

        $custearning=DB::table('customer')
                 ->leftjoin('customer_transaction', 'customer_transaction.customer_id', '=', 'customer.customer_id')
                 ->select(array('customer.customer_name','customer_transaction.transaction_amount','customer_transaction.transaction_date','customer_transaction.comment'))
                 ->where('customer_transaction.transaction_date','=',$date)
                 ->get()->toArray();
        $minecustearning=DB::table('mine_customer')
                 ->leftjoin('mine_customer_transaction', 'mine_customer_transaction.mine_customer_id', '=', 'mine_customer.customer_id')
                 ->select(array('mine_customer.customer_name','mine_customer_transaction.transaction_amount','mine_customer_transaction.transaction_date','mine_customer_transaction.comment'))
                 ->where('mine_customer_transaction.transaction_date','=',$date)
                 ->get()->toArray();
        $resultEarning=array_merge($custearning,$minecustearning);
        // print_r($resultEarning);
        return $resultEarning;


    }
    public function getDailyExpense($date){
        //<------------------Daily Expense Table Queries------------------>

        $merchantExpense=DB::table('merchant')
        ->leftjoin('merchant_transaction', 'merchant_transaction.merchant_id', '=', 'merchant.merchant_id')
        ->select(array('merchant.merchant_name as name','merchant_transaction.transaction_amount','merchant_transaction.transaction_date','merchant_transaction.comment'))
        ->where('merchant_transaction.transaction_date','=',$date)
        ->get()->toArray();

        $driverExpense=DB::table('driver')
        ->leftjoin('driver_expense', 'driver_expense.driver_id', '=', 'driver.driver_id')
        ->select(array('driver.driver_name as name','driver_expense.paid_amount as transaction_amount','driver_expense.expense_date as transaction_date','driver_expense.comment'))
        ->where('driver_expense.expense_date','=',$date)
        ->get()->toArray();

        $carrierExpense=DB::table('carrier')
        ->leftjoin('carrier_expense', 'carrier_expense.carrier_id', '=', 'carrier.carrier_id')
        ->select(array('carrier.carrier_name as name','carrier_expense.paid_amount as transaction_amount','carrier_expense.expense_date as transaction_date','carrier_expense.comment'))
        ->where('carrier_expense.expense_date','=',$date)
        ->get()->toArray();

        $vehicleExpense=DB::table('vehicle')
        ->leftjoin('vehicle_expense', 'vehicle_expense.vehicle_number', '=', 'vehicle.vehicle_number')
        ->select(array('vehicle.vehicle_number as name',DB::raw('vehicle_expense.diesel_expense + vehicle_expense.maintainance_expense as transaction_amount'),'vehicle_expense.expense_date as transaction_date','vehicle_expense.expense_comment as comment'))
        ->where('vehicle_expense.expense_date','=',$date)
        // ->groupby('')
        ->get()->toArray();
        // print_r(json_encode($vehicleExpense));
        $mineExpense=DB::table('mine')
        ->leftjoin('mine_expense', 'mine_expense.mine_id', '=', 'mine.mine_id')
        ->select(array('mine.mine_owner_name as name',DB::raw('mine_expense.diesel_expense + mine_expense.horizontal_machine + mine_expense.generator + mine_expense.power_trailor + mine_expense.other + mine_expense.royalty as transaction_amount'),'mine_expense.transaction_date','mine_expense.royalty_comment as comment'))
        ->where('mine_expense.transaction_date','=',$date)
        ->get()->toArray();


        $mineWorkerExpense=DB::table('mine_worker')
        ->leftjoin('mine_worker_expense', 'mine_worker_expense.worker_id', '=', 'mine_worker.worker_id')
        ->select(array('mine_worker.worker_name as name','mine_worker_expense.transaction_amount','mine_worker_expense.transaction_date','mine_worker_expense.comment'))
        ->where('mine_worker_expense.transaction_date','=',$date)
        ->get()->toArray();

        //This is the final expense of the day
        $resultExpenses=array_merge($merchantExpense,$driverExpense,$carrierExpense,$vehicleExpense,$mineExpense,$mineWorkerExpense);
        //<------------------Daily expense ends here-------------->
        return $resultExpenses;

    }

    public function getMonthlyEarning($month){
        $monthlycustearning = DB::table('customer_transaction')
            // ->join('mine_customer_transaction','mine_customer_transaction.transaction_date','=','customer_transaction.transaction_date')
            ->whereYear('customer_transaction.transaction_date',Carbon::now()->year)
            ->whereMonth('customer_transaction.transaction_date',$month)
            ->selectRaw('customer_transaction.transaction_date as day,sum(customer_transaction.transaction_amount) as transaction_amount,customer_transaction.comment as comment')
            ->groupby('day')
            ->orderby('day','asc')
            ->get()->toArray();
        //  print_r($monthlycustearning);
            $monthlyminecustearning = DB::table('mine_customer_transaction')
            // ->join('mine_customer_transaction','mine_customer_transaction.transaction_date','=','customer_transaction.transaction_date')
            ->whereYear('mine_customer_transaction.transaction_date',Carbon::now()->year)
            ->whereMonth('mine_customer_transaction.transaction_date',$month)
            ->selectRaw('mine_customer_transaction.transaction_date as day,sum(mine_customer_transaction.transaction_amount) as transaction_amount,mine_customer_transaction.comment as comment')
            ->groupby('day')
            ->orderby('day','asc')
            ->get()->toArray();
            if(count($monthlycustearning)>count($monthlyminecustearning)){
                foreach($monthlycustearning as $cust){
                    foreach($monthlyminecustearning as $minecust){
                        if($minecust->day==$cust->day){
                            $cust->transaction_amount=$minecust->transaction_amount + $cust->transaction_amount;
                            $cust->comment=$minecust->comment.' '.$cust->comment;
                        }
                    }
                }
                $monthlyEarning=$monthlycustearning;

            }
            else{
                foreach($monthlyminecustearning as $minecust){
                    foreach($monthlycustearning as $cust){
                        if($minecust->day==$cust->day){
                            $minecust->transaction_amount=$minecust->transaction_amount + $cust->transaction_amount;
                            $minecust->comment=$minecust->comment.'.'.$cust->comment;
                        }
                    }
                }
                $monthlyEarning=$monthlyminecustearning;
            }

        return $monthlyEarning;
    }

    public function getMonthlyExpense($month){
        $monthlyMerchantExpense=DB::table('merchant')
            ->leftjoin('merchant_transaction', 'merchant_transaction.merchant_id', '=', 'merchant.merchant_id')
            ->whereYear('merchant_transaction.transaction_date',Carbon::now()->year)
            ->whereMonth('merchant_transaction.transaction_date',$month)
            // ->select(array('merchant.merchant_name as name','merchant_transaction.transaction_amount','merchant_transaction.transaction_date','merchant_transaction.comment'))
            ->selectRaw('merchant_transaction.transaction_date as day,sum(merchant_transaction.transaction_amount) as transaction_amount,merchant_transaction.comment as comment')
            ->groupby('day')
            ->orderby('day','asc')
            ->get()->toArray();

            $monthlyVehicleExpense=DB::table('vehicle')
            ->leftjoin('vehicle_expense', 'vehicle_expense.vehicle_number', '=', 'vehicle.vehicle_number')
            ->whereYear('vehicle_expense.expense_date',Carbon::now()->year)
            ->whereMonth('vehicle_expense.expense_date',$month)
            ->selectRaw('vehicle_expense.expense_date as day,sum(vehicle_expense.diesel_expense + vehicle_expense.maintainance_expense) as transaction_amount,vehicle_expense.expense_comment as comment')
            ->groupby('day')
            ->orderby('day','asc')
            ->get()->toArray();

            $monthlyDriverExpense=DB::table('driver')
            ->leftjoin('driver_expense', 'driver_expense.driver_id', '=', 'driver.driver_id')
            ->whereYear('driver_expense.expense_date',Carbon::now()->year)
            ->whereMonth('driver_expense.expense_date',$month)
            ->selectRaw('driver_expense.expense_date as day,sum(driver_expense.paid_amount) as transaction_amount,CONCAT(driver_expense.comment) as comment')
            ->groupby('day')
            ->orderby('day','asc')
            ->get()->toArray();
            // print_r($monthlyDriverExpense);

            $monthlyCarrierExpense=DB::table('carrier')
            ->leftjoin('carrier_expense', 'carrier_expense.carrier_id', '=', 'carrier.carrier_id')
            ->whereYear('carrier_expense.expense_date',Carbon::now()->year)
            ->whereMonth('carrier_expense.expense_date',$month)
            ->selectRaw('carrier_expense.expense_date as day,sum(carrier_expense.paid_amount) as transaction_amount,carrier_expense.comment as comment')
            ->groupby('day')
            ->orderby('day','asc')
            ->get()->toArray();

            $monthlyMineExpense=DB::table('mine')
            ->leftjoin('mine_expense', 'mine_expense.mine_id', '=', 'mine.mine_id')
            ->whereYear('mine_expense.transaction_date',Carbon::now()->year)
            ->whereMonth('mine_expense.transaction_date',$month)
            ->selectRaw('mine_expense.transaction_date as day,sum(mine_expense.diesel_expense + mine_expense.horizontal_machine +
            mine_expense.generator + mine_expense.power_trailor + mine_expense.other +
            mine_expense.royalty) as transaction_amount,mine_expense.royalty_comment as comment')
            ->groupby('day')
            ->orderby('day','asc')
            ->get()->toArray();

            $monthlyMineWorkerExpense=DB::table('mine_worker')
            ->leftjoin('mine_worker_expense', 'mine_worker_expense.worker_id', '=', 'mine_worker.worker_id')
            ->whereYear('mine_worker_expense.transaction_date',Carbon::now()->year)
            ->whereMonth('mine_worker_expense.transaction_date',$month)
            ->selectRaw('mine_worker_expense.transaction_date as day,sum(mine_worker_expense.transaction_amount) as transaction_amount,mine_worker_expense.comment as comment')
            ->groupby('day')
            ->orderby('day','asc')
            ->get()->toArray();

        $monthlyExpense=array_merge($monthlyMerchantExpense,$monthlyVehicleExpense,$monthlyDriverExpense,
        $monthlyCarrierExpense,$monthlyMineExpense,$monthlyMineWorkerExpense);
        $expenseDatesArr=[];
        for($i=0;$i< count($monthlyExpense); $i++){
            $expenseDatesArr[$i]=$monthlyExpense[$i]->day;
        }
        $expenseDatesArr=array_unique($expenseDatesArr);
        $expenseDatesArr=array_values($expenseDatesArr);
        $expenseAmtArr=[];
        for($i=0;$i < count($expenseDatesArr); $i++){
            $expenseAmt=0;
            for($j=0;$j < count($monthlyExpense); $j++){
                if($monthlyExpense[$j]->day==$expenseDatesArr[$i]){
                    $expenseAmt+=$monthlyExpense[$j]->transaction_amount;
                }
            }
            $expenseAmtArr[$i]=$expenseAmt;
        }
        return [$expenseDatesArr,$expenseAmtArr];
    }

    public function getYearlyEarning($months){
        $yearlyCustEarning=DB::table('customer_transaction')
                    ->whereYear('customer_transaction.transaction_date','=',Carbon::now()->year)
                    ->select(DB::raw('sum(customer_transaction.transaction_amount) as transaction_amount'),DB::raw('monthname(customer_transaction.transaction_date) as month'))
                    ->groupby(DB::raw('monthname(customer_transaction.transaction_date)'))
                    ->orderby(DB::raw('month','asc'))
                    ->get()->toArray();

                $yearlyMineCustEarning=DB::table('mine_customer_transaction')
                    ->whereYear('mine_customer_transaction.transaction_date','=',Carbon::now()->year)
                    ->select(DB::raw('sum(mine_customer_transaction.transaction_amount) as transaction_amount'),DB::raw('monthname(mine_customer_transaction.transaction_date) as month'))
                    ->groupby(DB::raw('monthname(mine_customer_transaction.transaction_date)'))
                    ->orderby(DB::raw('month','asc'))
                    ->get()->toArray();

                    $yearlyEarning=array_merge($yearlyCustEarning,$yearlyMineCustEarning);
                    $calYearlyEarn=$months;
                    // print_r($calYearlyEarn);
                    foreach($calYearlyEarn as $month){
                        $amt=0;
                        foreach($yearlyEarning as $earning){
                            if($earning->month==$month){
                                $earning->transaction_amount+=$amt;
                                $amt=$earning->transaction_amount;
                            }
                        }
                            $calYearlyEarn[$month] = $amt;
                    }
        return $calYearlyEarn;
    }

    public function getYearlyExpense($months){
        $yearlyMerchantExpense=DB::table('merchant')
            ->leftjoin('merchant_transaction', 'merchant_transaction.merchant_id', '=', 'merchant.merchant_id')
            ->whereYear('merchant_transaction.transaction_date',Carbon::now()->year)
            ->selectRaw('monthname(merchant_transaction.transaction_date) as month,sum(merchant_transaction.transaction_amount) as transaction_amount,merchant_transaction.comment as comment')
            ->groupby('month')
            ->orderby('month','asc')
            ->get()->toArray();

        $yearlyVehicleExpense=DB::table('vehicle')
        ->leftjoin('vehicle_expense', 'vehicle_expense.vehicle_number', '=', 'vehicle.vehicle_number')
        ->whereYear('vehicle_expense.expense_date',Carbon::now()->year)
        ->selectRaw('monthname(vehicle_expense.expense_date) as month,sum(vehicle_expense.diesel_expense + vehicle_expense.maintainance_expense) as transaction_amount,vehicle_expense.expense_comment as comment')
        ->groupby('month')
        ->orderby('month','asc')
        ->get()->toArray();

        $yearlyDriverExpense=DB::table('driver')
        ->leftjoin('driver_expense', 'driver_expense.driver_id', '=', 'driver.driver_id')
        ->whereYear('driver_expense.expense_date',Carbon::now()->year)
        ->selectRaw('monthname(driver_expense.expense_date) as month,sum(driver_expense.paid_amount) as transaction_amount,driver_expense.comment as comment')
        ->groupby('month')
        ->orderby('month','asc')
        ->get()->toArray();

        $yearlyCarrierExpense=DB::table('carrier')
        ->leftjoin('carrier_expense', 'carrier_expense.carrier_id', '=', 'carrier.carrier_id')
        ->whereYear('carrier_expense.expense_date',Carbon::now()->year)
        ->selectRaw('monthname(carrier_expense.expense_date) as month,sum(carrier_expense.paid_amount) as transaction_amount,carrier_expense.comment as comment')
        ->groupby('month')
        ->orderby('month','asc')
        ->get()->toArray();

        $yearlyMineExpense=DB::table('mine')
        ->leftjoin('mine_expense', 'mine_expense.mine_id', '=', 'mine.mine_id')
        ->whereYear('mine_expense.transaction_date',Carbon::now()->year)
        ->selectRaw('monthname(mine_expense.transaction_date) as month,sum(mine_expense.diesel_expense + mine_expense.horizontal_machine +
        mine_expense.generator + mine_expense.power_trailor + mine_expense.other +
        mine_expense.royalty) as transaction_amount,mine_expense.royalty_comment as comment')
        ->groupby('month')
        ->orderby('month','asc')
        ->get()->toArray();

        $yearlyMineWorkerExpense=DB::table('mine_worker')
        ->leftjoin('mine_worker_expense', 'mine_worker_expense.worker_id', '=', 'mine_worker.worker_id')
        ->whereYear('mine_worker_expense.transaction_date',Carbon::now()->year)
        ->selectRaw('monthname(mine_worker_expense.transaction_date) as month,sum(mine_worker_expense.transaction_amount) as transaction_amount,mine_worker_expense.comment as comment')
        ->groupby('month')
        ->orderby('month','asc')
        ->get()->toArray();

        $yearlyExpense=array_merge($yearlyMerchantExpense,$yearlyVehicleExpense,$yearlyDriverExpense,$yearlyCarrierExpense
                        ,$yearlyMineExpense,$yearlyMineWorkerExpense);
            $calYearlyExpense=$months;
            foreach($calYearlyExpense as $month){
                $amt=0;
                foreach($yearlyExpense as $expense){
                    if($expense->month==$month){
                        $expense->transaction_amount+=$amt;
                        $amt=$expense->transaction_amount;
                    }
                }
                    $calYearlyExpense[$month] = $amt;
            }
        return $calYearlyExpense;
    }
}
