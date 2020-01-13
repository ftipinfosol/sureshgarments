<?php

namespace App;

use Illuminate\Database\Eloquent\Model,Auth;

class INVOICE extends Model
{
		protected $table="invoice";
    	protected $primaryKey="IID";
    	protected $fillable = [
            'id',
            'InvNo',
            'Date',
    		'Due',
            'CID',
            'Qty',
            'Amount',
            'CGST',
            'SGST',
            'IGST',
            'Round',
            'Total',
            'Balance',
            'Status',
            'RC',
            'PoNo',
            'PoRNo',
            'PoDate',
            'DesignNo',
            'DcNo',
            'Place',
            'DT',
            'Payment_terms',
            'VNo',
            'Ctn'
		];



        public function details()
        {
            return $this->hasMany('App\DETAILS', 'IID','IID');
        }


        public function save(array $options = array())
        {
            if(!$this->id)
            {
                $this->id = Auth::user()->id;
            }
            parent::save($options);
        }

        // public function newQuery($excludeDeleted = true) {
        //     return parent::newQuery($excludeDeleted)
        //         ->select('comid')->where('invoice.comid', Auth::user()->comid);
        // }
}