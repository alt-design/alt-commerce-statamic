<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;


class CustomerLookupController
{
    public function __invoke()
    {
        request()->validate([
            'id' => 'required'
        ]);

        $class = config('alt-commerce.customer');

        return $class::findOrFail(request('id'))->toArray();
    }
}