<?php

use App\Http\Controllers\MigrationController;
use App\Http\Controllers\v1\MigrateController;
use App\Http\Controllers\v1\OrderController;
use App\Http\Controllers\v1\ProductController;
use App\Http\Controllers\v1\RmaController;
use App\Http\Controllers\v1\TaxController;
use App\Http\Controllers\v1\UserController;
use App\Http\Controllers\v1\WaitinglistController;
use App\Http\Controllers\v1\WalletController;
use App\Http\Controllers\v1\WishlistController;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Services\DataFetcher;
use Carbon\Carbon;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
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
        //return view('test',['products' => Product::with('channel')->take(500)->get()]);
        return Inertia::render('Dashboard',[
            'products' => Product::with('channel')->take(500)->get()
        ]);
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

    Route::get('/rma-transaction', function () {
        return Inertia::render('Rma');
    })->name('rma.transactions');

    Route::post('/migrate',[MigrateController::class,'migrate'])->name('dashboard.migrate');
    Route::post('/migrate/users',[UserController::class,'migrateUser'])->name('migrate.users');
    Route::post('/migrate/wallets',[WalletController::class,'migrateWallet'])->name('migrate.wallets');
    Route::post('/migrate/tax/linking',[TaxController::class,'linking'])->name('migrate.tax.linking');
    Route::post('/migrate/product/linking',[ProductController::class,'linking'])->name('migrate.product.linking');
    Route::post('/migrate/product/channel-create',[ProductController::class,'createProductChannel'])->name('migrate.product.channel-create');
    Route::post('/migrate/waitinglist',[WaitinglistController::class,'migrate'])->name('migrate.waitinglist');
    Route::post('/migrate/wishlist',[WishlistController::class,'migrate'])->name('migrate.wishlist');
    Route::post('/migrate/orders',[OrderController::class,'migrate'])->name('migrate.orders');
    Route::post('/migrate/rma-transaction',[RmaController::class,'migrate'])->name('migrate.transaction.migrate');

});


require __DIR__.'/auth.php';


Route::get('/tester',function(){

    $orders = Post::where('post_type','shop_order')
            ->where('ID',837723)
            ->orderBy('post_date')
            ->with('meta')
            ->whereHas('items',function($q){
                $q->where('order_item_name','!=','Wallet Topup');
            })->with(['items'=>function($q){
                $q->with('meta');
            }])->with(['comments'=>function($q){
                $q->with('meta');
            }])->with(['child'=>function($q){
                $q->with('meta');
            }])->get();

});
