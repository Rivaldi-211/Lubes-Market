<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboardController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\Buyer\ProfileController as BuyerProfileController;
use App\Http\Controllers\Receipt\ReceiptController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Seller\ProfileController as SellerProfileController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Seller\ReportController as SellerReportController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UmkmController as AdminUmkmController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\RekeningBankController as AdminRekeningBankController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;

use App\Http\Controllers\Public\KeroyokanController as PublicKeroyokanController;
use App\Http\Controllers\Admin\KeroyokanController as AdminKeroyokanController;

Route::get('/', HomeController::class)->name('home');
Route::get('/katalog',[CatalogController::class,'index'])->name('catalogue');
Route::get('/toko', [\App\Http\Controllers\Public\UmkmController::class, 'index'])->name('umkm.index');
Route::get('/toko/{umkm}', [\App\Http\Controllers\Public\UmkmController::class, 'show'])->name('umkm.show');
Route::get('/produk/{produk}',[ProductController::class,'show'])->name('products.show');
Route::get('/keroyokan', [PublicKeroyokanController::class, 'index'])->name('keroyokan.index');
Route::get('/keroyokan/{kelompokKeroyokan}', [PublicKeroyokanController::class, 'show'])->name('keroyokan.show');
Route::post('/keroyokan/{kelompokKeroyokan}/simulasi', [PublicKeroyokanController::class, 'simulate'])->name('keroyokan.simulate');
Route::get('/keranjang',[CartController::class,'index'])->name('cart.index');
Route::post('/keranjang/tambah/{produk}',[CartController::class,'add'])->name('cart.add');
Route::patch('/keranjang',[CartController::class,'update'])->name('cart.update');
Route::delete('/keranjang/{produk}',[CartController::class,'remove'])->name('cart.remove');
Route::delete('/keranjang',[CartController::class,'clear'])->name('cart.clear');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class,'create'])->name('login');
    Route::post('/login', [LoginController::class,'store'])->name('login.store');
    Route::get('/register', [RegisterController::class,'create'])->name('register');
    Route::post('/register', [RegisterController::class,'store'])->name('register.store');
    Route::get('/lupa-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/lupa-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
});
Route::post('/logout',[LoginController::class,'destroy'])->middleware('auth')->name('logout');
Route::post('/webhooks/xendit/payment', [\App\Http\Controllers\Webhook\XenditWebhookController::class, 'payment'])->name('webhooks.xendit.payment');

if (app()->environment(['local', 'testing'])) {
    Route::get('/pembayaran/qris/{reference}/demo-mobile', [\App\Http\Controllers\Payment\QrisMobileDemoController::class, 'show'])->name('payment.qris.demo_mobile');
    Route::post('/pembayaran/qris/{reference}/demo-mobile', [\App\Http\Controllers\Payment\QrisMobileDemoController::class, 'pay'])->name('payment.qris.demo_mobile_pay');
}

