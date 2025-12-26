<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Http\Requests\NovaRequest;

class StockCommercial extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\StockCommercial>
     */
    public static $model = \App\Models\StockCommercial::class;

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
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Stock Commercial';
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return 'Stock Commercial';
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

            Number::make('Quantity', 'quantity')
                ->sortable()
                ->step(0.01)
                ->displayUsing(function ($value) {
                    $class = $value < 0 ? 'text-red-500' : ($value == 0 ? 'text-gray-500' : 'text-green-600');
                    return "<span class='{$class}'>{$value}</span>";
                })
                ->asHtml(),

            Badge::make('Stock Status', function () {
                if ($this->quantity < 0) {
                    return 'Negative';
                } elseif ($this->quantity == 0) {
                    return 'Empty';
                } elseif ($this->quantity < 10) {
                    return 'Low';
                }
                return 'OK';
            })->map([
                'Negative' => 'danger',
                'Empty' => 'warning',
                'Low' => 'warning',
                'OK' => 'success',
            ])->onlyOnIndex(),

            HasMany::make('Movements', 'movements', StockMovement::class),

            DateTime::make('Updated At', 'updated_at')
                ->sortable()
                ->exceptOnForms(),

            DateTime::make('Created At', 'created_at')
                ->sortable()
                ->exceptOnForms()
                ->hideFromIndex(),
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
        return false; // Stock is created automatically
    }

    /**
     * Determine if the current user can delete the given resource.
     */
    public function authorizedToDelete(Request $request)
    {
        return static::isSuperAdmin($request->user());
    }
}
