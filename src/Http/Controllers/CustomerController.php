<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

class CustomerController
{
    public function index()
    {

        return view('alt-commerce::customer-index');
    }

    public function show(string $customerId)
    {
        return view('alt-commerce::customer-show');
    }

}