<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

class ReportController
{
    public function __invoke()
    {
        return view('alt-commerce::reports.index');
    }

}