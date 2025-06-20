<?php

namespace AltDesign\AltCommerceStatamic\Fieldtypes;

use Statamic\Query\Scopes\Filters\Fields\Integer as IntegerFilter;

class Money extends BaseFieldType
{

    protected $keywords = ['pricing', 'price', 'currency', 'money'];

    protected $categories = ['number'];

    protected $icon = '<svg fill="#333333" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M2,11.308a1,1,0,0,0,.293.707l9.692,9.692a1,1,0,0,0,1.414,0L21.707,13.4a1,1,0,0,0,0-1.414L12.015,2.293A1,1,0,0,0,11.308,2H3A1,1,0,0,0,2,3ZM4,4h6.894l8.692,8.692-6.894,6.894L4,10.894ZM9.923,7.154a1.958,1.958,0,1,1-2.769,0A1.957,1.957,0,0,1,9.923,7.154Z"></path></g></svg>';

    public function preProcess($data)
    {
        if ($data === null) {
            return null;
        }

        return number_format($data / 100, 2);
    }

    public function preProcessIndex($data)
    {
        return number_format($data / 100, 2);
    }

    public function process($data)
    {
        if ($data === null || $data === '') {
            return null;
        }

        // todo pull locale from statamic settings
        $fmt = new \NumberFormatter('en_GB', \NumberFormatter::DECIMAL);
        $data = $fmt->parse($data);
        return (int) ($data * 100);
    }

    public function filter()
    {
        return new IntegerFilter($this);
    }


}
