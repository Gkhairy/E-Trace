@extends('errors.layout')

@section('code', '500')
@section('title', __('errors.500.title'))
@section('lead', __('errors.500.lead'))

@section('detail')
    {{-- Di aplikasi escrow, 500 di tengah pembayaran paling berisiko membuat orang
         bayar dua kali. Pesan exception TIDAK pernah ditampilkan di 5xx. --}}
    <div class="note" role="note">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5"/><path d="M12 16.5h.01"/>
        </svg>
        <div>
            {{ __('errors.500.payment') }}
            <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:4px 16px;">
                <a href="{{ url('/orders') }}">{{ __('errors.500.orders') }}</a>
                <a href="{{ url('/explorer') }}">{{ __('errors.explorer') }}</a>
            </div>
        </div>
    </div>
@endsection

@section('actions')
    <a class="btn btn-primary" href="{{ \App\Support\ErrorPage::retryUrl() }}">{{ __('errors.retry') }}</a>
    <a class="btn btn-ghost" href="{{ url('/') }}">{{ __('errors.home') }}</a>
@endsection
