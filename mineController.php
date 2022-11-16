<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\product;
use App\MineCustomer;
use App\mine;
use App\machine;
use App\maintenance;
use App\diesel;
use App\royalty;
use App\employee_manager_payment;
use App\MineExpense;
use App\MineWorker;
use DB;
use Session;
use Lang;
use POST;
class mineController extends Controller
{

    public function saveMineCustomer(Request $req){
        $customer=new MineCustomer;
        $customer->customer_name=$req->customerName;
        $customer->customer_address=$req->customerAddr;
        $customer->customer_phn_no=$req->customerPhnNo;
        $customer->customer_alt_phn_no=$req->customerAltPhnNo;
        $customer->customer_mail=$req->customerMail;
        try{
            $data=DB::select('select * from mine_customer where customer_phn_no ='.$req->customerPhnNo);
            if($data != null){
                $req->session()->flash('message', Lang::get('already_present'));
                return redirect('home/mine/mineCustomer');
            }
            else{
                $query=$customer->save();
                if($query){
                    $req->session()->flash('message', Lang::get('saved'));
                    return redirect('home/mine/mineCustomer');
                }
            }

        }
        catch(Exception $e){
            return redirect('home/mine/mineCustomer')->with('message',Lang::get('error'));
            // print_r($e);
        }

    }
    public function saveMineEmployee(Request $req){
        $mineworker=new MineWorker;
        $mineworker->worker_name=$req->workerName;
        $mineworker->worker_address=$req->workerAddr;
        $mineworker->worker_phn_no=$req->workerPhnNo;
        $mineworker->worker_rate=$req->workerRate;
        $mineworker->worker_alt_phn_no=$req->workerAltPhnNo;
        try{
            $data=DB::select('select * from mine_worker where worker_phn_no ='.$req->workerPhnNo);
            if($data != null){
                $req->session()->flash('message', Lang::get('already_present'));
                return redirect('home/mine/mineCustomer');
            }
            else{
                $query=$mineworker->save();
                if($query){
                    $req->session()->flash('message', Lang::get('saved'));
                    return redirect('home/mine/mineCustomer');
                }
            }

        }
        catch(Exception $e){
            return redirect('home/mine/mineCustomer')->with('message',Lang::get('error'));
            // print_r($e);
        }

    }


