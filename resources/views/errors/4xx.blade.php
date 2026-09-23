@extends('errors.layout')

{{-- Cadangan untuk kode 4xx tanpa halaman sendiri (405, 410, 413, ...). --}}
@section('code', (string) (isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400))
@section('title', __('errors.4xx.title'))
@section('lead', __('errors.4xx.lead'))

@section('actions')
    <a class="btn btn-primary" href="{{ \App\Support\ErrorPage::backUrl() }}">{{ __('errors.back') }}</a>
    <a class="btn btn-ghost" href="{{ url('/') }}">{{ __('errors.home') }}</a>
@endsection
