<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;


use AltDesign\AltCommerce\Contracts\CustomerRepository;

class CustomerLookupController
{
    public function __invoke(CustomerRepository $customerRepository)
    {
        request()->validate([
            'id' => 'required'
        ]);

        $customer = $customerRepository->find(request('id')) ?? throw new \Exception('Customer not found');

        return $customer->toArray();
    }
}