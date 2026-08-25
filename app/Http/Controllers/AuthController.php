<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $request->validate([
            'name'     => 'required',
            'email'    => 'required|email|unique:users',
            'phone'    => 'required|string|regex:/^[0-9]+$/|min:8|max:15',
            'wallet_address' => 'required|string|regex:/^0x[a-fA-F0-9]{40}$/|unique:users',
            'password' => 'required|min:8|confirmed',
            'signature'     => 'required|string',
            'sig_timestamp' => 'required|numeric',
        ], [
            'phone.regex'          => 'Nomor telepon hanya boleh berisi angka.',
            'phone.max'            => 'Nomor telepon maksimal 15 digit.',
            'phone.min'            => 'Nomor telepon minimal 8 digit.',
            'wallet_address.regex' => 'Alamat wallet tidak valid (harus 0x + 40 karakter hex).',
            'wallet_address.unique'=> 'Alamat wallet ini sudah terdaftar.',
            'email.unique'         => 'Email ini sudah terdaftar.',
            'password.min'         => 'Password minimal 8 karakter.',
            'password.confirmed'   => 'Konfirmasi password tidak cocok.',
            'signature.required'   => 'Hubungkan & tanda tangani wallet dulu (klik Connect Wallet).',
        ]);

        // BUKTI KEPEMILIKAN WALLET: signature harus cocok dg wallet_address.
        $wallet = strtolower($request->wallet_address);
        $ts = (int) $request->sig_timestamp;
        if (abs(time() - $ts) > 600) { // maks 10 menit
            return back()->withErrors([
                'wallet_address' => 'Tanda tangan wallet kadaluarsa. Klik Connect Wallet lagi.',
            ])->withInput();
        }
        $message = "MyCryptoShop register\nWallet: {$wallet}\nWaktu: {$ts}";
        $recovered = $this->recoverSigner($message, $request->signature);
        if (!$recovered || strtolower($recovered) !== $wallet) {
            return back()->withErrors([
                'wallet_address' => 'Bukti kepemilikan wallet tidak valid. Pastikan menandatangani dengan wallet yang benar.',
            ])->withInput();
        }

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'wallet_address' => strtolower($request->wallet_address),
            'nonce' => Str::random(20),
            'password' => Hash::make($request->password),
        ]);

        return redirect('/login')->with('success', 'Akun berhasil dibuat!');
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
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            // Kembali ke intended URL (mis. halaman produk), default ke katalog /products.
            return redirect()->intended('/products');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ]);
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

        Auth::login($user);
        $request->session()->regenerate();   // cegah session fixation

        // Kembali ke intended URL (mis. halaman produk) kalau ada, default katalog /products.
        $redirect = $request->session()->pull('url.intended', url('/products'));

        return response()->json(['success' => true, 'redirect' => $redirect]);
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
