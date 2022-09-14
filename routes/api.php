<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('auth')->group(function(){
    //api/auth/register
    Route::post('/register', 'UserProfilePageController@register');
    Route::post('/login', 'UserProfilePageController@login');
    Route::get('/logout', 'UserProfilePageController@logout')->middleware('auth:api');
    Route::get('/user', 'UserProfilePageController@user')->middleware('auth:api');
    Route::get('authentication-failed', 'UserProfilePageController@authFailed')->name('auth-failed');

});
Route::group(['middleware' => 'auth:api'], function () {
Route::resource('category', 'CategoryController');
Route::resource('product', 'ProductController');
Route::get('Orders', 'UserOrderPageController@apiUserOrders');
Route::get('Orders/{o}', 'UserOrderPageController@apiOrderDetails');
Route::get('dproduct', 'ProductController@apiDesacount');
Route::get('tproduct', 'ProductController@apitred');
Route::get('newArr', 'ProductController@newArr');
Route::get('tageproduct/{t}', 'ProductController@gettageproduct');//filter
Route::get('brandproduct/{t}', 'ProductController@getBrandproduct');//filter
Route::get('getctgproduct/{t}', 'ProductController@getctgproduct');//filter
Route::get('getsonctg/{t}', 'CategoryController@sonctg');//filter
Route::get('tages/{id}', 'ProductTageController@show');
});

