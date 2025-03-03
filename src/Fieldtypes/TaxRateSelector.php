<?php

namespace AltDesign\AltCommerceStatamic\Fieldtypes;

use AltDesign\AltCommerceStatamic\Support\Settings;

class TaxRateSelector extends BaseFieldType
{

    protected $keywords = ['tax', 'rate'];

    public function preload()
    {
        $options = [
            [
                'value' => null,
                'label' => 'None'
            ]
        ];

        $settings = app(Settings::class);
        return array_merge(
            $options,
            collect($settings->taxRules())
                ->map(fn($rule) => [
                    'value' => $rule['id'],
                    'label' => $rule['name']
                ])
                ->toArray()
        );
    }

}