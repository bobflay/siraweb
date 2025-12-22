<?php

namespace App\Nova\Filters;

use App\Models\BaseCommerciale;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class BaseCommercialeFilter extends Filter
{
    public $name = 'Base Commerciale';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        return $query->where('base_commerciale_id', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return BaseCommerciale::where('is_active', true)
            ->orderBy('name')
            ->pluck('id', 'name')
            ->toArray();
    }
}
