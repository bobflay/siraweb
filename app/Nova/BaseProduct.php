<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Http\Requests\NovaRequest;

class BaseProduct extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\BaseProduct>
     */
    public static $model = \App\Models\BaseProduct::class;

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
        'sku_base',
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
        'id' => 'desc',
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

            // Relationships
            BelongsTo::make('Product', 'product', Product::class)
                ->sortable()
                ->searchable()
                ->rules('required')
                ->displayUsing(fn ($product) => $product->name ?? $product->sku_global),

            BelongsTo::make('Base Commerciale', 'baseCommerciale', BaseCommerciale::class)
                ->sortable()
                ->searchable()
                ->rules('required'),

            // Base-specific identity
            Text::make('SKU Base', 'sku_base')
                ->sortable()
                ->rules('required', 'max:255'),

            // Pricing
            Number::make('Current Price', 'current_price')
                ->step(0.01)
                ->sortable()
                ->rules('required', 'numeric', 'min:0')
                ->displayUsing(fn ($value) => number_format($value, 0, ',', ' ') . ' XOF'),

            Boolean::make('Allow Discount', 'allow_discount')
                ->sortable(),

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
            new Filters\ProductBaseCommercialeFilter,
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
        $user = $request->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // ROLE_SUPER_ADMIN and ROLE_COMMERCIAL_ADMIN see all
        if ($user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('ROLE_COMMERCIAL_ADMIN')) {
            return $query;
        }

        // ROLE_BASE_MANAGER and ROLE_AGENT see base products of their assigned bases
        if ($user->hasRole('ROLE_BASE_MANAGER') || $user->hasRole('ROLE_AGENT')) {
            $baseIds = $user->basesCommerciales()->pluck('bases_commerciales.id');
            return $query->whereIn('base_commerciale_id', $baseIds);
        }

        // Default: no access
        return $query->whereRaw('1 = 0');
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
               $user->hasRole('ROLE_COMMERCIAL_ADMIN') ||
               $user->hasRole('ROLE_BASE_MANAGER');
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

        // ROLE_AGENT has read-only access
        if ($user->hasRole('ROLE_AGENT')) {
            return false;
        }

        // ROLE_SUPER_ADMIN and ROLE_COMMERCIAL_ADMIN can update all
        if ($user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('ROLE_COMMERCIAL_ADMIN')) {
            return true;
        }

        // ROLE_BASE_MANAGER can only update base products for their bases
        if ($user->hasRole('ROLE_BASE_MANAGER')) {
            $baseIds = $user->basesCommerciales()->pluck('bases_commerciales.id');
            return $baseIds->contains($this->base_commerciale_id);
        }

        return false;
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
