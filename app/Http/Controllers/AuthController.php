<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Jobs\SendOtp;
use App\Jobs\GasDrip;
use App\Services\EmbeddedWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use kornrunner\Keccak;  // hashing keccak256
use Elliptic\EC;        // recovery public key dari signature (ecrecover)

class AuthController extends Controller
{
    // ============================
    // REGISTER
    // ============================
    public function register()
    {
        return view('auth.index', ['mode' => 'register']);
    }

    public function registerStore(Request $request)
    {
        // Dua jalur: (1) MetaMask (wallet+signature) ATAU (2) embedded wallet (PIN 6 angka).
        $usesMetamask = $request->filled('wallet_address') && $request->filled('signature');

        $rules = [
            'name'     => 'required',
            'email'    => 'required|email|unique:users',
            'phone'    => ['required', 'string', 'regex:/^\+?[0-9]{8,20}$/'], // kode negara + nomor (mis. +62812xxxx)
            'password' => 'required|min:8|confirmed',
            'pin'      => 'required|digits:6|confirmed', // PIN WAJIB semua akun (login & bayar)
        ];
        if ($usesMetamask) {
            $rules['wallet_address'] = 'required|string|regex:/^0x[a-fA-F0-9]{40}$/|unique:users';
            $rules['signature']      = 'required|string';
            $rules['sig_timestamp']  = 'required|numeric';
        }

        $request->validate($rules, [
            'phone.regex'          => 'Nomor telepon tidak valid (gunakan kode negara + nomor, mis. +62812xxxxxxx).',
            'email.unique'         => 'Email ini sudah terdaftar.',
            'password.min'         => 'Password minimal 8 karakter.',
            'password.confirmed'   => 'Konfirmasi password tidak cocok.',
            'pin.digits'           => 'PIN harus 6 angka.',
            'pin.confirmed'        => 'Konfirmasi PIN tidak cocok.',
        ]);

        // PIN hash disimpan untuk SEMUA akun (login + konfirmasi bayar QRIS/aksi).
        $extra = ['pin_hash' => Hash::make($request->pin)];
        if ($usesMetamask) {
            // BUKTI KEPEMILIKAN WALLET: signature harus cocok dg wallet_address.
            // (MetaMask: PIN hanya untuk login/konfirmasi app; penandatanganan on-chain tetap via MetaMask.)
            $wallet = strtolower($request->wallet_address);
            $ts = (int) $request->sig_timestamp;
            if (abs(time() - $ts) > 600) {
                return back()->withErrors(['wallet_address' => 'Tanda tangan wallet kadaluarsa. Klik Connect Wallet lagi.'])->withInput();
            }
            $recovered = $this->recoverSigner("E-Trace register\nWallet: {$wallet}\nWaktu: {$ts}", $request->signature);
            if (!$recovered || strtolower($recovered) !== $wallet) {
                return back()->withErrors(['wallet_address' => 'Bukti kepemilikan wallet tidak valid.'])->withInput();
            }
        } else {
            // Buat embedded wallet otomatis, enkripsi private key dengan PIN.
            $ew = new EmbeddedWallet();
            $w  = $ew->generate();
            $wallet = $w['address'];
            $extra  = $ew->encrypt($w['private'], $request->pin) + ['is_embedded' => true] + $extra;
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'wallet_address' => $wallet,
            'nonce' => Str::random(20),
            'password' => Hash::make($request->password),
            // email_verified_at sengaja NULL: akun aktif setelah OTP diverifikasi.
        ] + $extra);

        if (!$usesMetamask) {
            GasDrip::dispatch($user->id); // kirim sedikit tBNB (BSC Testnet) untuk gas
        }

        $this->sendOtp($user);
        session(['otp_user_id' => $user->id]);

