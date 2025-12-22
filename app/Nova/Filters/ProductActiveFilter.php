<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class ProductActiveFilter extends Filter
{
    public $component = 'select-filter';
    public $name = 'Status';

    public function apply(NovaRequest $request, $query, $value)
    {
        return $query->where('is_active', $value);
    }

    public function options(NovaRequest $request)
    {
        return [
            'Active' => true,
            'Inactive' => false,
        ];
    }
}