Route::middleware(['auth','active'])->group(function () {
    Route::get('/nota/{pesanan}', [ReceiptController::class,'show'])->name('receipt.show');

    Route::middleware('role:pembeli')->group(function(){
        Route::get('/checkout',[CheckoutController::class,'create'])->name('checkout.create');
        Route::post('/checkout',[CheckoutController::class,'store'])->name('checkout.store')->block(lockSeconds: 30, waitSeconds: 35);
        Route::get('/pembayaran/qris/{reference}', [\App\Http\Controllers\Payment\QrisPaymentController::class, 'show'])->name('payment.qris.show');
        Route::get('/pembayaran/qris/{reference}/status', [\App\Http\Controllers\Payment\QrisPaymentController::class, 'status'])->name('payment.qris.status');
        if (app()->environment(['local', 'testing'])) {
            Route::post('/pembayaran/qris/{reference}/simulate', [\App\Http\Controllers\Payment\QrisPaymentSimulationController::class, 'store'])->name('payment.qris.simulate');
            Route::get('/pembayaran/qris/{reference}/demo-mobile', [\App\Http\Controllers\Payment\QrisMobileDemoController::class, 'show'])->name('payment.qris.demo_mobile');
            Route::post('/pembayaran/qris/{reference}/demo-mobile', [\App\Http\Controllers\Payment\QrisMobileDemoController::class, 'pay'])->name('payment.qris.demo_mobile_pay');
        }
        Route::post('/keroyokan/{kelompokKeroyokan}/konfirmasi', [PublicKeroyokanController::class, 'confirm'])->name('keroyokan.confirm');
        Route::get('/pembeli', BuyerDashboardController::class)->name('buyer.dashboard');
        Route::get('/pembeli/profil', [BuyerProfileController::class, 'edit'])->name('buyer.profile.edit');
        Route::patch('/pembeli/profil', [BuyerProfileController::class, 'update'])->name('buyer.profile.update');
        Route::patch('/pembeli/profil/password', [BuyerProfileController::class, 'updatePassword'])->name('buyer.profile.password');
        Route::patch('/pembeli/pesanan/{pesanan}/batal',[BuyerOrderController::class,'cancel'])->name('buyer.orders.cancel');
        Route::post('/pembeli/pesanan/{pesanan}/bukti',[BuyerOrderController::class,'uploadProof'])->name('buyer.orders.proof');
        Route::post('/pembeli/pesanan/{pesanan}/ulasan',[BuyerOrderController::class,'review'])->name('buyer.orders.review');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function(){
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/profil', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profil', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::patch('/profil/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');
        Route::resource('umkm', AdminUmkmController::class)->except('show');
        Route::resource('produk', AdminProductController::class)->except('show')->names('products');
        Route::resource('keroyokan', AdminKeroyokanController::class)->except('show')->names('keroyokan');
        Route::resource('rekening-bank', AdminRekeningBankController::class)->except('show')->names('rekening-bank');
        Route::patch('/rekening-bank/{rekeningBank}/status', [AdminRekeningBankController::class, 'status'])->name('rekening-bank.status');
        Route::get('/pengguna', [AdminUserController::class,'index'])->name('users.index');
        Route::patch('/pengguna/{user}/status', [AdminUserController::class,'status'])->name('users.status');
        Route::get('/pesanan', [AdminOrderController::class,'index'])->name('orders.index');
        Route::patch('/pesanan/{pesanan}', [AdminOrderController::class,'update'])->name('orders.update');
        Route::get('/laporan', [AdminReportController::class,'index'])->name('reports.index');
        Route::get('/laporan/csv', [AdminReportController::class,'csv'])->name('reports.csv');
        Route::get('/analitik-umkm', [\App\Http\Controllers\Admin\UmkmAnalyticsController::class, 'index'])->name('umkm.analytics');
        Route::get('/analitik-umkm/{umkm}/rekomendasi', [\App\Http\Controllers\Admin\UmkmAnalyticsController::class, 'rekomendasiCreate'])->name('umkm.rekomendasi.create');
        Route::post('/analitik-umkm/{umkm}/rekomendasi', [\App\Http\Controllers\Admin\UmkmAnalyticsController::class, 'rekomendasiStore'])->name('umkm.rekomendasi.store');
        Route::get('/verifikasi-penjual', [\App\Http\Controllers\Admin\VerifikasiPenjualController::class, 'index'])->name('verifikasi-penjual.index');
        Route::post('/verifikasi-penjual/{umkm}/approve', [\App\Http\Controllers\Admin\VerifikasiPenjualController::class, 'approve'])->name('verifikasi-penjual.approve');
        Route::post('/verifikasi-penjual/{umkm}/reject', [\App\Http\Controllers\Admin\VerifikasiPenjualController::class, 'reject'])->name('verifikasi-penjual.reject');
        Route::get('/disbursement', [\App\Http\Controllers\Admin\DisbursementController::class, 'index'])->name('disbursement.index');
        Route::post('/disbursement/{disbursement}/setujui', [\App\Http\Controllers\Admin\DisbursementController::class, 'approve'])->name('disbursement.approve');
        Route::post('/disbursement/{disbursement}/tolak', [\App\Http\Controllers\Admin\DisbursementController::class, 'reject'])->name('disbursement.reject');
        Route::post('/disbursement/{umkm}', [\App\Http\Controllers\Admin\DisbursementController::class, 'store'])->name('disbursement.store');
        Route::resource('zona-pengiriman', \App\Http\Controllers\Admin\ZonaPengirimanController::class)->only(['index', 'update']);
        Route::get('/aktivitas', [AdminActivityLogController::class,'index'])->name('logs.index');
    });

    Route::middleware('auth')->prefix('penjual')->group(function () {
        Route::get('/onboarding', [\App\Http\Controllers\Seller\OnboardingController::class, 'create'])->name('seller.onboarding');
        Route::post('/onboarding', [\App\Http\Controllers\Seller\OnboardingController::class, 'store'])->name('seller.onboarding.store');
        Route::get('/onboarding/menunggu', fn() => view('seller.onboarding-waiting'))->name('seller.onboarding.waiting');
        Route::get('/onboarding/ditolak', fn() => view('seller.onboarding-rejected'))->name('seller.onboarding.rejected');
    });

    Route::prefix('penjual')->name('seller.')->middleware(['role:penjual', \App\Http\Middleware\EnsureSellerVerified::class])->group(function(){
        Route::get('/', SellerDashboardController::class)->name('dashboard');
        Route::get('/profil', [SellerProfileController::class,'edit'])->name('profile.edit');
        Route::patch('/profil', [SellerProfileController::class,'update'])->name('profile.update');
        Route::patch('/profil/akun', [SellerProfileController::class,'updateAccount'])->name('profile.account');
        Route::patch('/profil/password', [SellerProfileController::class,'updatePassword'])->name('profile.password');
        Route::resource('produk', SellerProductController::class)->except('show')->names('products');
        Route::get('/pesanan', [SellerOrderController::class,'index'])->name('orders.index');
        Route::get('/pesanan/notifikasi-pembayaran', [SellerOrderController::class,'paymentNotifications'])->name('orders.notifications');
        Route::patch('/pesanan/{pesanan}', [SellerOrderController::class,'update'])->name('orders.update');
        Route::patch('/pesanan/{pesanan}/pembayaran', [SellerOrderController::class,'updatePaymentStatus'])->name('orders.payment.update');
        Route::get('/saldo', [\App\Http\Controllers\Seller\SaldoController::class, 'index'])->name('saldo.index');
        Route::post('/saldo/ajukan', [\App\Http\Controllers\Seller\SaldoController::class, 'store'])->name('saldo.ajukan')->middleware('throttle:5,1');
        Route::post('/saldo/rekening', [\App\Http\Controllers\Seller\SaldoController::class, 'storeRekening'])->name('saldo.rekening.store');
        Route::get('/laporan', [SellerReportController::class,'index'])->name('reports.index');
        Route::get('/laporan/csv', [SellerReportController::class,'csv'])->name('reports.csv');
        Route::get('/analitik', [\App\Http\Controllers\Seller\AnalyticsController::class, 'index'])->name('analytics');
    });
});
