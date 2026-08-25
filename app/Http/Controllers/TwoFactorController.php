<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    private function g2fa(): Google2FA
    {
        return new Google2FA();
    }

    /** Halaman setup 2FA: tampilkan QR untuk di-scan Authenticator. */
    public function setup()
    {
        $user = auth()->user();
        if ($user->hasTwoFactor()) {
            return redirect('/profile')->with('success', '2FA sudah aktif.');
        }

        // Buat secret baru (belum dikonfirmasi) — disimpan terenkripsi.
        $secret = $this->g2fa()->generateSecretKey();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => null])->save();

        $otpauth = $this->g2fa()->getQRCodeUrl(config('app.name', 'E-Trace'), $user->email, $secret);
        $renderer = new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd());
        $qrSvg = (new Writer($renderer))->writeString($otpauth);

        return view('auth.two-factor-setup', compact('secret', 'qrSvg'));
    }

    /** Konfirmasi 2FA: verifikasi kode pertama, lalu buat recovery codes. */
    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);
        $user = auth()->user();

        if (!$user->two_factor_secret || !$this->g2fa()->verifyKey($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => 'Kode salah. Coba lagi dari aplikasi Authenticator.']);
        }

        $recovery = collect(range(1, 8))->map(fn () => Str::upper(Str::random(5)) . '-' . Str::upper(Str::random(5)))->all();
        $user->forceFill([
            'two_factor_confirmed_at'   => now(),
            'two_factor_recovery_codes' => json_encode($recovery),
        ])->save();

        return view('auth.two-factor-recovery', ['codes' => $recovery, 'fresh' => true]);
    }

    /** Matikan 2FA (butuh konfirmasi password). */
    public function disable(Request $request)
    {
        $request->validate(['password' => 'required']);
        $user = auth()->user();
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect('/profile')->with('success', '2FA dinonaktifkan.');
    }

    // ===== TANTANGAN saat login (2FA aktif) =====
    public function challenge()
    {
        if (!session('2fa:user:id')) {
            return redirect('/login');
        }
        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = User::find(session('2fa:user:id'));
        if (!$user || !$user->hasTwoFactor()) {
            return redirect('/login');
        }

        $code = trim($request->code);
        $ok = false;

        // Kode TOTP 6 digit dari Authenticator.
        if (preg_match('/^\d{6}$/', $code) && $this->g2fa()->verifyKey($user->two_factor_secret, $code)) {
            $ok = true;
        } else {
            // Atau salah satu recovery code (sekali pakai).
            $codes = json_decode($user->two_factor_recovery_codes ?: '[]', true) ?: [];
            if (in_array($code, $codes, true)) {
                $codes = array_values(array_diff($codes, [$code]));
                $user->forceFill(['two_factor_recovery_codes' => json_encode($codes)])->save();
                $ok = true;
            }
        }

        if (!$ok) {
            return back()->withErrors(['code' => 'Kode 2FA/recovery salah.']);
        }

        session()->forget('2fa:user:id');
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended('/products');
    }
}
