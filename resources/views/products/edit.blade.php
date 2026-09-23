@extends('layouts.app')

@section('content')
@include('seller._nav', ['store' => auth()->user()->store])
@include('products._form', [
    'product'     => $product,
    'action'      => '/products/' . $product->id . '/update',
    'submitLabel' => 'Simpan perubahan',
])
@endsection

@section('scripts')
@stack('form-scripts')
@endsection
