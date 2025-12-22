<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\DateFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class RoutingDateFilter extends DateFilter
{
    public $name = 'Route Date';

    public function apply(NovaRequest $request, $query, $value)
    {
        return $query->whereDate('route_date', $value);
    }
}
