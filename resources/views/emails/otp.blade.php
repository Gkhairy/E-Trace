<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#1e293b;">
    <div style="background:#2563eb;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
        <h2 style="margin:0;font-size:18px;">Verifikasi Email</h2>
        <p style="margin:6px 0 0;font-size:13px;opacity:.85;">E-Trace</p>
    </div>
    <div style="border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:24px;">
        <p style="margin:0 0 12px;">Halo{{ $name ? ' '.$name : '' }}, gunakan kode berikut untuk mengaktifkan akunmu:</p>
        <div style="text-align:center;margin:20px 0;">
            <span style="display:inline-block;font-size:32px;font-weight:800;letter-spacing:8px;color:#0f172a;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:12px;padding:14px 24px;">{{ $code }}</span>
        </div>
        <p style="font-size:13px;color:#64748b;margin:0;">Kode berlaku <b>10 menit</b> dan hanya bisa dipakai sekali. Jangan bagikan ke siapa pun. Kalau kamu tidak mendaftar di E-Trace, abaikan email ini.</p>
    </div>
</div>
