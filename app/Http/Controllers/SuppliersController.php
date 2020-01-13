<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\SUPPLIER,Auth,App\PURCHASE,App\PAYMENT,App\User;

class SuppliersController extends Controller
{
	public function index(Request $request)
	{
		$data=SUPPLIER::select('*');

		$filter=$request->filter;
		if(isset($filter['Name'])&&$filter['Name']!='')
        {
            $data->where(function ($query) use ($filter){
			    $query->where('CName','LIKE','%'.$filter['Name'].'%')->orWhere('Mobile1','LIKE','%'.$filter['Name'].'%')->orWhere('Name','LIKE','%'.$filter['Name'].'%');
			});
        }

        $total=count($data->get());
        $data = $data->take($request->take)->skip($request->skip)
        ->orderBy(key((array)($request->orderBy)),current((array)($request->orderBy)))
        ->get();
		return response (['data'=>$data,'total'=>$total]);
	}

	public function store(Request $request)
	{
		$this->validate($request,
            ['TIN'=>'unique:supplier,TIN',
            'Mobile1'=>'required|unique:supplier,Mobile1,NULL,Mobile2',
            'Mobile2'=>'unique:supplier,Mobile1,NULL,Mobile2'],
             ['Mobile1.unique'=>'Number already Taken.','Mobile2.unique'=>'Number already Taken.']);		
		$input=$request->all();
		if(!isset($request->State)||$request->State=='')
		{
			$input['State']=32;
		}
		$data=SUPPLIER::create($input);
		if(isset($data->Opening)){
			$create['SID']=$data->SID;
			$create['Amount']=$data->Opening;
			$create['Status']='Opening';
			$purchase=PURCHASE::create($create);

		}
		return response($data);
	}

	public function show($id)
	{
		$data=SUPPLIER::select('Name','CName','SID','State')->get();
		return response($data);
	}

	public function edit($id)
	{
		$data=SUPPLIER::find($id);
		return response($data);
	}

	public function update(Request $request,$id)
	{

		$this->validate($request,
            ['TIN'=>"unique:supplier,TIN,$id,SID",
            'Mobile1'=>"required|unique:supplier,Mobile1,$id,SID",
            'Mobile2'=>"unique:supplier,Mobile2,NULL,Mobile2,$id,SID"],
             ['Mobile1.unique'=>'Number already Taken.','Mobile2.unique'=>'Number already Taken.']);

		$input=$request->all();
		if(!isset($request->State)||$request->State=='')
		{
			$input['State']=32;
		}
		$data=SUPPLIER::find($id);
		$data->update($input);

		if(isset($data->Opening)){
			$purchase=PURCHASE::where('SID',$data->SID)->first();

			if(isset($purchase)){
				$create['SID']=$data->SID;
				$create['Amount']=$data->Opening;
				$create['Status']='Opening';
				$purchase->update($create);
			}else{
				$create['SID']=$data->SID;
				$create['Amount']=$data->Opening;
				$create['Status']='Opening';
				$purchase=PURCHASE::create($create);
			}
		}
		return response($data);
	}

	public function destroy($id)
	{
		$data=SUPPLIER::find($id);
		$lead=PURCHASE::where('SID',$id)->first();
		if(isset($lead))
		{
			return response(['error'],422);
		}
		$data->delete();
	}

}