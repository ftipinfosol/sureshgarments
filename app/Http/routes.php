<?php


Route::get('/', 'Auth\AuthController@getlogin');
Route::get('login','Auth\AuthController@getlogin');

Route::post('login','Auth\AuthController@postlogin');
Route::get('/logout', function(){
	Auth::logout();
    return redirect('/');
});


Route::group(['middleware' => 'auth'],function(){

	Route::resource('users','Auth\AuthController');
	Route::resource('items','ItemsController');
	Route::resource('hsn','HsnController');
	Route::resource('customers','CustomersController');
	Route::resource('suppliers','SuppliersController');

	Route::resource('invoice','InvoiceController');
	Route::resource('purchase','PurchaseController');
	Route::resource('purchase-payment','PurchasePaymentController');
	
	Route::get('dash','PurchaseController@dash');
	Route::resource('purchase-report','PurchaseController@purchase_report');
	Route::resource('purchase-status','PurchaseController@status');
	Route::resource('supplier-payment','PurchaseController@supplier_payment');
	Route::resource('all-payment','PurchaseController@all_payment');

	Route::group(array('prefix'=>'/app/'),function(){
	    Route::get('{template}', array( function($template)
	    {
	        $template = str_replace(".html","",$template);
	        View::addExtension('html','php');
	        return View::make($template);

	    }));
	});



});