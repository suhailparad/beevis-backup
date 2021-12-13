<?php

use App\Http\Controllers\MigrationController;
use Illuminate\Support\Facades\Route;

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

Route::get('/migrate/user', [MigrationController::class,'migrateUser']);
Route::get('/migrate/order', [MigrationController::class,'migrateOrder']);

Route::get('/migrate-tax-rates', [MigrationController::class,'migrateTaxRate']);
