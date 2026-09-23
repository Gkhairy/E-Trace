@extends('errors.layout')

{{-- Cadangan untuk kode 5xx tanpa halaman sendiri (502, 504, ...). --}}
@section('code', (string) (isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500))
@section('title', __('errors.5xx.title'))
@section('lead', __('errors.5xx.lead'))

@section('actions')
    <a class="btn btn-primary" href="{{ \App\Support\ErrorPage::retryUrl() }}">{{ __('errors.retry') }}</a>
    <a class="btn btn-ghost" href="{{ url('/') }}">{{ __('errors.home') }}</a>
@endsection
