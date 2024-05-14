<?php

/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | This file is where you may define all of the routes that are handled
  | by your module. Just tell Laravel the URIs it should respond
  | to using a Closure or controller method. Build something great!
  |
 */

///
Route::prefix('module/payment-method-pix')->group(function () {
    Route::post('/authenticate', 'PixPaymentController@authenticate');
    Route::post('/generate-qr', 'PixPaymentController@generatePixQrCode');
    Route::post('/webhook/pix', 'PixPaymentController@handlePixNotification');
});

// Route::group(['prefix' => 'module/payment-method-bsc'], function () {
//     Route::post('/generatePaymentLink', 'CoinController@generatePaymentLink');
//     Route::post('/processNotifications51e627e88c7ebd4e954aefe9_1', 'CoinController@processNotifications');
//     Route::get('/processNotifications51e627e88c7ebd4e954aefe9_1', 'CoinController@processNotifications');
//     Route::get('/config','CoinController@config');
//     Route::get('/pay/{id}', function($id) {
//         return redirect("bo/module/payment-method-bsc/pay/" . $id);
//     });
// });
#module/payment-method-bsc/processNotifications51e627e88c7ebd4e954aefe9_1



// Route::group(['prefix' => 'bo/module/payment-method-bsc'], function () {
//     Route::get('/pay/{id}', 'CoinController@pay');
// });
