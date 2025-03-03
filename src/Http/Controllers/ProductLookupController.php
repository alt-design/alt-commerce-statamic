<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

use Statamic\Facades\Entry;
use Statamic\Fields\Value;

class ProductLookupController
{
    public function __invoke()
    {
        request()->validate([
            'id' => 'required|string|uuid'
        ]);

        $entry = Entry::query()
            ->where('collection', 'products')
            ->where('id', request('id'))
            ->first();

        if (empty($entry)) {
            abort(404);
        }

        return $entry->toAugmentedCollection()->mapWithKeys(fn (Value $value, $key) => [$key => $value->raw()])->toArray();
    }
}