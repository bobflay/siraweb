<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;

class Routing extends Resource
{
    public static $model = \App\Models\Routing::class;

    public static $title = 'id';

    public static $search = [
        'id',
    ];

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Commercial', 'user', User::class)
                ->searchable()
                ->withoutTrashed()
                ->rules('required'),

            BelongsTo::make('Base Commerciale', 'baseCommerciale', BaseCommerciale::class)
                ->searchable()
                ->withoutTrashed()
                ->rules('required'),

            BelongsTo::make('Zone', 'zone', Zone::class)
                ->searchable()
                ->withoutTrashed()
                ->nullable(),

            Date::make('Route Date', 'route_date')
                ->sortable()
                ->rules('required'),

            Text::make('Start Time', 'start_time')
                ->hideFromIndex(),

            Select::make('Status', 'status')
                ->options([
                    'planned' => 'Planned',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ])
                ->displayUsingLabels()
                ->rules('required'),

            BelongsTo::make('Created By', 'creator', User::class)
                ->searchable()
                ->withoutTrashed()
                ->readonly()
                ->hideWhenCreating()
                ->hideFromIndex(),

            DateTime::make('Created At', 'created_at')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->hideFromIndex(),

            HasMany::make('Routing Items', 'routingItems', RoutingItem::class),
        ];
    }

    public function cards(NovaRequest $request)
    {
        return [];
    }

    public function filters(NovaRequest $request)
    {
        return [
            new Filters\RoutingCommercialFilter,
            new Filters\RoutingBaseCommercialeFilter,
            new Filters\RoutingDateFilter,
            new Filters\RoutingStatusFilter,
        ];
    }

    public function lenses(NovaRequest $request)
    {
        return [];
    }

    public function actions(NovaRequest $request)
    {
        return [];
    }

    public static function authorizedToCreate(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        return $user->hasRole('ROLE_COMMERCIAL_ADMIN') || $user->hasRole('ROLE_SUPER_ADMIN');
    }

    public function authorizedToUpdate(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        return $user->hasRole('ROLE_COMMERCIAL_ADMIN') || $user->hasRole('ROLE_SUPER_ADMIN');
    }

    public function authorizedToDelete(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        return $user->hasRole('ROLE_COMMERCIAL_ADMIN') || $user->hasRole('ROLE_SUPER_ADMIN');
    }
}
