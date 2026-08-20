@extends('statamic::layout')
@section('title', 'Reports')

@section('content')
    <reports-view export-url="{{ cp_route('alt-commerce::exports.order-item') }}"></reports-view>
@endsection
