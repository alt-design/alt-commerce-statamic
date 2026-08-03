@extends('statamic::layout')

@section('content')
    <div>
        <!-- Header Content -->
        <section>
            <h1 class="mb-2">Alt Commerce</h1>
            <p>Settings for Alt Commerce!</p>

        </section>
        <!-- End Header Content -->

        <div>
            <alt-commerce-settings-form
                    action="{{ cp_route('alt-commerce.settings.update') }}"
                    :blueprint='@json($blueprint)'
                    :meta='@json($meta)'
                    :values='@json($values)'
            >

            </alt-commerce-settings-form>
        </div>
    </div>
@endsection
