<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayController;

Route::get('/', function () {
    return view('welcome');
});