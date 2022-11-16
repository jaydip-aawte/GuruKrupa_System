<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\bizman;
use App\vehicle;
use App\Trip;
use App\Transaction;
use DB;
use POST;
class LiveSearch extends Controller
{
    function index()
    {
     return view('live_search');
    }

    function action(Request $request)
    {
     if($request->ajax())
     {
      $output = '';
      $query = $request->get('query');
      if($query != '')
      {
       $data = DB::table('merchant')
         ->where('merchant_name', 'like', '%'.$query.'%')
         ->orWhere('bizman_address', 'like', '%'.$query.'%')
          ->orWhere('bizman_phn_no', 'like', '%'.$query.'%')
          ->orWhere('created_on', 'like', '%'.$query.'%')
         ->orderBy('bizman_id', 'desc')
         ->get();
      }
      else
      {
       $data = DB::table('merchant')
         ->orderBy('bizman_id', 'desc')
         ->get();
      }
      $total_row = $data->count();
      if($total_row > 0)
      {
       foreach($data as $row)
       {
        $output .= '
        <tr>
        <td>
        <div class="td-content customer-name mt-1">'.$row->bizman_id.'</div>
    </td>
    <td>
    <div class="td-content customer-name mt-1"><a href="{{ url('.'\'home/merchant/edit/\''.$row->bizman_id.') }}"
            class="btn btn-danger btn-sm text-white">'.$row->merchant_name.' <i
                class="fa fa-pencil" aria-hidden="true"></i></a>
    </div>
</td>
         <td>'.$row->merchant_name.'</td>
         <td>'.$row->bizman_address.'</td>
         <td>'.$row->bizman_phn_no.'</td>
         <td>'.$row->bizman_id.'</td>
         <td>'.$row->created_on.'</td>
         </tr>
        ';
       }
      }
      else
      {
       $output = '
       <tr>
        <td align="center" colspan="5">No Data Found</td>
       </tr>
       ';
      }
      $data = array(
       'table_data'  => $output,
       'total_data'  => $total_row
      );

      echo json_encode($data);
     }
    }
}

