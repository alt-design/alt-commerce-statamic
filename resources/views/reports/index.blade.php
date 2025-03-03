@extends('statamic::layout')

@section('content')
    <section class="mb-2">
        <h1>Reports</h1>
    </section>

    <div class="card p-6 w-full space-y-5">
        <div class="flex items-center">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd">
                <path d="M16 2v7h-2v-5h-12v16h12v-5h2v7h-16v-20h16zm2 9v-4l6 5-6 5v-4h-10v-2h10z"/>
            </svg>
            <div class="text-lg font-bold align-baseline ml-4">Order Items CSV Export</div>
        </div>

        <form action="{{ cp_route('alt-commerce::exports.order-item') }}" method="POST" class="mt-5">
            @csrf

            <!-- Filters --->
            <div class="pl-5">
                <div>
                    <label>Date From</label>
                    <input name="date_from" type="date" class="input border px-3 rounded py-1">
                </div>
                <div class="mt-3">
                    <label>Date To</label>
                    <input name="date_to" type="date" class="input border px-3 rounded py-1 ">
                </div>
            </div>

            <div class="mt-5">
                <button class="btn btn-primary" type="submit" >Export</button>
            </div>
        </form>
    </div>
@endsection
