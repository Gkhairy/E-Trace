@extends('errors.layout')

@section('code', '404')
@section('title', __('errors.404.title'))
@section('lead', __('errors.404.lead'))

@section('detail')
    <form class="search" action="{{ url('/search') }}" method="get" role="search">
        <label class="sr-only" for="q">{{ __('errors.search') }}</label>
        <input id="q" name="q" type="search" maxlength="80" enterkeyhint="search"
               placeholder="{{ __('errors.search_placeholder') }}">
        <button class="btn btn-primary" type="submit">{{ __('errors.search') }}</button>
    </form>
@endsection

@section('actions')
    @php $back = \App\Support\ErrorPage::backUrl(); @endphp
    <a class="btn btn-ghost" href="{{ url('/') }}">{{ __('errors.home') }}</a>
    @if($back !== url('/'))
        <a class="btn btn-ghost" href="{{ $back }}">{{ __('errors.back') }}</a>
    @endif
@endsection
