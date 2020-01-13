<?php

namespace App;

use Illuminate\Database\Eloquent\Model,Auth;

class SUPPLIER extends Model
{
		protected $table="supplier";
    	protected $primaryKey="SID";
    	protected $fillable = [
    		'Name',
            'CName',
            'TIN',
            'Mobile1',
            'Mobile2',
            'Email',
            'Address',
            'State',
            'Opening',
		];
        protected $hidden = [
        'comid','created_at', 'updated_at'
        ];

        public function invoice()
        {
            return $this->hasMany('App\PURCHASE', 'SID','SID');
        }
}