        return redirect('/verify-otp')->with('success', 'Kode OTP dikirim ke ' . $user->email . '. Cek email kamu.');
    }

    // ============================
    // OTP EMAIL (verifikasi registrasi)
    // ============================

    /** Generate OTP 6 digit, simpan HASH-nya, kirim via queue. */
    private function sendOtp(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->forceFill([
            'otp_hash'       => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_attempts'   => 0,
            'otp_sent_at'    => now(),
        ])->save();

        SendOtp::dispatch($user->id, $code); // RabbitMQ — tidak memblok request
    }

    public function verifyOtpForm(Request $request)
    {
        $user = User::find(session('otp_user_id'));
        if (!$user) {
            return redirect('/login');
        }
        if ($user->email_verified_at) {
            session()->forget('otp_user_id');
            return redirect('/login')->with('success', 'Akun sudah terverifikasi. Silakan login.');
        }
        // Sisa cooldown "kirim ulang" (60 dtk sejak OTP terakhir dikirim) untuk countdown di UI.
        $cooldown = 0;
        if ($user->otp_sent_at) {
            $cooldown = max(0, 60 - (int) $user->otp_sent_at->diffInSeconds(now()));
        }
        return view('auth.verify-otp', ['email' => $user->email, 'cooldown' => $cooldown]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $user = User::find(session('otp_user_id'));
        if (!$user) {
            return redirect('/login')->withErrors(['code' => 'Sesi verifikasi berakhir. Silakan login/daftar lagi.']);
        }

        if (!$user->otp_hash || !$user->otp_expires_at || now()->greaterThan($user->otp_expires_at)) {
            return back()->withErrors(['code' => 'Kode kadaluarsa. Klik "Kirim ulang".']);
        }
        if ($user->otp_attempts >= 5) {
            return back()->withErrors(['code' => 'Terlalu banyak percobaan. Klik "Kirim ulang" untuk kode baru.']);
        }

        if (!Hash::check($request->code, $user->otp_hash)) {
            $user->increment('otp_attempts');
            return back()->withErrors(['code' => 'Kode salah. Sisa percobaan: ' . max(0, 5 - $user->otp_attempts) . '.']);
        }

        // Sukses: aktifkan akun, hapus OTP (sekali pakai).
        $user->forceFill([
            'email_verified_at' => now(),
            'otp_hash' => null, 'otp_expires_at' => null, 'otp_attempts' => 0,
        ])->save();

        session()->forget('otp_user_id');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/products')->with('success', 'Akun terverifikasi. Selamat datang di E-Trace!');
    }

    public function resendOtp(Request $request)
    {
        $user = User::find(session('otp_user_id'));
        if (!$user) {
            return redirect('/login');
        }
        // Cooldown 60 detik antar kirim.
        if ($user->otp_sent_at && $user->otp_sent_at->diffInSeconds(now()) < 60) {
            $wait = 60 - $user->otp_sent_at->diffInSeconds(now());
            return back()->withErrors(['code' => "Tunggu {$wait} detik sebelum kirim ulang."]);
        }
        $this->sendOtp($user);
        return back()->with('success', 'Kode OTP baru dikirim.');
    }

    // ============================
    // LUPA / RESET PASSWORD (via OTP email, memakai infra OTP yang sama)
    // ============================

    public function forgotForm()
    {
        return view('auth.forgot');
    }

    public function forgotSend(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $user = User::where('email', $data['email'])->first();
        // Kirim kode HANYA bila akun ada & sudah terverifikasi. Pesan dibuat netral
        // (tidak membocorkan apakah email terdaftar).
        if ($user && $user->email_verified_at) {
            $this->sendOtp($user);
            session(['pwreset_user_id' => $user->id]);
            return redirect('/reset-password')->with('success', 'Kode reset dikirim ke ' . $user->email . '. Cek email kamu.');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('success', 'Jika email terdaftar, kami sudah mengirim kode reset. Cek inbox kamu.');
    }

    public function resetForm()
    {
        $user = User::find(session('pwreset_user_id'));
        if (!$user) {
            return redirect('/forgot-password')->withErrors(['email' => 'Sesi reset berakhir. Minta kode lagi.']);
        }
        $cooldown = 0;
        if ($user->otp_sent_at) {
            $cooldown = max(0, 60 - (int) $user->otp_sent_at->diffInSeconds(now()));
        }
        return view('auth.reset', ['email' => $user->email, 'cooldown' => $cooldown]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'code'     => 'required|digits:6',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::find(session('pwreset_user_id'));
        if (!$user) {
            return redirect('/forgot-password')->withErrors(['email' => 'Sesi reset berakhir. Minta kode lagi.']);
        }
        if (!$user->otp_hash || !$user->otp_expires_at || now()->greaterThan($user->otp_expires_at)) {
            return back()->withErrors(['code' => 'Kode kadaluarsa. Klik "Kirim ulang".']);
        }
        if ($user->otp_attempts >= 5) {
            return back()->withErrors(['code' => 'Terlalu banyak percobaan. Klik "Kirim ulang" untuk kode baru.']);
        }
        if (!Hash::check($request->code, $user->otp_hash)) {
            $user->increment('otp_attempts');
            return back()->withErrors(['code' => 'Kode salah. Sisa percobaan: ' . max(0, 5 - $user->otp_attempts) . '.']);
        }

        // Sukses: ganti password, hapus OTP (sekali pakai), akhiri sesi reset.
        $user->forceFill([
            'password'       => Hash::make($request->password),
            'otp_hash'       => null,
            'otp_expires_at' => null,
            'otp_attempts'   => 0,
        ])->save();
        session()->forget('pwreset_user_id');

        return redirect('/login')->with('success', 'Password berhasil diubah. Silakan login dengan password baru.');
    }

    public function resendReset(Request $request)
    {
        $user = User::find(session('pwreset_user_id'));
        if (!$user) {
            return redirect('/forgot-password')->withErrors(['email' => 'Sesi reset berakhir. Minta kode lagi.']);
        }
        if ($user->otp_sent_at && $user->otp_sent_at->diffInSeconds(now()) < 60) {
            $wait = 60 - $user->otp_sent_at->diffInSeconds(now());
            return back()->withErrors(['code' => "Tunggu {$wait} detik sebelum kirim ulang."]);
        }
        $this->sendOtp($user);
        return back()->with('success', 'Kode reset baru dikirim.');
    }

    // ============================
    // LOGIN FORM NORMAL
    // ============================
    public function login(Request $request)
    {
        // Simpan tujuan kembali (mis. dari tombol "Login untuk membeli") sebagai
        // intended URL, supaya setelah login user kembali ke halaman produk tadi.
        if ($request->filled('next')) {
            $next = $this->safeInternalUrl($request->query('next'));
            if ($next) {
                $request->session()->put('url.intended', $next);
            }
        }

        return view('auth.index', ['mode' => 'login']);
    }

    public function loginStore(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        // Proteksi brute force: kunci per (email + IP) setelah 5x gagal.
        $throttleKey = 'login:' . Str::lower($data['email']) . '|' . $request->ip();
        $maxAttempts = 5;
        $lockSeconds = 300; // 5 menit

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $secs = RateLimiter::availableIn($throttleKey);
            $mins = (int) ceil($secs / 60);
            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login yang gagal. Akun/IP ini dikunci sementara. Coba lagi dalam {$mins} menit ({$secs} detik).",
            ])->withInput($request->only('email'));
        }

        // Cek kredensial TANPA langsung login (agar bisa sisipkan OTP/2FA).
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($throttleKey, $lockSeconds); // catat kegagalan (kedaluwarsa 5 menit)
            $left = RateLimiter::remaining($throttleKey, $maxAttempts);
            $suffix = $left > 0 ? " Sisa percobaan: {$left}." : ' Akun dikunci sementara.';
            return back()->withErrors(['email' => 'Email atau password salah.' . $suffix])
                ->withInput($request->only('email'));
        }

        RateLimiter::clear($throttleKey); // sukses → reset penghitung kegagalan
        return $this->finishLogin($user, $request, $request->boolean('remember'));
    }

    // ============================
    // PIN GATE — setelah login primer (password/MetaMask), minta PIN.
    // Kalau akun belum punya PIN, minta buat PIN baru dulu.
    // ============================
    public function pinChallengeForm()
    {
        $user = User::find(session('pin:user:id'));
        if (!$user) {
            return redirect('/login');
        }
        return $user->pin_hash ? view('auth.pin-challenge') : view('auth.pin-create');
    }

    /** Verifikasi PIN yang sudah ada, lalu selesaikan login. */
    public function pinChallenge(Request $request)
    {
        $request->validate(['pin' => 'required|digits:6']);
        $user = User::find(session('pin:user:id'));
        if (!$user) {
            return redirect('/login');
        }
        if (!$user->pin_hash) {
            return redirect('/pin-challenge'); // belum ada PIN → form buat PIN
        }
        if ($user->pinLocked()) {
            return back()->withErrors(['pin' => 'PIN terkunci sementara. Coba lagi nanti.']);
        }
        if (!Hash::check($request->pin, $user->pin_hash)) {
            $user->increment('pin_attempts');
            if ($user->pin_attempts >= 5) {
                $user->forceFill(['pin_locked_until' => now()->addMinutes(15), 'pin_attempts' => 0])->save();
                return back()->withErrors(['pin' => 'PIN salah 5×. Dikunci 15 menit.']);
            }
            return back()->withErrors(['pin' => 'PIN salah. Sisa percobaan: ' . max(0, 5 - $user->pin_attempts) . '.']);
        }
        $user->forceFill(['pin_attempts' => 0, 'pin_locked_until' => null])->save();
        return $this->completeLoginFromGate($user, $request);
    }

    /** Buat PIN baru (akun yang belum punya) lalu selesaikan login. */
    public function pinCreate(Request $request)
    {
        $request->validate(['pin' => 'required|digits:6|confirmed']);
        $user = User::find(session('pin:user:id'));
        if (!$user) {
            return redirect('/login');
        }
        $user->forceFill(['pin_hash' => Hash::make($request->pin)])->save();
        return $this->completeLoginFromGate($user, $request);
    }

    private function completeLoginFromGate(User $user, Request $request)
    {
        $remember = (bool) session('pin:remember', false);
        session()->forget(['pin:user:id', 'pin:remember']);
        Auth::login($user, $remember);
        $request->session()->regenerate();
        return redirect()->intended('/products');
    }

    /** Set PIN untuk user yang belum punya (mis. akun lama sebelum fitur PIN). */
    public function setupPin(Request $request)
    {
        $data = $request->validate([
            'pin'      => 'required|digits:6|confirmed',
            'password' => 'required',
        ]);
        $user = auth()->user();
        if (!\Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Password salah.'], 422);
        }
        $user->forceFill(['pin_hash' => \Illuminate\Support\Facades\Hash::make($data['pin'])])->save();
        return response()->json(['success' => true]);
    }

    /** Cabang setelah kredensial benar: OTP verifikasi → 2FA → login normal. */
    private function finishLogin(User $user, Request $request, bool $remember = false)
    {
        if (is_null($user->email_verified_at)) {
            $this->sendOtp($user);
            session(['otp_user_id' => $user->id]);
            return redirect('/verify-otp')->with('success', 'Akun belum terverifikasi. Kode OTP baru dikirim ke email kamu.');
        }
        if ($user->hasTwoFactor()) {
            session(['2fa:user:id' => $user->id]);
            return redirect('/two-factor-challenge');
        }
        // Langkah terakhir: PIN (masukkan PIN, atau buat PIN baru bila belum ada).
        session(['pin:user:id' => $user->id, 'pin:remember' => $remember]);
        return redirect('/pin-challenge');
    }

    // Validasi URL "next" agar hanya path internal (cegah open redirect).
    private function safeInternalUrl(?string $next): ?string
    {
        if (!is_string($next) || $next === '') {
            return null;
        }
        // Path relatif internal, mis. "/products/5"
        if (str_starts_with($next, '/') && !str_starts_with($next, '//')) {
            return url($next);
        }
        // URL absolut yang masih di host aplikasi
        if (str_starts_with($next, url('/'))) {
            return $next;
        }
        return null;
    }

    // ============================
    // API GET NONCE (untuk login metamask)
    // ============================
    public function getNonce(Request $request)
    {
        $wallet = strtolower($request->wallet);

        $user = User::where('wallet_address', $wallet)->first();

        if (!$user) {
            return response()->json(['nonce' => null]);
        }

        // Rotasi nonce tiap kali diminta + masa berlaku 5 menit (cegah replay).
        $user->nonce = Str::random(24);
        $user->nonce_expires_at = now()->addMinutes(5);
        $user->save();

        return response()->json(['nonce' => $user->nonce]);
    }

    // ============================
    // LOGIN DENGAN WALLET
    // ============================
    public function loginWithWallet(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required',
            'signature' => 'required',
        ]);

        $wallet = strtolower($request->wallet_address);

        $user = User::where('wallet_address', $wallet)->first();

        if (!$user) {
            return response()->json(['error' => 'Wallet not registered'], 400);
        }

        // Nonce wajib masih berlaku (cegah replay signature lama).
        if (!$user->nonce_expires_at || now()->greaterThan($user->nonce_expires_at)) {
            return response()->json(['error' => 'Nonce kadaluarsa, muat ulang halaman & coba lagi'], 401);
        }

        // Pesan yang ditandatangani frontend (MetaMask personal_sign menambah prefix EIP-191).
        $message   = "Login with wallet\nNonce: {$user->nonce}";
        $recovered = $this->recoverSigner($message, $request->signature);

        if (!$recovered) {
            return response()->json(['error' => 'Format/verifikasi signature gagal'], 400);
        }
        if (strtolower($recovered) !== strtolower($wallet)) {
            return response()->json(['error' => 'Signature tidak cocok dengan wallet'], 401);
        }

        // Nonce sekali pakai: rotasi + hapus masa berlaku.
        $user->nonce = Str::random(24);
        $user->nonce_expires_at = null;
        $user->save();

        // 2FA aktif → jangan langsung login; arahkan ke tantangan TOTP.
        if ($user->hasTwoFactor()) {
            session(['2fa:user:id' => $user->id]);
            return response()->json(['success' => true, 'redirect' => url('/two-factor-challenge')]);
        }

        // Langkah terakhir: PIN (masukkan PIN, atau buat PIN baru bila belum ada).
        session(['pin:user:id' => $user->id]);
        return response()->json(['success' => true, 'redirect' => url('/pin-challenge')]);
    }

    // ecrecover: kembalikan alamat penanda tangan dari pesan personal_sign, atau null.
    private function recoverSigner(string $message, string $signature): ?string
    {
        if (!is_string($signature) || strlen($signature) !== 132 || substr($signature, 0, 2) !== '0x') {
            return null;
        }
        $hash = Keccak::hash("\x19Ethereum Signed Message:\n" . strlen($message) . $message, 256);
        $sig  = substr($signature, 2);
        $r    = substr($sig, 0, 64);
        $s    = substr($sig, 64, 64);
        $v    = hexdec(substr($sig, 128, 2));
        $recid = $v >= 27 ? $v - 27 : $v;
        if ($recid !== 0 && $recid !== 1) {
            return null;
        }
        try {
            $ec     = new EC('secp256k1');
            $pubKey = $ec->recoverPubKey($hash, ['r' => $r, 's' => $s], $recid);
            $pubHex = $pubKey->encode('hex');
            return '0x' . substr(Keccak::hash(substr(hex2bin($pubHex), 1), 256), 24);
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ============================
    // LOGOUT
    // ============================
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
