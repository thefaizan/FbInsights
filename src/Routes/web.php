<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['namespace'  => 'SmartHub\FbInsights\Controllers', 'middleware' => ['web'] ], function() {

	// Generate a login URL
	Route::get('/facebook/login', ['as'=>'facebook.login', 'uses'=>'FbInsightController@authFacebook']);

	// Endpoint that is redirected to after an authentication attempt
	Route::get('/facebook/callback', ['as'=>'facebook.storeUserFanpages', 'uses'=>'FbInsightController@storeUserFanpages']);


});