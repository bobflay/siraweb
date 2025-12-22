<?php

namespace App\Nova\Filters;

use App\Models\User;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class RoutingCommercialFilter extends Filter
{
    public $name = 'Commercial';

    public function apply(NovaRequest $request, $query, $value)
    {
        return $query->where('user_id', $value);
    }

    public function options(NovaRequest $request)
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'ROLE_AGENT');
        })->pluck('id', 'name')->toArray();
    }
}
