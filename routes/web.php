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
use App\Http\Controllers\CommunityWalletController;
use App\Http\Controllers\FriendController;

// GANTI BAHASA (id/en) — simpan di session, lalu kembali.
Route::get('/lang/{locale}', [\App\Http\Controllers\LocaleController::class, 'switch'])
    ->where('locale', 'id|en')->name('lang.switch');

// HOME / LANDING PAGE
// Belum login -> landing page publik. Sudah login -> ke katalog produk.
Route::get('/', fn() => auth()->check() ? redirect('/products') : redirect()->route('welcome'))->name('home');
Route::get('/welcome', fn() => auth()->check() ? redirect('/products') : view('welcome'))->name('welcome');

// REGISTER + LOGIN (endpoint sensitif -> rate limit cegah brute force/abuse)
Route::get('/register', [AuthController::class, 'register']);
Route::post('/register', [AuthController::class, 'registerStore'])->middleware('throttle:6,1');

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'loginStore'])->middleware('throttle:8,1');

// LOGIN METAMASK
Route::get('/api/get-nonce', [AuthController::class, 'getNonce'])->middleware('throttle:20,1');
Route::post('/login-wallet', [AuthController::class, 'loginWithWallet'])->middleware('throttle:12,1');

// PIN GATE (setelah password/MetaMask; berbasis sesi, sebelum login penuh)
Route::get('/pin-challenge', [AuthController::class, 'pinChallengeForm']);
Route::post('/pin-challenge', [AuthController::class, 'pinChallenge'])->middleware('throttle:10,1');
Route::post('/pin-create', [AuthController::class, 'pinCreate'])->middleware('throttle:10,1');

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

// CHATBOT AI (PUBLIK, rate-limited). API key OpenAI di backend (.env).
Route::post('/chatbot', [\App\Http\Controllers\ChatbotController::class, 'chat'])->middleware('throttle:15,1');

// PENCARIAN (produk, toko, orang) — publik
Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');

// EXPLORER TRANSPARANSI (PUBLIK)
Route::get('/explorer', [ExplorerController::class, 'index'])->name('explorer.index');
Route::get('/explorer/{address}', [ExplorerController::class, 'show'])->where('address', '0x[a-fA-F0-9]{40}')->name('explorer.show');

// DONASI berbasis campaign. Daftar & detail = publik; buat campaign & salurkan = pengawas.
Route::get('/donate', [DonationController::class, 'index']);
Route::get('/donate/create', [DonationController::class, 'create'])->middleware('auth');
Route::post('/donate/campaigns', [DonationController::class, 'store'])->middleware('auth');
Route::get('/donate/{slug}/edit', [DonationController::class, 'edit'])->where('slug', '[a-z0-9\-]+')->middleware('auth');
Route::post('/donate/{slug}/update', [DonationController::class, 'update'])->where('slug', '[a-z0-9\-]+')->middleware('auth');
Route::post('/donate/{slug}/delete', [DonationController::class, 'destroy'])->where('slug', '[a-z0-9\-]+')->middleware('auth');
Route::get('/donate/{slug}', [DonationController::class, 'show'])->where('slug', '[a-z0-9\-]+');
Route::post('/donation/donate', [DonationController::class, 'donate'])->middleware('throttle:20,1');
Route::post('/donation/disburse', [DonationController::class, 'disburse'])->middleware('auth');

// BAYAR PERMINTAAN UANG (publik — siapa saja bisa membayar via link/QR).
Route::get('/pay/{code}', [WalletController::class, 'pay'])->where('code', '[A-Za-z0-9]+');

// TOKO (profil publik + produk yang dijual)
Route::get('/store/{slug}', [StoreController::class, 'show'])->where('slug', '[a-z0-9\-]+');

