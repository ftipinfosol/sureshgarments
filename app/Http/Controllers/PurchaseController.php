<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\SUPPLIER,Auth,App\PURCHASE,DB;
use App\PURCHASE_PAYMENT;

class PurchaseController extends Controller
{
	public function index(Request $request)
	{
			
		$data=PURCHASE::leftJoin('supplier', 'supplier.SID', '=', 'purchase.SID')
		->select('PUID','InvNo','Date','purchase.SID','Amount','Status','CName')->where('Status','!=','Opening');

		$filter=$request->filter;
		if(isset($filter['Type'])&&$filter['Type']!='')
		{
			$data->where('Status','!=','Regen')->where('Status','!=','Cancelled');
		}

		if(isset($filter['InvNo'])&&$filter['InvNo']!='')
		{
			$data->where('InvNo','LIKE','%'.$filter['InvNo'].'%');
		}
		if(isset($filter['CName'])&&$filter['CName']!='')
		{
			$data->where('CName','LIKE','%'.$filter['CName'].'%');
		}

		if(isset($filter['FromDate'])&&$filter['FromDate']!='NaN'&&isset($filter['ToDate'])&&$filter['ToDate']!='NaN')
        {
        	$from = date('Y-m-d',$filter['FromDate']);
			$to = date('Y-m-d',$filter['ToDate']);

        	$data->whereRaw("DATE(from_unixtime(Date)) BETWEEN '$from' AND '$to'");
        }

        $total=count($data->get());
        $data = $data->take($request->take)->skip($request->skip)
        ->orderBy(key((array)($request->orderBy)),current((array)($request->orderBy)))
        ->get();
		return response (['data'=>$data,'total'=>$total]);
	}

	public function store(Request $request)
	{
		$input=$request->purchase;
		$input['Status']='Unverified';
		$data=PURCHASE::create($input);

		$data=PURCHASE::leftJoin('supplier', 'supplier.SID', '=', 'purchase.SID')->find($data->PUID);
		return response($data);
	}
	
	public function update(Request $request,$id)
	{
		$input=$request->purchase;
		$data=PURCHASE::find($id);
		$data->update($input);
		
		$data=PURCHASE::leftJoin('supplier', 'supplier.SID', '=', 'purchase.SID')->find($data->PUID);
		return response($data);
	}
	
	public function show($id)
	{
		$data=PURCHASE::leftJoin('supplier', 'supplier.SID', '=', 'purchase.SID')->orderBy('PUID','Desc')->take(50)->get();
		return response($data);
	}
	
	public function edit($id)
	{
		$data=PURCHASE::leftJoin('supplier', 'supplier.SID', '=', 'purchase.SID')
		->find($id);
		return response($data);
	}

	public function status(Request $request,$id)
	{
		$data=PURCHASE::find($id);
		$status['Status']='Verified';
		$data->update($status);
		$data=PURCHASE::leftJoin('supplier', 'supplier.SID', '=', 'purchase.SID')->find($data->PUID);
		return response($data);
		


	}

	public function destroy($id)
	{
		$data=PURCHASE::find($id);
		$data->delete();
	}

	public function purchase_report(Request $request)
	{
		$from = date('Y-m-d',$request->FromDate);
		$to = date('Y-m-d',$request->ToDate);

		$report=PURCHASE::leftJoin('supplier', 'supplier.SID', '=', 'purchase.SID')
		->select('CName','Date','InvNo','Amount')
		->whereRaw("DATE(from_unixtime(Date)) BETWEEN '$from' AND '$to'")
		->where('Status','Unverified')
		->orderBy('InvNo','asc')
		->get();

		$data['Report']=$report;
		$data['From']=$request->FromDate;
		$data['To']=$request->ToDate;
		return response($data);
	}

