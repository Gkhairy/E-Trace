<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CryptoController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ExplorerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\StoreController;

// HOME / LANDING PAGE
// Belum login -> landing page publik. Sudah login -> ke katalog produk.
Route::get('/', fn() => auth()->check() ? redirect('/products') : view('welcome'));

// REGISTER + LOGIN (endpoint sensitif -> rate limit cegah brute force/abuse)
Route::get('/register', [AuthController::class, 'register']);
Route::post('/register', [AuthController::class, 'registerStore'])->middleware('throttle:6,1');

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'loginStore'])->middleware('throttle:8,1');

// LOGIN METAMASK
Route::get('/api/get-nonce', [AuthController::class, 'getNonce'])->middleware('throttle:20,1');
Route::post('/login-wallet', [AuthController::class, 'loginWithWallet'])->middleware('throttle:12,1');

// OTP VERIFIKASI EMAIL (berbasis sesi, sebelum login penuh)
Route::get('/verify-otp', [AuthController::class, 'verifyOtpForm']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:5,1');

// 2FA — tantangan saat login (berbasis sesi)
Route::get('/two-factor-challenge', [\App\Http\Controllers\TwoFactorController::class, 'challenge']);
Route::post('/two-factor-challenge', [\App\Http\Controllers\TwoFactorController::class, 'verify'])->middleware('throttle:10,1');

// LOGOUT
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

// TICKER HARGA CRYPTO (PUBLIK) — strip marquee di welcome & katalog.
Route::get('/api/ticker', [CryptoController::class, 'ticker']);

// EXPLORER TRANSPARANSI (PUBLIK)
Route::get('/explorer', [ExplorerController::class, 'index']);
Route::get('/explorer/{address}', [ExplorerController::class, 'show'])->where('address', '0x[a-fA-F0-9]{40}');

// DONASI berbasis campaign. Daftar & detail = publik; buat campaign & salurkan = pengawas.
Route::get('/donate', [DonationController::class, 'index']);
Route::get('/donate/create', [DonationController::class, 'create'])->middleware('auth');
Route::post('/donate/campaigns', [DonationController::class, 'store'])->middleware('auth');
Route::get('/donate/{slug}', [DonationController::class, 'show'])->where('slug', '[a-z0-9\-]+');
Route::post('/donation/donate', [DonationController::class, 'donate'])->middleware('throttle:20,1');
Route::post('/donation/disburse', [DonationController::class, 'disburse'])->middleware('auth');

// BAYAR PERMINTAAN UANG (publik — siapa saja bisa membayar via link/QR).
Route::get('/pay/{code}', [WalletController::class, 'pay'])->where('code', '[A-Za-z0-9]+');

// TOKO (profil publik + produk yang dijual)
Route::get('/store/{slug}', [StoreController::class, 'show'])->where('slug', '[a-z0-9\-]+');

// PRODUCTS
// Katalog & detail produk PUBLIK (bisa dilihat tanpa login).
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id');
// Listing produk oleh admin tetap WAJIB login.
Route::get('/products/create', [ProductController::class, 'create'])->middleware('auth');
Route::post('/products/store', [ProductController::class, 'store'])->middleware('auth');

// CART (semua butuh login)
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::post('/cart/update', [CartController::class, 'update']);
    Route::post('/cart/remove', [CartController::class, 'remove']);

    // CHECKOUT
    Route::get('/checkout', [CheckoutController::class, 'index']);

    // PROFIL PUBLIK (semua user)
    Route::get('/profile', [ProfileController::class, 'edit']);
    Route::post('/profile', [ProfileController::class, 'update']);

    // 2FA (opsional) — kelola dari profil
    Route::get('/two-factor/setup', [\App\Http\Controllers\TwoFactorController::class, 'setup']);
    Route::post('/two-factor/confirm', [\App\Http\Controllers\TwoFactorController::class, 'confirm']);
    Route::post('/two-factor/disable', [\App\Http\Controllers\TwoFactorController::class, 'disable']);

    // BUKU ALAMAT
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses/default', [AddressController::class, 'setDefault']);
    Route::post('/addresses/delete', [AddressController::class, 'destroy']);

    // DOMPET (kirim TLKM & minta uang)
    Route::get('/wallet', [WalletController::class, 'index']);
    Route::post('/wallet/send', [WalletController::class, 'send'])->middleware('throttle:20,1');
    Route::post('/wallet/requests', [WalletController::class, 'createRequest']);

    // DASHBOARD SELLER
    Route::get('/seller', [SellerController::class, 'dashboard']);
    Route::get('/seller/store', [SellerController::class, 'editStore']);
    Route::post('/seller/store', [SellerController::class, 'updateStore']);
    Route::post('/seller/fulfill', [SellerController::class, 'fulfill']);

    // ULASAN (pembeli)
    Route::post('/review', [ReviewController::class, 'store']);

    // PANEL PENGAWAS (dispute/arbiter)
    Route::get('/supervisor/disputes', [SupervisorController::class, 'disputes']);
    Route::post('/supervisor/resolve', [SupervisorController::class, 'resolve']);
    Route::get('/supervisor/labels', [SupervisorController::class, 'labels']);
    Route::post('/supervisor/labels', [SupervisorController::class, 'labelStore']);
    Route::post('/supervisor/labels/delete', [SupervisorController::class, 'labelDelete']);
});

// ORDER
Route::get('/orders', [OrderController::class, 'index'])->middleware('auth');
Route::post('/order/store', [OrderController::class, 'store'])->middleware('auth');
Route::post('/order/item-status', [OrderController::class, 'updateItemStatus'])->middleware('auth');
