<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\CUSTOMER,Auth,App\INVOICE,App\DETAILS,DB,App\PAYMENT,App\SETTINGS,App\DC,Mail;
use Carbon\Carbon;

class InvoiceController extends Controller
{
	public function index(Request $request)
	{
		$data=INVOICE::leftJoin('customers', 'customers.CID', '=', 'invoice.CID')
		->where('invoice.id',Auth::id())
		->select('IID','InvNo','Date','Due','invoice.CID','Total','Balance','Status','CName','Mobile1','invoice.created_at',
			DB::raw('DATEDIFF(CURDATE(),DATE(from_unixtime(Due))) AS Diff'));

		$filters=$request->filter['filters'][0];
		
		if(isset($filters['Type'])&&$filters['Type']!='')
		{
			$data->where('Status','!=','Regen')->where('Status','!=','Cancelled');
		}

		if(isset($filters['InvNo'])&&$filters['InvNo']!='')
		{
			$data->where('InvNo','LIKE','%'.$filters['InvNo'].'%');
		}
		if(isset($filters['CName'])&&$filters['CName']!='')
		{
			$data->where('CName','LIKE','%'.$filters['CName'].'%');
		}
		if(isset($filters['Status'])&&$filters['Status']!='')
		{
			if($filters['Status']=='Payable')
			{
				$data->where(DB::raw('DATEDIFF(CURDATE(),DATE(from_unixtime(Due)))'),'<',1);
			}
			if($filters['Status']=='Overdue')
			{
				$data->where(DB::raw('DATEDIFF(CURDATE(),DATE(from_unixtime(Due)))'),'>',0)->where('Status','!=','Closed');
			}
			else
			{
				$data->where('Status','=',$filters['Status']);
			}
			
		}
		if(isset($filters['FromDate'])&&$filters['FromDate']!='NaN'&&isset($filters['ToDate'])&&$filters['ToDate']!='NaN')
        {
        	$data->whereBetween('Date',[$filters['FromDate'],$filters['ToDate']]);
        }

        $total=count($data->get());
        $data->take($request->take)->skip($request->skip);
        if($request->sort)
        {
        	$data->orderBy($request->sort[0]['field'],$request->sort[0]['dir']);	
        }
        $data=$data->get();
		return response (['data'=>$data,'total'=>$total]);
	}

	public function store(Request $request)
	{
		$user=Auth::user();
		$input=$request->invoice;
		$det=$request->details;
		$input=$request->invoice;
		$input['Status']='Payable';
		if(!isset($input['RID']))
		{
			$date = Carbon::createFromTimestamp($input['Date']);
			$d_check = clone($date);
			$from = clone($date);
			$to = clone($date);

			if($d_check->format('m')<=3)
			{
				$from = '01-04-'.$from->addYear(-1)->format('Y');
				$to = '01-04-'.$to->format('Y');
			}
			else{
				$from = '01-04-'.$from->format('Y');
				$to = '01-04-'.$to->addYear(1)->format('Y');
			}

			$inv=INVOICE::whereBetween(DB::raw("DATE(from_unixtime(Date))"),[Carbon::parse($from), Carbon::parse($to)])->where('id',Auth::id())->orderBy('InvNo','DESC')->first();
			if(isset($inv))
			{ $input['InvNo']=$inv->InvNo+1; }
			else
			{ $input['InvNo']=1; }
		}
		else
		{
			$inv2=INVOICE::find($input['RID']);
			if($inv2->Status=='Cancelled')
			{
				$inv2->update(['Status'=>'Regen']);
				$input['InvNo']=$inv2->InvNo;
			}
			else
			{
				return response('Cant Generate ',422);
			}
		}
		$data=INVOICE::create($input);
		$details=$request->details;
		foreach ($details as $detail) {
			$detail['IID']=$data->IID;
			DETAILS::create($detail);
		}
		return response($data);
	}

	public function paid($id)
	{
		$data=INVOICE::find($id);
		$input['Status']="Closed";
		$data->update($input);

	}

	public function status(Request $request)
	{
		$pay=PAYMENT::where('IID',$request->IID)->first();
		if(isset($pay))
		{
			return response('There is Active Payments. Cant '.$request->Status,422);
		}
		$input=$request->all();
		$data=INVOICE::find($request->IID);
		if($data->Status!='Closed'&&$data->Status!='Regen')
		{
			if($data->Status=="Cancelled")
			{
				$data->update(['Status'=>'Payable']);
			}
			else
			{
				$data->update(['Status'=>'Cancelled']);
			}
		}
		else
		{
			return response('Cant Cancel ',422);
		}
		return response($data);
	}

