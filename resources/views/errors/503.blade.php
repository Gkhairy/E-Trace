@extends('errors.layout')

@section('code', '503')
@section('title', __('errors.503.title'))
@section('lead', __('errors.503.lead'))

@section('detail')
    <div class="note" role="note">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 3l7 3v5c0 4.4-3 8.3-7 10-4-1.7-7-5.6-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>
        </svg>
        <div>{{ __('errors.503.escrow') }}</div>
    </div>
@endsection

@section('actions')
    {{-- Seluruh situs sedang turun, jadi "beranda" pun sama: cukup satu tombol. --}}
    <a class="btn btn-primary" href="{{ \App\Support\ErrorPage::retryUrl() }}">{{ __('errors.retry') }}</a>
@endsection
