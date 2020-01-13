<?php

namespace App;

use Illuminate\Database\Eloquent\Model,Auth;

class PURCHASE extends Model
{
		protected $table="purchase";
    	protected $primaryKey="PUID";
    	protected $fillable = [
            'InvNo',
            'Date',
            'SID',
            'Amount',
            'Status',
		];

		public function supplier()
        {
            return $this->hasMany('App\SUPPLIER', 'SID','SID');
        }


}