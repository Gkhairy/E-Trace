@extends('errors.layout')

@section('code', '403')
@section('title', __('errors.403.title'))
@section('lead', __('errors.403.lead'))

@section('detail')
    {{-- Pesan abort(403, '...') dari aplikasi (mis. "Hanya admin/pengawas ..."),
         bukan teks bawaan framework. Di-escape oleh {{ }}. --}}
    @if($msg = \App\Support\ErrorPage::message($exception ?? null))
        <div class="note note-plain">{{ $msg }}</div>
    @endif
@endsection

@section('actions')
    @php $back = \App\Support\ErrorPage::backUrl(); @endphp
    <a class="btn btn-primary" href="{{ url('/') }}">{{ __('errors.home') }}</a>
    @if($back !== url('/'))
        <a class="btn btn-ghost" href="{{ $back }}">{{ __('errors.back') }}</a>
    @endif
@endsection