// PRODUCTS
// Katalog & detail produk PUBLIK (bisa dilihat tanpa login).
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id')->name('products.show');
// Listing produk oleh admin tetap WAJIB login.
Route::get('/products/create', [ProductController::class, 'create'])->middleware('auth');
Route::post('/products/store', [ProductController::class, 'store'])->middleware('auth');
Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->whereNumber('id')->middleware('auth');
Route::post('/products/{id}/update', [ProductController::class, 'update'])->whereNumber('id')->middleware('auth');
Route::post('/products/{id}/delete', [ProductController::class, 'destroy'])->whereNumber('id')->middleware('auth');

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

    // SET PIN (user lama yang belum punya PIN)
    Route::post('/pin/setup', [AuthController::class, 'setupPin'])->middleware('throttle:8,1');

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
    Route::post('/wallet/lookup', [WalletController::class, 'lookup'])->middleware('throttle:30,1');
    Route::post('/wallet/requests', [WalletController::class, 'createRequest']);

    // PEMBAYARAN PAKAI PIN (embedded wallet) — tanda tangan tx di backend
    Route::post('/pin/transfer', [\App\Http\Controllers\PinTxController::class, 'transfer'])->middleware('throttle:15,1');
    Route::post('/pin/donate',   [\App\Http\Controllers\PinTxController::class, 'donate'])->middleware('throttle:15,1');
    Route::post('/pin/checkout', [\App\Http\Controllers\PinTxController::class, 'checkout'])->middleware('throttle:15,1');
    Route::post('/pin/community',[\App\Http\Controllers\PinTxController::class, 'community'])->middleware('throttle:15,1');

    // BAYAR QRIS pakai stablecoin — PROTOTIPE (receipt simulasi, tanpa settlement nyata)
    Route::post('/qris/pay', [\App\Http\Controllers\QrisController::class, 'pay'])->middleware('throttle:15,1');

    // CHAT (penjual ↔ pembeli, termasuk tawar-menawar)
    Route::get('/chat/conversations', [\App\Http\Controllers\ChatController::class, 'conversations']);
    Route::get('/chat/thread', [\App\Http\Controllers\ChatController::class, 'thread']);
    Route::get('/chat/unread-count', [\App\Http\Controllers\ChatController::class, 'unreadCount']);
    Route::post('/chat/send', [\App\Http\Controllers\ChatController::class, 'send'])->middleware('throttle:60,1');
    Route::post('/chat/offer', [\App\Http\Controllers\ChatController::class, 'offer'])->middleware('throttle:30,1');
    Route::post('/chat/respond', [\App\Http\Controllers\ChatController::class, 'respond'])->middleware('throttle:30,1');

    // NOTIFIKASI IN-APP
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll']);
    Route::get('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->whereNumber('id');

    // TEMAN (kontak)
    Route::get('/friends', [FriendController::class, 'index']);
    Route::post('/friends', [FriendController::class, 'store']);
    Route::post('/friends/accept', [FriendController::class, 'accept']);
    Route::post('/friends/reject', [FriendController::class, 'reject']);
    Route::post('/friends/delete', [FriendController::class, 'destroy']);

    // DOMPET KOMUNITAS (dikelola app: teman + undang)
    Route::get('/community', [CommunityWalletController::class, 'index']);
    Route::get('/community/create', [CommunityWalletController::class, 'create']);
    Route::post('/community', [CommunityWalletController::class, 'store']);
    Route::get('/community/{id}', [CommunityWalletController::class, 'show'])->whereNumber('id');
    Route::post('/community/withdraw', [CommunityWalletController::class, 'withdraw'])->middleware('throttle:15,1');
    Route::post('/community/propose', [CommunityWalletController::class, 'propose'])->middleware('throttle:15,1');
    Route::post('/community/approve', [CommunityWalletController::class, 'approve'])->middleware('throttle:15,1');

    // DASHBOARD SELLER
    Route::get('/seller', [SellerController::class, 'dashboard']);
    Route::get('/seller/store', [SellerController::class, 'editStore']);
    Route::post('/seller/store', [SellerController::class, 'updateStore']);
    Route::post('/seller/fulfill', [SellerController::class, 'fulfill']);

    // LAPORAN PENJUALAN (Excel/PDF, harian & bulanan) — hanya pemilik toko.
    Route::get('/seller/reports/download', [\App\Http\Controllers\SellerReportController::class, 'download'])
        ->middleware('throttle:20,1');

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
Route::get('/orders/updates', [OrderController::class, 'updates'])->middleware('auth'); // H3 polling ringan
Route::post('/order/store', [OrderController::class, 'store'])->middleware('auth');
Route::post('/order/item-status', [OrderController::class, 'updateItemStatus'])->middleware('auth');