    public function getMinePanel(Request $req){
        try{
            $mineproduct = DB::select('select * from product where is_deleted= 0');
            $mine = DB::select('select * from mine where is_deleted=0');
            return view('minepanel',['mineproduct'=>$mineproduct,'mine'=>$mine]);
        }
        catch(Exception $e){
            print_r($e);

        }
    }
    public function getMineMaintainance(Request $req){
        try{
            $owner=$req->get('mineName');
            $mine=DB::select('select * from mine');
            $fromdate=$req->input('fromdate');
            $todate=$req->input('todate');

            if($req->isMethod('GET')){
            $maintainance=DB::table('mine_expense')
                        ->where('mine_expense.is_deleted','=',0)
                          ->selectRaw('sum(diesel_expense) as diesel_expense,sum(royalty) as royalty,sum(generator) as generator,sum(horizontal_machine) as horizontal_machine,
                          sum(power_trailor) as power_trailor,sum(other) as other,transaction_date,
                          concat(diesel_comment) as diesel_comment,royalty_comment,generator_comment,horizontal_machine_comment,power_trailor_comment,
                          other_comment,mine_expense.expense_id as expense_id')
                          ->groupby('transaction_date')
                          ->get();
                        //   print_r(json_encode($maintainance));

            }
            else if($req->isMethod('POST')){

                $maintainance=DB::table('mine')
                          ->join('mine_expense','mine.mine_id','=','mine_expense.mine_id')
                          ->where('mine.is_deleted','=',0)
                          ->where('mine_expense.mine_id','=',$owner)
                          ->where('mine_expense.is_deleted','=',0)
                          ->whereBetween('transaction_date',[$fromdate,$todate])
                          ->selectRaw('sum(diesel_expense) as diesel_expense,sum(royalty) as royalty,sum(generator) as generator,sum(horizontal_machine) as horizontal_machine,
                          sum(power_trailor) as power_trailor,sum(other) as other,transaction_date,
                          concat(diesel_comment) as diesel_comment,royalty_comment,generator_comment,horizontal_machine_comment,power_trailor_comment,
                          other_comment,mine_expense.expense_id as expense_id')
                          ->groupby('transaction_date')
                          ->get();

            }
            return view('mineMaintainance',['maintainance'=>$maintainance,'mine'=>$mine]);
        }
        catch(Exception $e)
        {
            print_r($e);
        }
    }

    public function getMineCustomer(Request $req){
        $uname=$req->input('searchkeyname');
        $filter=$req->input('filter');
        // print_r($uname);
        if($req->isMethod('GET')){
            try{
                $customer = DB::select('select * from mine_customer where is_deleted= 0');

                return view('mineCustomer',['customer'=>$customer]);
            }
            catch(Exception $e){
                print_r($e);
            }
        }
        else if($req->isMethod('POST')){
            try{
                if($filter==2){
                    $customer = MineCustomer::select('*')
                    ->where('is_deleted', '=',0)
                    ->get();
                }
                else if($filter==3){
                    $customer=DB::table('mine_customer')
                    ->select('*')
                    ->whereNotIn('customer_id',function($query){
                        $query->select('mine_customer_id')->from('mine_trip');
                    })
                    ->where('status','=', 1)
                    ->where('customer_name', 'LIKE', '%'.$uname.'%')
                    ->get();
                    // print_r(json_encode($customer));
                }
                else if($filter==1){
                $customer = MineCustomer::select('*')
                ->where('is_deleted', '=',0)
                ->whereIn('customer_id',function($query){
                    $query->select('mine_customer_id')->from('mine_trip');
                })
                ->where('status','=', 1)
                ->where('customer_name', 'LIKE', '%'.$uname.'%')
                ->get();
                }
                else{
                    $customer=MineCustomer::select('*')
                              ->where('is_deleted','=',0)
                              ->where('status','=', 0)
                              ->where('customer_name', 'LIKE', '%'.$uname.'%')
                              ->get();
                }
                // print_r(json_encode($filter));
                // print_r(json_encode($uname));
                // print_r(json_encode($customer));
                return view('mineCustomer',['customer'=>$customer]);
            }
            catch(Exception $e){
                print_r($e);
            }
        }
    }

    public function editMineCustomer(Request $req,$id){
        $name=$req->input('customerName');
        $address=$req->input('customerAddr');
        $phn=$req->input('customerPhnNo');
        $altphn=$req->input('customerAltPhnNo');
        $mail=$req->input('customerMail');
        // print_r($phn);
        try{
            // $data=DB::select('select * from customer where customer_id='.$id);
            $query=DB::update('update mine_customer set customer_name = ? ,customer_address = ? ,customer_phn_no = ? ,customer_alt_phn_no = ?  ,customer_mail = ? where customer_id = ?',[$name,$address,$phn,$altphn,$mail,$id]);
            if($query){
                return redirect('home/mine/mineCustomer')->with('message',Lang::get('updated'));
            }
        }
        catch(Exception $e){
            return redirect('home/mine/mineCustomer')->with('message',Lang::get('error'));
            // print_r($e);
        }


    }

    public function getMineWorkerTransaction(Request $req,$id){
        $from=$req->input('fromdate');
        $to=$req->input('todate');
        try{
            $worker=DB::select('select * from mine_worker where worker_id='.$id);
            $product=DB::select('select * from product');
            if($req->isMethod('GET')){
                $trip=DB::table('mine_trip')
                    ->select('mine_trip.*')
                    ->groupby('mine_trip_id')
                    ->orderby('mine_trip_date','asc')
                    ->get();
                // $merchantTrip=DB::table('')

                $transaction=DB::table('mine_worker_expense')
                            ->select('mine_worker_expense.*')
                            ->where('worker_id','=',$id)
                            ->get();
                $sumOfChira=DB::table('mine_trip')
                            ->selectRaw('sum(product_quantity) as sum')
                            ->get();
                // print_r($sumOfChira[0]->sum);

            }
            else if($req->isMethod('POST')){
                $trip=DB::table('mine_trip')
                    ->select('mine_trip.*')
                    ->whereBetween('mine_trip_date',[$from,$to])
                    ->orderby('mine_trip_date','asc')
                    ->get();
                    //  print_r(json_encode($trip));
                $transaction=DB::table('mine_worker_expense')
                            ->select('mine_worker_expense.*')
                            ->whereBetween('transaction_date',[$from,$to])
                            ->where('worker_id','=',$id)
                            ->orderby('transaction_date','asc')
                            ->get();
                $sumOfChira=DB::table('mine_trip')
                            ->selectRaw('sum(product_quantity) as sum')
                            ->get();


            }
            // $sumOfChira=
            return view('mineEmployeeTransaction',['worker'=>$worker,'trip'=>$trip,'product'=>$product,
            'transaction'=>$transaction,'sumOfChira'=>$sumOfChira]);
        }
        catch(Exception $e){
            print_r($e);
        }
    }
    public function getMineCustomerTransaction(Request $req,$customerid){
        $customer = DB::select('select * from mine_customer where customer_id='.$customerid);
        $trip = DB::select('select * from mine_trip where mine_customer_id='.$customerid);

        $transaction=DB::select('select * from mine_customer_transaction where mine_customer_id='.$customerid);
        if($req->isMethod('POST')){
            $fromdate=$req->input('fromdate');
            $todate=$req->input('todate');
            Session::put('fromdateMineCust',$fromdate);
            Session::put('todateMineCust',$todate);
            $trip=DB::table('mine_trip')
                  ->where('mine_customer_id','=',$customerid)
                  ->whereBetween('mine_trip_date',[$fromdate,$todate])
                  ->get();
            $sumOfProducts=DB::table('mine_trip')
                ->join('product','product.product_name','=','mine_trip.product_name')
                ->where('mine_customer_id','=',$customerid)
                ->whereBetween('mine_trip_id',[$fromdate,$todate])
                ->selectRaw('sum(product_quantity) as totalProduct ,sum(total_amount) as totalBill,product.product_name as product_name,product.product_unit as unit')
                ->groupby('product.product_name')
                ->get();

            return view('mineCustomerTransaction',['customer'=>$customer,'trip'=>$trip,'transaction'=> $transaction,
            'sumOfProducts'=>$sumOfProducts]);
        }
        else if($req->isMethod('GET') && $req->is('home/mine/mineCustomer/'.$customerid)){

            $sumOfChira=DB::table('mine_trip')
                ->where('mine_customer_id','=',$customerid)
                ->where('product_name','=','चिरा')
                ->selectRaw('sum(product_quantity) as totalProduct ,sum(total_amount) as totalBill')
                ->get();

            $sumOfProducts=DB::table('mine_trip')
                ->join('product','product.product_name','=','mine_trip.product_name')
                ->where('mine_customer_id','=',$customerid)
                ->selectRaw('sum(product_quantity) as totalProduct ,sum(total_amount) as totalBill,product.product_name as product_name,product.product_unit as unit')
                ->groupby('product.product_name')
                ->get();
            // print_r($sumOfProducts);
            return view('mineCustomerTransaction',['customer'=>$customer,'trip'=>$trip,'transaction'=> $transaction,
            'sumOfProducts'=>$sumOfProducts]);
        }
        else{
            return view('editMineCustomer',['customer'=>$customer]);
        }
    }

    public function getMineEmployee(Request $req){
        try{
            $mineWorker = DB::select('select * from mine_worker where is_deleted=0');
            return view('mineEmployee',['mineWorker'=>$mineWorker]);
        }
        catch(Exception $e){
            print_r($e);
        }
    }
    function saveProduct(Request $req){
        // adding productsavemaintanace
        try{
            $product=new product;
            $product->product_name=$req->product_name;
            $product->product_unit=$req->product_unit;
             $product->save();
            return redirect('/home/mine')->with('message',Lang::get('saved'));

        }
        catch(Exception $e){
            print_r($e);
        }

    }
    function savemaintanace(Request $req){
        // adding maintance
        try{
            $maintenance=new maintenance;
            $maintenance->maintainance_for=$req->maintainance_for;
            $maintenance->maintainance_type=$req->maintainance_type;
            $maintenance->maintainance_amount=$req->maintainance_amount;
            $maintenance->maintainance_date=$req->maintainance_date;
            $maintenance->save();
            return redirect('/home/mineMachine')->with('message',Lang::get('saved'));
        }
        catch(Exception $e){
            print_r($e);
        }

    }
    function addMinePayment(Request $req){
        // adding maintance
        try{
            $employee_manager_payment=new employee_manager_payment;
            $employee_manager_payment->reciever_name=$req->reciever_name;
            $employee_manager_payment->amount=$req->amount;
            $employee_manager_payment->note=$req->note;
            $employee_manager_payment->recived_date=$req->recived_date;
             $employee_manager_payment->save();
            return redirect('/home/mine/mineCustomer')->with('message',Lang::get('saved'));

        }
        catch(Exception $e){
            print_r($e);
        }

    }
    function addRoyalty(Request $req){
        // adding maintance
        try{
            $royalty=new royalty;
            $royalty->royalty_reciever_name=$req->royalty_reciever_name;
            $royalty->royalty_amount=$req->royalty_amount;
            $royalty->royalty_note=$req->royalty_note;
            $royalty->royalty_date=$req->royalty_date;
            $royalty->royalty_contact=$req->royalty_contact;
             $royalty->save();
            return redirect('/home/mine/mineCustomer')->with('message',Lang::get('saved'));

        }
        catch(Exception $e){
            print_r($e);
        }

    }

    function addDiesel(Request $req){
        // adding maintance
        try{
            $diesel=new diesel;
            $diesel->diesel_for=$req->diesel_for;
            $diesel->diesel_amount=$req->diesel_amount;
            $diesel->diesel_comment=$req->diesel_comment;
            $diesel->diesel_date=$req->diesel_date;
             $diesel->save();
            return redirect('/home/mineMachine')->with('message',Lang::get('saved'));

        }
        catch(Exception $e){
            print_r($e);
        }

    }

    function addMachine(Request $req){
        // adding maintance
        try{
            $machine=new machine;
            $machine->machine_name=$req->machine_name;
            $machine->machine_status=$req->machine_status;
             $machine->save();
            return redirect('/home/mineMachine')->with('message',Lang::get('saved'));
        }
        catch(Exception $e){
            print_r($e);
        }

    }
    function addMine(Request $req){
        // adding vehicle
        try{
            $mine=new mine;
            // $mine->mine_name=$req->mine_name;
            $mine->mine_owner_name=$req->mine_owner;
            $mine->mine_address=$req->mine_address;
            $mine->mine_phn_no=$req->mine_phn_no;
            $mine->mine_mail=$req->mine_mail;
             $mine->save();
            return redirect('/home/mine')->with('message',Lang::get('saved'));

        }
        catch(Exception $e){
            print_r($e);
        }
    }
    public function editMine(Request $req,$id){
            // $name=$req->input('mine_name');
            $owner=$req->input('mine_owner');
            $address=$req->input('mine_address');
            $phn=$req->input('mine_phn_no');
            $mail=$req->input('mine_mail');
            try{
                DB::update('update mine set mine_owner_name = ? ,mine_address = ? ,mine_phn_no = ?  ,mine_mail = ? where mine_id = ?',[$owner,$address,$phn,$mail,$id]);
                return redirect('home/mine')->with('message',Lang::get('updated'));
                }
            catch(Exception $e){
                print_r($e);
                 }

        }

        public function setMine(Request $req,$id){
            try{
                 $mine = DB::select('select * from mine where mine_id ='.$id);
                return view('editmine',['mine'=>$mine]);
            }
            catch(Exception $e){
                print_r($e);
            }
        }
        public function setMineWorker(Request $req,$id){
            try{
                 $worker = DB::select('select * from mine_worker where worker_id ='.$id);
                 return view('editMineWorker',['worker'=>$worker]);
            }
            catch(Exception $e){
                print_r($e);
            }
        }
        public function editMineWorker(Request $req,$id){
            $workerName=$req->workerName;
            $workerAddr=$req->workerAddr;
            $workerPhnNo=$req->workerPhnNo;
            $workerRate=$req->workerRate;
            $workerAltPhnNo=$req->workerAltPhnNo;
            try{
                    DB::update('update mine_worker set worker_name = ? ,worker_address = ? ,worker_phn_no = ?  ,worker_alt_phn_no = ? ,worker_rate = ? where worker_id = ?',[$workerName,$workerAddr,$workerPhnNo,$workerAltPhnNo,$workerRate,$id]);
                    return redirect('home/mine/employee')->with('message', Lang::get('updated'));
                }
                catch(Exception $e){
                    return redirect('home/mine/employee')->with('message', Lang::get('error'));
                }
        }
        public function deleteMineWorker(Request $req,$id){
            try{
                 DB::update('update mine_worker set is_deleted=1 where worker_id='.$id);
                 return redirect('home/mine/employee')->with('message',Lang::get('deleted'));
            }
            catch(Exception $e){
                 return redirect('home/mine/employee')->with('message',Lang::get('error'));
            }
         }



    public function editproduct(Request $req,$id){
        $name=$req->input('product_name');
        $unit=$req->input('product_unit');
        // $rate=$req->input('product_rate');

        // print_r($name);
        try{
            DB::update('update product set product_name = ? ,product_unit = ?  where product_id = ?',[$name,$unit,$id]);
            return redirect('home/mine')->with('message',Lang::get('updated'));
        }
        catch(Exception $e){
            print_r($e);
        }
    }
 public function deleteProduct(Request $req,$id){
    DB::update('update product set is_deleted=1 where product_id='.$id);
    return redirect('home/mine')->with('message',Lang::get('deleted'));
 }
 public function deleteMine(Request $req,$id){
     try{
        DB::update('update mine set is_deleted=1 where mine_id='.$id);
        return redirect('home/mine')->with('message',Lang::get('deleted'));
    }
    catch(Exception $e){
        return redirect('home/mine')->with('message',Lang::get('error'));

     }
 }

public function deleteMineCustomer(Request $req,$id){
    DB::update('update mine_customer set is_deleted=1 where customer_id='.$id);
    return redirect('home/mine/mineCustomer')->with('message',Lang::get('deleted'));
 }


public function getEditProduct(Request $req,$id){
        $mineproduct = DB::select('select * from product where product_id='.$id);
         return view('editproduct',['mineproduct'=>$mineproduct]);

    }

public function editMineCustomerTrip($tripid)
{
    return view('mineCustomerTripEdit');
}

public function editMineCustomerPayment($paymentid)
{
    return view('editMineCustomerPayment');
}

public function editMineEmployeeTrip($tripid)
{
    return view('mineEmployeeTripEdit');
}

public function editMineEmployeePayment($paymentid)
{
    return view('editMineEmployeePayment');
}
}
