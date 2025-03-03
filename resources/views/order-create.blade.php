@extends('statamic::layout')
@section('content')

   <create-order-view
           save-url="{{ cp_route('alt-commerce::order.store') }}"
           product-lookup-url="{{ cp_route('alt-commerce::product.lookup') }}"
           customer-lookup-url="{{ cp_route('alt-commerce::customer.lookup') }}"
           basket-lookup-url="{{ cp_route('alt-commerce::basket.lookup') }}"
           :sections=" {{ json_encode($sections) }}">
   </create-order-view>


@endsection
