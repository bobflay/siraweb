<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class RoutingStatusFilter extends Filter
{
    public $name = 'Status';

    public function apply(NovaRequest $request, $query, $value)
    {
        return $query->where('status', $value);
    }

    public function options(NovaRequest $request)
    {
        return [
            'Planned' => 'planned',
            'In Progress' => 'in_progress',
            'Completed' => 'completed',
            'Cancelled' => 'cancelled',
        ];
    }
}
