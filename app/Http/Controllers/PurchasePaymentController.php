<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\PURCHASE_PAYMENT,Auth,App\PURCHASE,DB,App\User,App\SUPPLIER;

class PurchasePaymentController extends Controller
{
	public function index(Request $request)
	{

		$data=PURCHASE_PAYMENT::leftJoin('supplier', 'supplier.SID', '=', 'purchase_payment.SID');

		$filter=$request->filter;

		if(isset($filter['Type'])&&$filter['Type']!='')
        {

            $data->where('Type',$filter['Type']);
        }

		if(isset($filter['CName'])&&$filter['CName']!='')
        {
            $data->where(function ($query) use ($filter){
			    $query->where('CName','LIKE','%'.$filter['CName'].'%')->orWhere('Mobile1','LIKE','%'.$filter['CName'].'%');
			});
        }

        if(isset($filter['FromDate'])&&$filter['FromDate']!='NaN'&&isset($filter['ToDate'])&&$filter['ToDate']!='NaN')
        {
        	$from = date('Y-m-d',$filter['FromDate']);
			$to = date('Y-m-d',$filter['ToDate']);

        	$data->whereRaw("DATE(from_unixtime(Date)) BETWEEN '$from' AND '$to'");
        }

        $total=count($data->get());
        $data = $data->select('*')->take($request->take)->skip($request->skip)
        ->orderBy(key((array)($request->orderBy)),current((array)($request->orderBy)))
        ->get();
		return response (['data'=>$data,'total'=>$total]);

	}

	public function show($id)
	{		
		$data=PURCHASE_PAYMENT::groupBy('GST')->get();
		return response($data);
	}

	public function store(Request $request)
	{
		$input=$request->all();

		$data=PURCHASE_PAYMENT::create($input);
		$data=PURCHASE_PAYMENT::leftJoin('supplier', 'supplier.SID', '=', 'purchase_payment.SID')->find($data->PID);

		return response($data);
	}

	public function update(Request $request,$id)
	{
		$data=PURCHASE_PAYMENT::find($id);
		$input=$request->all();

		$data->update($input);		
		$data=PURCHASE_PAYMENT::leftJoin('supplier', 'supplier.SID', '=', 'purchase_payment.SID')->find($data->PID);
		return response($data);
	}

	public function destroy($id)
	{
		$data=PURCHASE_PAYMENT::find($id);
		$data->delete();
	}

	public function report(Request $request)
	{
		$beg1=PURCHASE::select(DB::raw('SUM(Total) As Inv'))->where('SID',$request->SID)->whereNotIn('Status',['Cancelled','Regen'])->where('Date','<',$request->FromDate)->first();
		$beg2=PURCHASE_PAYMENT::select(DB::raw('SUM(Amount) As Pay'))->where('SID',$request->SID)->where('Date','<',$request->FromDate)->first();

		$beg3=PURCHASE::select(DB::raw('SUM(Total) As Inv'))
		->where('SID',$request->SID)
		->whereNotIn('Status',['Cancelled','Regen'])
		->whereBetween('Date',[$request->FromDate,$request->ToDate])
		->first();
		$beg4=PURCHASE_PAYMENT::select(DB::raw('SUM(Amount) As Pay'))
		->where('SID',$request->SID)
		->whereBetween('Date',[$request->FromDate,$request->ToDate])
		->first();

		$report=DB::select("SELECT * FROM (
		(SELECT purchase.Total As Inv, '' AS Rec, purchase.SID, purchase.Date, purchase.PoNo As No, purchase.Status As Status FROM po)
		    UNION ALL
		    (SELECT '' AS Inv, purchase_payment.Amount As Rec, purchase_payment.SID, purchase_payment.Date, CONCAT(purchase_payment.ChequeNo,'-',purchase_payment.Detail) As No, '' As Status FROM expense)
		) results WHERE SID = ".$request->SID." AND Status NOT IN ('Cancelled','Regen') AND Date BETWEEN ".$request->FromDate." AND ".$request->ToDate);

		
		$data=SUPPLIER::select('SID','CName')->find($request->SID);
		$data['Report']=$report;
		$data['Beginning']=$beg1->Inv-$beg2->Pay;
		$data['From']=$request->FromDate;
		$data['To']=$request->ToDate;
		$data['InvTot']=$beg3->Inv;
		$data['RecTot']=$beg4->Pay;
		$data['Closing']=$data['Beginning']+$data['InvTot']-$data['RecTot'];

		// return response($beg3);
		return response($data);
	}

}