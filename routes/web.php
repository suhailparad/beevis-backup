<?php

use App\Http\Controllers\MigrationController;
use App\Http\Controllers\v1\MigrateController;
use App\Http\Controllers\v1\OrderController;
use App\Http\Controllers\v1\ProductController;
use App\Http\Controllers\v1\TaxController;
use App\Http\Controllers\v1\UserController;
use App\Http\Controllers\v1\WaitinglistController;
use App\Http\Controllers\v1\WalletController;
use App\Http\Controllers\v1\WishlistController;
use App\Services\DataFetcher;
use Carbon\Carbon;
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

    Route::get('/taxes', function () {
        return Inertia::render('Taxes');
    })->name('taxes');

    Route::get('/products', function () {
        return Inertia::render('Product');
    })->name('products');

    Route::get('/waitinglist', function () {
        return Inertia::render('Waitinglist');
    })->name('waitinglist');

    Route::get('/wishlist', function () {
        return Inertia::render('Wishlist');
    })->name('wishlist');

    Route::get('/orders', function () {
        return Inertia::render('Order');
    })->name('orders');

    Route::post('/migrate',[MigrateController::class,'migrate'])->name('dashboard.migrate');
    Route::post('/migrate/users',[UserController::class,'migrateUser'])->name('migrate.users');
    Route::post('/migrate/wallets',[WalletController::class,'migrateWallet'])->name('migrate.wallets');
    Route::post('/migrate/tax/linking',[TaxController::class,'linking'])->name('migrate.tax.linking');
    Route::post('/migrate/product/linking',[ProductController::class,'linking'])->name('migrate.product.linking');
    Route::post('/migrate/waitinglist',[WaitinglistController::class,'migrate'])->name('migrate.waitinglist');
    Route::post('/migrate/wishlist',[WishlistController::class,'migrate'])->name('migrate.wishlist');
    Route::post('/migrate/orders',[OrderController::class,'migrate'])->name('migrate.orders');

});


require __DIR__.'/auth.php';


Route::get('/tester',function(){
    $data = 'a:1:{s:10:"20-04-2022";a:9:{s:6:"action";s:28:"mwb_wrma_return_product_info";s:8:"products";a:1:{i:0;a:5:{s:10:"product_id";s:7:"1405040";s:12:"variation_id";s:7:"1405071";s:7:"item_id";s:6:"844102";s:5:"price";s:4:"1699";s:3:"qty";s:1:"1";}}s:6:"amount";s:4:"1699";s:7:"subject";s:10:"Size Issue";s:6:"reason";s:15:"No Reason Enter";s:7:"orderid";s:7:"1431624";s:14:"security_check";s:10:"fe126cd5b6";s:6:"status";s:8:"complete";s:12:"approve_date";s:10:"20-04-2022";}}';
    return unserialize($data);
});
