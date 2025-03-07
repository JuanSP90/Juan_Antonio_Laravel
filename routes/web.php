<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web'])->group(function () {
Route::post('/pay/easy-money', [PayController::class, 'payEasyMoney']); 
Route::post('/pay/super-walletz', [PayController::class, 'paySuperWalletz']);
Route::post('/webhook/super-walletz', [PayController::class, 'handleSuperWalletzWebhook']); 
});