<?php
/**
 * Ubah latar HITAM foto koin TLKM jadi TRANSPARAN supaya menyatu di atas
 * section putih landing page.
 *
 * Cara pakai:
 *   1) Simpan foto koin asli (yg background-nya hitam) ke:
 *        public/img/tlkm-coin-raw.png
 *   2) Jalankan:  php scripts/make-coin-transparent.php
 *   3) Hasilnya otomatis dipakai landing:  public/img/tlkm-coin.png
 *
 * Teknik: luminance keying. Piksel gelap (latar hitam + anti-alias tepi)
 * dibuat transparan; koin perak & tulisan merah (terang) tetap utuh.
 * Bagian abu-abu logo di dalam koin tidak ikut hilang karena luminansinya
 * masih di atas ambang.
 */

$root   = dirname(__DIR__);
$src    = $root . '/public/img/tlkm-coin-raw.png';
$outPng = $root . '/public/img/tlkm-coin.png';

if (!extension_loaded('gd')) {
    fwrite(STDERR, "GD extension tidak aktif di PHP ini. Aktifkan 'extension=gd' di php.ini.\n");
    exit(1);
}
if (!is_file($src)) {
    fwrite(STDERR, "Sumber tidak ditemukan: $src\n");
    fwrite(STDERR, "Simpan dulu foto koin (background hitam) ke public/img/tlkm-coin-raw.png lalu jalankan lagi.\n");
    exit(1);
}

$info = getimagesize($src);
if ($info === false) { fwrite(STDERR, "File bukan gambar valid.\n"); exit(1); }

switch ($info[2]) {
    case IMAGETYPE_PNG:  $im = imagecreatefrompng($src);  break;
    case IMAGETYPE_JPEG: $im = imagecreatefromjpeg($src); break;
    case IMAGETYPE_WEBP: $im = imagecreatefromwebp($src); break;
    default: fwrite(STDERR, "Format tak didukung (pakai PNG/JPG/WEBP).\n"); exit(1);
}
if (!$im) { fwrite(STDERR, "Gagal memuat gambar.\n"); exit(1); }

$w = imagesx($im);
$h = imagesy($im);

$out = imagecreatetruecolor($w, $h);
imagealphablending($out, false);
imagesavealpha($out, true);
imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127)); // transparan penuh

// Keying pakai kanal-maksimum (jarak dari hitam). Ini menjaga tulisan MERAH
// dan koin PERAK tetap tajam: warna terang/berjenuh -> utuh, hitam murni ->
// transparan. Di bawah LOW = transparan penuh, di atas HIGH = utuh;
// di antaranya di-feather biar tepi mulus (tanpa halo hitam).
$LOW  = 22.0;
$HIGH = 55.0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        $v = max($r, $g, $b); // seberapa jauh dari hitam

        if ($v <= $LOW) {
            continue; // biarkan transparan (latar hitam)
        }
        if ($v >= $HIGH) {
            $alpha = 0; // opaque penuh (0 = tidak transparan di GD)
        } else {
            $t = ($v - $LOW) / ($HIGH - $LOW);      // 0..1
            $alpha = (int) round((1.0 - $t) * 127); // 127 transparan .. 0 opaque
        }

        $col = imagecolorallocatealpha($out, $r, $g, $b, $alpha);
        imagesetpixel($out, $x, $y, $col);
    }
}

if (!is_dir(dirname($outPng))) { mkdir(dirname($outPng), 0775, true); }
if (!imagepng($out, $outPng)) { fwrite(STDERR, "Gagal menulis $outPng\n"); exit(1); }

imagedestroy($im);
imagedestroy($out);

echo "OK -> " . str_replace($root . DIRECTORY_SEPARATOR, '', $outPng) . " ({$w}x{$h}, latar hitam kini transparan)\n";
