@extends('statamic::layout')
@section('title', 'Order')
@section('content')
   <order-view endpoint="{{ $endpoint }}" />
@endsection
