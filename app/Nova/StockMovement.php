<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Http\Requests\NovaRequest;

class StockMovement extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\StockMovement>
     */
    public static $model = \App\Models\StockMovement::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'notes',
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Mouvements de Stock';
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return 'Mouvement de Stock';
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

            BelongsTo::make('Agent', 'user', User::class)
                ->sortable()
                ->searchable(),

            BelongsTo::make('Product', 'product', Product::class)
                ->sortable()
                ->searchable(),

            Badge::make('Type', 'movement_type')
                ->map([
                    'in' => 'success',
                    'out' => 'danger',
                    'adjustment' => 'warning',
                ])
                ->labels([
                    'in' => 'Entree',
                    'out' => 'Sortie',
                    'adjustment' => 'Ajustement',
                ])
                ->sortable(),

            Number::make('Quantity', 'quantity')
                ->sortable()
                ->step(0.01),

            Text::make('Reference Type', 'reference_type')
                ->sortable()
                ->displayUsing(function ($value) {
                    return $value === 'invoice' ? 'Facture' : ($value === 'order' ? 'Commande' : $value);
                }),

            Text::make('Reference ID', 'reference_id')
                ->sortable(),

            Textarea::make('Notes', 'notes')
                ->hideFromIndex(),

            BelongsTo::make('Stock', 'stockCommercial', StockCommercial::class)
                ->hideFromIndex(),

            DateTime::make('Created At', 'created_at')
                ->sortable()
                ->exceptOnForms(),
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
        return false; // Movements are created automatically
    }

    /**
     * Determine if the current user can update the given resource.
     */
    public function authorizedToUpdate(Request $request)
    {
        return false; // Movements cannot be edited
    }

    /**
     * Determine if the current user can delete the given resource.
     */
    public function authorizedToDelete(Request $request)
    {
        return static::isSuperAdmin($request->user());
    }
}
