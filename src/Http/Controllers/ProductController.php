<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

class ProductController
{
    public function index()
    {

        return view('alt-commerce::product-index');
    }

    public function show(string $productId)
    {
        return view('alt-commerce::product-show');
    }

}