{{-- Favicon E-Trace (dipakai di seluruh halaman). Ganti file di public/ untuk memperbarui. --}}
{{-- nginx menyajikan ikon dengan Cache-Control immutable 7 hari: naikkan $v setiap kali file ikon diganti. --}}
@php($v = 2)
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $v }}" sizes="any">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ $v }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ $v }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ $v }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ $v }}">
