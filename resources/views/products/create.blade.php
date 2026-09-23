@extends('layouts.app')

@section('content')
@include('seller._nav', ['store' => auth()->user()->store])
@include('products._form', [
    'product'     => null,
    'action'      => '/products/store',
    'submitLabel' => 'Simpan produk',
])
@endsection

@section('scripts')
@stack('form-scripts')
@endsection
