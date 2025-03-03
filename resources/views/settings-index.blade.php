@extends('statamic::layout')

@section('content')
    <div>
        <publish-form
                title="Alt Commerce Settings"
                action="{{ cp_route('alt-commerce.settings.update') }}"
                :blueprint='@json($blueprint)'
                :meta='@json($meta)'
                :values='@json($values)'
        ></publish-form>
    </div>
@endsection
