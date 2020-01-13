<?php

namespace App;

use Illuminate\Database\Eloquent\Model,Auth;

class PURCHASE_PAYMENT extends Model
{
		protected $table="purchase_payment";
    	protected $primaryKey="PID";
    	protected $fillable = [
            'Type',
            'SID',
            'id',
            'Date',
            'Bank',
            'Cheque',
            'ChequeNo',
            'Amount',
            'Detail',
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