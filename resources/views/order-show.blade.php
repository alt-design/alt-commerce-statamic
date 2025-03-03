@extends('statamic::layout')
@section('content')

    <order-view :initial-order="{{ json_encode($order) }}"
                :gateway-urls="{{ json_encode($gatewayUrls) }}"
                :additional-fields="{{ json_encode($additionalFields) }}"
                item-action-url="{{ cp_route('collections.entries.actions.run', 'orders') }}"
                :initial-item-actions="{{ json_encode($actions)}}" />

@endsection
