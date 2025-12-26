<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Http\Requests\NovaRequest;

class Product extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Product>
     */
    public static $model = \App\Models\Product::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'sku_global',
        'name',
    ];

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Products';

    /**
     * Default ordering for index queries.
     *
     * @var array
     */
    public static $orderBy = [
        'name' => 'asc',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            // Identity
            ID::make()->sortable(),

            Text::make('SKU Global', 'sku_global')
                ->sortable()
                ->rules('required', 'max:255', 'unique:products,sku_global,{{resourceId}}'),

            Text::make('Name', 'name')
                ->sortable()
                ->rules('required', 'max:255'),

            // Classification
            BelongsTo::make('Category', 'productCategory', ProductCategory::class)
                ->sortable()
                ->searchable()
                ->rules('required'),

            // Product details
            Text::make('Unit', 'unit')
                ->rules('required', 'max:255')
                ->help('Ex: kg, carton, sac, pièce'),

            Text::make('Packaging', 'packaging')
                ->nullable()
                ->help('Ex: 25kg, 50kg, 12x1L'),

            // Pricing
            Currency::make('Price', 'price')
                ->currency('XOF')
                ->nullable()
                ->sortable()
                ->help('Price from latest invoice OCR'),

            DateTime::make('Price Updated At', 'price_updated_at')
                ->readonly()
                ->exceptOnForms()
                ->hideFromIndex(),

            // Status
            Boolean::make('Active', 'is_active')
                ->sortable(),

            // Audit
            DateTime::make('Created At', 'created_at')
                ->readonly()
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
        return [
            new Filters\ProductCategoryFilter,
            new Filters\ProductActiveFilter,
        ];
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
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query;

        // $user = $request->user();

        // // ROLE_SUPER_ADMIN and ROLE_COMMERCIAL_ADMIN see all
        // if ($user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('ROLE_COMMERCIAL_ADMIN')) {
        //     return $query;
        // }

        // // ROLE_BASE_MANAGER see all (read-only)
        // if ($user->hasRole('ROLE_BASE_MANAGER')) {
        //     return $query;
        // }

        // // ROLE_AGENT: no access
        // return $query->whereRaw('1 = 0');
    }

    /**
     * Determine if the user can create the given resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function authorizedToCreate(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        return $user->hasRole('ROLE_SUPER_ADMIN') ||
               $user->hasRole('ROLE_COMMERCIAL_ADMIN');
    }

    /**
     * Determine if the user can update the given resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function authorizedToUpdate(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        return $user->hasRole('ROLE_SUPER_ADMIN') ||
               $user->hasRole('ROLE_COMMERCIAL_ADMIN');
    }

    /**
     * Determine if the user can delete the given resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function authorizedToDelete(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        return $user->hasRole('ROLE_SUPER_ADMIN') ||
               $user->hasRole('ROLE_COMMERCIAL_ADMIN');
    }
}
