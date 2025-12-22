<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Http\Requests\NovaRequest;

class ProductCategory extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\ProductCategory>
     */
    public static $model = \App\Models\ProductCategory::class;

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
        'code',
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
        'parent_id' => 'asc',
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

            Text::make('Code', 'code')
                ->sortable()
                ->rules('required', 'max:255', 'unique:product_categories,code,{{resourceId}}'),

            Text::make('Name', 'name')
                ->sortable()
                ->rules('required', 'max:255'),

            // Hierarchy
            BelongsTo::make('Parent Category', 'parent', ProductCategory::class)
                ->nullable()
                ->searchable()
                ->withoutTrashed()
                ->showCreateRelationButton()
                ->help('Leave empty for top-level category'),

            HasMany::make('Children', 'children', ProductCategory::class),

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
            new Filters\ProductCategoryActiveFilter,
            new Filters\ProductCategoryLevelFilter,
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
     * Build a "relatable" query for parent categories.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableParents(NovaRequest $request, $query)
    {
        // Prevent selecting itself as parent
        if ($request->resourceId) {
            return $query->where('id', '!=', $request->resourceId);
        }

        return $query;
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

        // Prevent deleting if has children
        if ($this->resource->children()->exists()) {
            return false;
        }

        return $user->hasRole('ROLE_SUPER_ADMIN') ||
               $user->hasRole('ROLE_COMMERCIAL_ADMIN');
    }

    /**
     * Determine if the user can view the given resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function authorizedToView(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        // All roles except AGENT can view
        return !$user->hasRole('ROLE_AGENT');
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

        // ROLE_AGENT has no access
        if ($user->hasRole('ROLE_AGENT')) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
