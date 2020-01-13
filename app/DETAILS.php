<?php

namespace App;

use Illuminate\Database\Eloquent\Model,Auth;

class DETAILS extends Model
{
		protected $table="details";
    	protected $primaryKey="DID";
    	protected $fillable = [
            'id',
            'IID',
            'DcNo',
            'DCID',
            'DDID',
            'ITID',
            'HSN',
            'IName',
            'Rate',
            'Box',
            'Qty',
            'UOM',
            'GST',
            'RCGST',
            'CGST',
            'RSGST',
            'SGST',
            'RIGST',
            'IGST',
            'Amount',
            'Total',
		];


        public function save(array $options = array())
        {
            if(!$this->id)
            {
                $this->id = Auth::user()->id;
            }
            parent::save($options);
        }
}