<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;


use AltDesign\AltCommerceStatamic\Support\Settings;
use Statamic\Http\Controllers\Controller;

class SettingsController extends Controller
{


    public function __construct(protected Settings $settings)
    {

    }

    public function index()
    {
        $blueprint = $this->settings->blueprint();
        $fields = $blueprint->fields()->addValues($this->settings->all())->preProcess();

        return view('alt-commerce::settings', [
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values'=> $fields->values(),
        ]);
    }

    public function update()
    {
        $blueprint = $this->settings->blueprint();
        $fields = $blueprint->fields()->addValues(request()->all());
        $fields->validate();
        $this->settings->setAll($fields->process()->values()->toArray());
    }
}