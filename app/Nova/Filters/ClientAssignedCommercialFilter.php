<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Models\User;

class ClientAssignedCommercialFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * The displayable name of the filter.
     *
     * @var string
     */
    public $name = 'Commercial Assigné';

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
        return $query->whereHas('assignedUsers', function ($q) use ($value) {
            $q->where('users.id', $value)
              ->where('client_user.active', true);
        });
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        // Get all users with 'commercial' or 'ROLE_AGENT' role
        $commercials = User::whereHas('roles', function ($q) {
            $q->whereIn('code', ['commercial', 'ROLE_AGENT']);
        })->orderBy('name')->get();

        $options = [];
        foreach ($commercials as $commercial) {
            $options[$commercial->name] = $commercial->id;
        }

        return $options;
    }
}
