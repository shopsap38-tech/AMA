<?php

use App\Http\Controllers\BigBagController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesinfectantController;
use App\Http\Controllers\SemiFiniController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('semi-fini', SemiFiniController::class)
    ->parameters(['semi-fini' => 'semi_fini'])
    ->except(['show']);

Route::resource('desinfectant', DesinfectantController::class)
    ->except(['show']);

Route::resource('big-bag', BigBagController::class)
    ->parameters(['big-bag' => 'big_bag'])
    ->except(['show']);
