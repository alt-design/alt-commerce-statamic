<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;


use App\Models\User;

class CustomerLookupController
{
    public function __invoke()
    {
        request()->validate([
            'id' => 'required'
        ]);

        return User::findOrFail(request('id'))->toArray();
    }
}