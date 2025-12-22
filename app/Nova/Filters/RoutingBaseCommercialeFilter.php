<?php

namespace App\Nova\Filters;

use App\Models\BaseCommerciale;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class RoutingBaseCommercialeFilter extends Filter
{
    public $name = 'Base Commerciale';

    public function apply(NovaRequest $request, $query, $value)
    {
        return $query->where('base_commerciale_id', $value);
    }

    public function options(NovaRequest $request)
    {
        return BaseCommerciale::pluck('id', 'name')->toArray();
    }
}