	public function edit($id)
	{
		$uid=Auth::user();
		$data=INVOICE::leftJoin('customers', 'customers.CID', '=', 'invoice.CID')
		->with('details')->find($id);
		return response($data);
	}

	public function update(Request $request,$id)
	{
		$data=INVOICE::find($id);
		$user=Auth::user();
		$input=$request->invoice;
		$data=INVOICE::find($id);
		$data->update($input);
		DETAILS::where('IID',$id)->delete();
		$details=$request->details;
		foreach ($details as $detail) {
			$detail['IID']=$data->IID;
			DETAILS::create($detail);
		}

		return response($data);
	}

	public function binvpay(Request $request)
	{

		$data1=INVOICE::leftjoin('customers','customers.CID','=','invoice.CID')
		->select('invoice.CID','CName','Name','invoice.id','Mobile1',DB::raw('SUM(Total) As Inv'))
		->whereNotIn('Status',['Cancelled','Regen'])
		->where('invoice.id',Auth::id())
		->groupBy('invoice.CID')
		->orderBy('invoice.CID','asc')
		->get();

		$data2=BPAYMENT::leftjoin('customers','customers.CID','=','bpayment.CID')
		->select('bpayment.CID','CName','bpayment.id','Mobile1',DB::raw('SUM(Amount) As Pay'))
		->where('bpayment.id',Auth::id())
		->groupBy('bpayment.CID')
		->orderBy('bpayment.CID','asc')
		->get();

		return response (['data1'=>$data1,'data2'=>$data2]);
	}

	public function searchbinvpay(Request $request)
	{

		$data1=INVOICE::leftjoin('customers','customers.CID','=','invoice.CID')
		->select('invoice.CID','CName','Name','invoice.id','Mobile1',DB::raw('SUM(Total) As Inv'))
		->where('CName',$request->CName)
		->whereNotIn('Status',['Cancelled','Regen'])
		->where('invoice.id',Auth::id())
		->groupBy('invoice.CID')
		->orderBy('invoice.CID','asc')
		->get();

		$data2=BPAYMENT::leftjoin('customers','customers.CID','=','bpayment.CID')
		->select('bpayment.CID','CName','bpayment.id','Mobile1',DB::raw('SUM(Amount) As Pay'))
		->where('CName',$request->CName)
		->where('bpayment.id',Auth::id())
		->groupBy('bpayment.CID')
		->orderBy('bpayment.CID','asc')
		->get();

		return response (['data1'=>$data1,'data2'=>$data2]);
	}

	public function destroy($id)
	{
		$data=INVOICE::find($id);
		$lead=PAYMENT::where('IID',$id)->first();
		if(isset($lead))
		{
			return response(['error'],422);
		}
		$data->delete();
	}

	public function auditor(Request $request)
	{
		$total=INVOICE::selectRaw('SUM(Amount) As Amount,SUM(CGST) As CGST,SUM(SGST) As SGST,SUM(IGST) As IGST,SUM(Total) As Total')
		->whereBetween('Date',[$request->FromDate,$request->ToDate])
		->whereNotIn('Status',['Cancelled','Regen'])
		->where('invoice.id',Auth::id())
		->first();

		$report=INVOICE::leftJoin('customers', 'customers.CID', '=', 'invoice.CID')->select('CName','TIN','Date','InvNo','Amount','CGST','SGST','IGST','Total')
		->whereBetween('Date',[$request->FromDate,$request->ToDate])
		->whereNotIn('Status',['Cancelled','Regen'])
		->where('invoice.id',Auth::id())
		->orderBy('InvNo','asc')
		->get();

		
		$data['Report']=$report;
		$data['From']=$request->FromDate;
		$data['To']=$request->ToDate;
		$data['Amount']=$total->Amount;
		$data['CGST']=$total->CGST;
		$data['SGST']=$total->SGST;
		$data['IGST']=$total->IGST;
		$data['Total']=$total->Total;
		return response($data);
	}

	public function sendinvoice(Request $request)
	{
		$user=Auth::user();

		$data = base64_decode(preg_replace('#^data:application/\w+;base64,#i', '', $request->Pdf));

		file_put_contents('Invoice.pdf', $data);

		$from=$user->email;
		$to=$request->Email;
		$subject=$request->Subject;

		config(['mail.driver' => $user->Driver,
				'mail.host' => $user->Host,
				'mail.port' => $user->Port,
				'mail.encryption' => $user->Encryption,
				'mail.username' => $user->email,
				'mail.password' => $user->MPassword]);


		Mail::queue('layout.template',['data'=>$request->Body],function($message) use ($from,$to,$subject)
         {
            $message->from($from)->to($to)->subject($subject)
            ->attach('Invoice.pdf');
		            
        });

        unlink('Invoice.pdf');

	}


}