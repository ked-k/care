<?php

use Illuminate\Support\Facades\Route;

// Reports module (UI / presentation only — wire to real data when available)
Route::view('/reports', 'reports.index');
Route::view('/reports/sales', 'reports.sales');
Route::view('/reports/inventory', 'reports.inventory');
Route::view('/reports/profit-loss', 'reports.profit-loss');
Route::view('/reports/tax', 'reports.tax');