	public function supplier_payment(Request $request)
	{
		$from = date('Y-m-d',$request->FromDate);
		$to = date('Y-m-d',$request->ToDate);

		$purchase=PURCHASE::selectRaw('SUM(Amount) As Amount')
		->where('SID',$request->SID)
		->whereRaw("DATE(from_unixtime(Date)) BETWEEN '$from' AND '$to'")
		->first();

		$payment=PURCHASE_PAYMENT::selectRaw('SUM(Amount) As Amount')
		->where('SID',$request->SID)
		->whereRaw("DATE(from_unixtime(Date)) BETWEEN '$from' AND '$to'")
		->first();
		
		$begpur=PURCHASE::selectRaw('SUM(Amount) As Amount')
		->where('SID',$request->SID)
		->whereRaw("DATE(from_unixtime(Date)) < '$from'")
		->first();
		
		$begpay=PURCHASE_PAYMENT::selectRaw('SUM(Amount) As Amount')
		->where('SID',$request->SID)
		->whereRaw("DATE(from_unixtime(Date)) < '$from'")
		->first();


		$report=DB::select("SELECT * FROM (
		(SELECT '' As Type,purchase.SID,  purchase.Date, purchase.InvNo As No, purchase.Amount As InvAmt, '' As RecAmt FROM purchase )
		    UNION ALL
		(SELECT purchase_payment.Cheque As Type,purchase_payment.SID, purchase_payment.Date, purchase_payment.ChequeNo  As No, '' As InvAmt, purchase_payment.Amount As RecAmt FROM purchase_payment )
		) 
		results 
		WHERE SID = ".$request->SID." AND DATE(from_unixtime(Date)) BETWEEN '$from' AND '$to' ORDER BY Date ASC");
		
		$data=SUPPLIER::find($request->SID);
		$data['Report']=$report;
		$data['Beginning']=$begpur->Amount-$begpay->Amount;
		$data['From']=$request->FromDate;
		$data['To']=$request->ToDate;
		$data['Inv']=$purchase->Amount;
		$data['Rec']=$payment->Amount;
		return response($data);
	}

	public function all_payment(Request $request)
	{

		$beg1=PURCHASE::selectRaw(('SUM(Amount) As Amount'));
		$beg2=PURCHASE_PAYMENT::selectRaw(('SUM(Amount) As Paid'));

		


		if(isset($request->SID)&&$request->SID!='')
		{
			$beg1->where('SID',$request->SID);
			$beg2->where('SID',$request->SID);

			$report=DB::select("SELECT SUM(Inv) AS Inv, SUM(Paid) AS Paid, results.SID, CName FROM (
			(SELECT purchase.Amount As Inv, '' AS Paid, purchase.SID, purchase.Date FROM purchase )	
			UNION ALL
			(SELECT '' AS Inv, purchase_payment.Amount As Paid, purchase_payment.SID, purchase_payment.Date FROM purchase_payment)
			) results 
			LEFT JOIN supplier ON results.SID = supplier.SID
			WHERE supplier.SID='$request->SID'
			GROUP BY results.SID ORDER BY CName");
		}else{
			$report=DB::select("SELECT SUM(Inv) AS Inv, SUM(Paid) AS Paid, results.SID, CName FROM (
			(SELECT purchase.Amount As Inv, '' AS Paid, purchase.SID, purchase.Date FROM purchase )	
			UNION ALL
			(SELECT '' AS Inv, purchase_payment.Amount As Paid, purchase_payment.SID, purchase_payment.Date FROM purchase_payment)
			) results 
			LEFT JOIN supplier ON results.SID = supplier.SID
			GROUP BY results.SID ORDER BY CName");
		}

		$beg1=$beg1->first();
		$beg2=$beg2->first();

		$data['Report']=$report;
		$data['InvTot']=$beg1->Amount;
		$data['PaidTot']=$beg2->Paid;
		
		return response($data);
	}

	public function dash()
	{
	

		$id=Auth::id();
		$today=strtotime('12:00:00');

		$invoice=PURCHASE::leftJoin('supplier', 'supplier.SID', '=', 'purchase.SID')
		->select('PUID','InvNo','Date','purchase.SID','Amount','Status','CName')
		->orderBy('PUID','DESC')->take(5)->get();

		$invamount=PURCHASE::select(DB::raw('SUM(Amount) As Amount'))->first();

		$payment=PURCHASE_PAYMENT::leftJoin('supplier', 'supplier.SID', '=', 'purchase_payment.SID')
		->select('PID','purchase_payment.SID','CName','purchase_payment.Date','Cheque','Bank','purchase_payment.Amount')
		->orderBy('PID','DESC')->take(5)->get();

		
		$report=DB::select("SELECT SUM(Inv) As Purchase, SUM(Rec) As Payment, Date FROM (
		(SELECT SUM(purchase.Amount) As Inv, '' AS Rec, Date  FROM purchase GROUP BY YEAR(from_unixtime(Date)), MONTH(from_unixtime(Date)))
		    UNION ALL
		    (SELECT '' AS Inv, SUM(purchase_payment.Amount) As Rec, Date FROM purchase_payment GROUP BY YEAR(from_unixtime(Date)), MONTH(from_unixtime(Date)))
		) results GROUP BY YEAR(from_unixtime(Date)), MONTH(from_unixtime(Date))");

		return response (['invoice'=>$invoice,'invamount'=>$invamount,'payment'=>$payment,'report'=>$report]);

	}


}