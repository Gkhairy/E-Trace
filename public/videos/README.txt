Taruh file berikut di folder ini (public/videos/):

1. hero.mp4         -> video background hero (ringan, mis. 1080p/720p, <5MB, loop mulus)
2. hero-poster.jpg  -> gambar poster (frame pertama video) untuk fallback & mobile

Kalau file belum ada, halaman TETAP AMAN: hero otomatis fallback ke
background gradient terang (lihat welcome.blade.php -> .hero-fallback).
