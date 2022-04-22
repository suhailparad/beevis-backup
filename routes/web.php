<?php

use App\Http\Controllers\MigrationController;
use App\Http\Controllers\v1\MigrateController;
use App\Http\Controllers\v1\UserController;
use App\Http\Controllers\v1\WalletController;
use App\Services\DataFetcher;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => false,
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function(){

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/users', function () {
        return Inertia::render('User');
    })->name('users');

    Route::get('/wallets', function () {
        return Inertia::render('Wallet');
    })->name('wallets');

    Route::post('/migrate',[MigrateController::class,'migrate'])->name('dashboard.migrate');
    Route::post('/migrate/users',[UserController::class,'migrateUser'])->name('migrate.users');
    Route::post('/migrate/wallets',[WalletController::class,'migrateWallet'])->name('migrate.wallets');

});


require __DIR__.'/auth.php';


Route::get('/tester',function(){
    return (new DataFetcher())->trimMobile('+919876543210');
});
