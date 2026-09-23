@extends('errors.layout')

@section('code', '429')
@section('title', __('errors.429.title'))
@section('lead', __('errors.429.lead'))

@php $wait = \App\Support\ErrorPage::retryAfter($exception ?? null); @endphp

@section('detail')
    @if($wait)
        <p class="note note-plain" id="wait" aria-live="polite" data-seconds="{{ $wait }}"
           data-ready="{{ __('errors.429.ready') }}" data-template="{{ __('errors.429.wait', ['seconds' => '__S__']) }}">
            {{ __('errors.429.wait', ['seconds' => $wait]) }}
        </p>
    @endif
@endsection

@section('actions')
    <a class="btn btn-primary" id="retry" href="{{ \App\Support\ErrorPage::retryUrl() }}"
       @if($wait) aria-disabled="true" tabindex="-1" @endif>{{ __('errors.retry') }}</a>
    <a class="btn btn-ghost" href="{{ url('/') }}">{{ __('errors.home') }}</a>
@endsection

@push('scripts')
    <script>
        (function () {
            var box = document.getElementById('wait');
            var btn = document.getElementById('retry');
            if (!box || !btn) return;
            var left = parseInt(box.dataset.seconds, 10) || 0;
            var tick = function () {
                if (left <= 0) {
                    box.textContent = box.dataset.ready;
                    btn.removeAttribute('aria-disabled');
                    btn.removeAttribute('tabindex');
                    return;
                }
                box.textContent = box.dataset.template.replace('__S__', left);
                left -= 1;
                setTimeout(tick, 1000);
            };
            tick();
        })();
    </script>
@endpush
