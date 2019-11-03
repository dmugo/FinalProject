<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/stk', 'Site\SafMpesaController@stkPush')->name('stkshow');
Route::post('/callback', 'Site\SafMpesaController@callback')->name('callbackhow');
Route::post('/access', 'Site\SafMpesaController@getAccessToken')->name('token');
