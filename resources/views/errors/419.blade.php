@extends('errors.layout')

@section('code', '419')
@section('title', __('errors.419.title'))
@section('lead', __('errors.419.lead'))

@section('actions')
    {{-- Halaman asal dibuka lewat GET = token CSRF baru; mengirim ulang POST lama
         hanya akan gagal lagi. --}}
    <a class="btn btn-primary" href="{{ \App\Support\ErrorPage::backUrl() }}">{{ __('errors.419.reopen') }}</a>
    <a class="btn btn-ghost" href="{{ url('/') }}">{{ __('errors.home') }}</a>
@endsection
