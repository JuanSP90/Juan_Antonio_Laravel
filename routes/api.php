<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Controllers\PayController;

Route::post('/pay/easy-money', [PayController::class, 'payEasyMoney']); 
Route::post('/pay/super-walletz', [PayController::class, 'paySuperWalletz']);
Route::post('/webhook/super-walletz', [PayController::class, 'handleSuperWalletzWebhook']); 