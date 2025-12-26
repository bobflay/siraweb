<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Order extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Order>
     */
    public static $model = \App\Models\Order::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'reference';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'reference',
    ];

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Commandes';

    /**
     * Default ordering.
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->orderBy('ordered_at', 'desc');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            Text::make('Reference')
                ->readonly()
                ->sortable(),

            BelongsTo::make('Client')
                ->readonly()
                ->searchable(),

            BelongsTo::make('Commercial', 'user', User::class)
                ->readonly()
                ->searchable(),

            BelongsTo::make('Visit')
                ->readonly()
                ->nullable()
                ->searchable(),

            BelongsTo::make('Base Commerciale', 'baseCommerciale', BaseCommerciale::class)
                ->readonly()
                ->searchable(),

            BelongsTo::make('Zone')
                ->readonly()
                ->searchable(),

            Currency::make('Total Amount')
                ->currency('XOF')
                ->readonly(),

            Text::make('Currency')
                ->readonly()
                ->hideFromIndex(),

            Select::make('Status')
                ->options([
                    'draft' => 'Draft',
                    'submitted' => 'Submitted',
                    'validated' => 'Validated',
                    'prepared' => 'Prepared',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ])
                ->displayUsingLabels()
                ->sortable(),

            DateTime::make('Ordered At')
                ->readonly()
                ->sortable(),

            DateTime::make('Validated At')
                ->readonly()
                ->nullable()
                ->hideFromIndex(),

            DateTime::make('Created At')
                ->readonly()
                ->onlyOnDetail(),

            HasMany::make('Order Items', 'orderItems', OrderItem::class),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }

    /**
     * Determine if the current user can create new resources.
     */
    public static function authorizedToCreate(Request $request)
    {
        return false;
    }

    /**
     * Determine if the current user can update the given resource.
     */
    public function authorizedToUpdate(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        // ROLE_AGENT: read-only
        if ($user->hasRole('ROLE_AGENT')) {
            return false;
        }

        // ROLE_FINANCE: read-only
        if ($user->hasRole('ROLE_FINANCE')) {
            return false;
        }

        // ROLE_BASE_MANAGER: can update orders from their base
        if ($user->hasRole('ROLE_BASE_MANAGER')) {
            return $user->bases->contains($this->base_commerciale_id);
        }

        // ROLE_COMMERCIAL_ADMIN, ROLE_SUPER_ADMIN: full access
        return $user->hasRole('ROLE_COMMERCIAL_ADMIN') || $user->hasRole('ROLE_SUPER_ADMIN');
    }

    /**
     * Determine if the current user can delete the given resource.
     */
    public function authorizedToDelete(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        return $user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('super_admin');
    }

    /**
     * Determine if the current user can view the given resource.
     */
    public function authorizedToView(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        // ROLE_AGENT: only own orders
        if ($user->hasRole('ROLE_AGENT')) {
            return $this->user_id === $user->id;
        }

        // All other roles can view
        return true;
    }
}
