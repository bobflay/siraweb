<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class RoutingItem extends Resource
{
    public static $model = \App\Models\RoutingItem::class;

    public static $title = 'id';

    public static $search = [
        'id',
    ];

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Routing', 'routing', Routing::class)
                ->searchable()
                ->withoutTrashed()
                ->readonly()
                ->rules('required'),

            BelongsTo::make('Client', 'client', Client::class)
                ->searchable()
                ->withoutTrashed()
                ->rules('required'),

            BelongsTo::make('Zone', 'zone', Zone::class)
                ->searchable()
                ->withoutTrashed()
                ->rules('required'),

            Number::make('Sequence Order', 'sequence_order')
                ->sortable()
                ->rules('required', 'integer'),

            DateTime::make('Planned At', 'planned_at')
                ->hideFromIndex(),

            BelongsTo::make('Visit', 'visit', Visit::class)
                ->searchable()
                ->withoutTrashed()
                ->nullable()
                ->readonly()
                ->hideFromIndex(),

            Badge::make('Status', 'status')
                ->map([
                    'pending' => 'warning',
                    'visited' => 'success',
                    'skipped' => 'danger',
                ])
                ->labels([
                    'pending' => 'Pending',
                    'visited' => 'Visited',
                    'skipped' => 'Skipped',
                ]),

            Boolean::make('Overridden', 'overridden')
                ->hideFromIndex(),

            Textarea::make('Override Reason', 'override_reason')
                ->hideFromIndex()
                ->alwaysShow(),

            BelongsTo::make('Overridden By', 'overriddenBy', User::class)
                ->searchable()
                ->withoutTrashed()
                ->nullable()
                ->readonly()
                ->hideFromIndex()
                ->hideWhenCreating(),

            DateTime::make('Overridden At', 'overridden_at')
                ->readonly()
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->hideFromIndex(),
        ];
    }

    public function cards(NovaRequest $request)
    {
        return [];
    }

    public function filters(NovaRequest $request)
    {
        return [];
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
