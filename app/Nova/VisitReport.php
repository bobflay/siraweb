<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Http\Requests\NovaRequest;

class VisitReport extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\VisitReport>
     */
    public static $model = \App\Models\VisitReport::class;

    /**
     * Get the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        if (!$this->visit || !$this->visit->client) {
            return 'Report #' . $this->id;
        }

        $date = $this->validated_at
            ? $this->validated_at->format('d M Y')
            : ($this->created_at ? $this->created_at->format('d M Y') : 'N/A');

        return 'Report - ' . $this->visit->client->name . ' (' . $date . ')';
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Visits & Reports';

    /**
     * Default ordering for index queries.
     *
     * @var array
     */
    public static $orderBy = [
        'validated_at' => 'desc',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user && $user->hasRole('ROLE_SUPER_ADMIN');
        $isCreating = !$this->resource->exists;

        return [
            // Identity
            ID::make()->sortable()->readonly(),

            // Relations
            BelongsTo::make('Visit', 'visit', Visit::class)
                ->sortable()
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->searchable()
                ->required()
                ->display(function ($visit) {
                    if (!$visit || !$visit->client) {
                        return 'Visit #' . ($visit->id ?? 'N/A');
                    }
                    $date = $visit->started_at ? $visit->started_at->format('d M Y') : 'N/A';
                    return 'Visit - ' . $visit->client->name . ' (' . $date . ')';
                }),

            // GPS
            Number::make('Latitude', 'latitude')
                ->step(0.0000001)
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->hideFromIndex(),

            Number::make('Longitude', 'longitude')
                ->step(0.0000001)
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->hideFromIndex(),

            // Report fields
            Boolean::make('Manager Present', 'manager_present')
                ->readonly(!$isCreating || !$isSuperAdmin),

            Boolean::make('Order Made', 'order_made')
                ->readonly(!$isCreating || !$isSuperAdmin),

            Boolean::make('Needs Order', 'needs_order')
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->help('Client needs to place an order'),

            Text::make('Order Reference', 'order_reference')
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->hideFromIndex(),

            Number::make('Order Estimated Amount', 'order_estimated_amount')
                ->step(0.01)
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->displayUsing(fn ($value) => $value ? number_format($value, 0, ',', ' ') . ' XOF' : null),

            Boolean::make('Stock Shortage Observed', 'stock_shortage_observed')
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->help('Stock shortage was observed during visit'),

            Textarea::make('Stock Issues', 'stock_issues')
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->hideFromIndex()
                ->help('Details about stock issues'),

            Boolean::make('Competitor Activity Observed', 'competitor_activity_observed')
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->help('Competitor activity was observed during visit'),

            Textarea::make('Competitor Activity', 'competitor_activity')
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->hideFromIndex()
                ->help('Details about competitor activity'),

            Textarea::make('Comments', 'comments')
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->hideFromIndex(),

            // Validation
            DateTime::make('Validated At', 'validated_at')
                ->readonly(!$isCreating || !$isSuperAdmin)
                ->sortable(),

            // Audit
            DateTime::make('Created At', 'created_at')
                ->readonly()
                ->hideFromIndex(),

            // Photos
            HasMany::make('Photos', 'photos', VisitPhoto::class),
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
            new Filters\BaseCommercialeFilter,
            new Filters\ZoneFilter,
            new Filters\CommercialFilter,
            new Filters\VisitReportValidatedFilter,
            new Filters\VisitDateRangeFilter,
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

        // ROLE_BASE_MANAGER sees reports of their base
        if ($user->hasRole('ROLE_BASE_MANAGER')) {
            $baseIds = $user->basesCommerciales()->pluck('bases_commerciales.id');
            return $query->whereHas('visit', function ($q) use ($baseIds) {
                $q->whereIn('base_commerciale_id', $baseIds);
            });
        }

        // ROLE_AGENT sees only own visit reports
        if ($user->hasRole('ROLE_AGENT')) {
            return $query->whereHas('visit', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // ROLE_FINANCE read-only access (handled in authorizedToUpdate/Delete)
        if ($user->hasRole('ROLE_FINANCE')) {
            return $query;
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

        // Only ROLE_SUPER_ADMIN can create visit reports manually
        return $user->hasRole('ROLE_SUPER_ADMIN');
    }

    /**
     * Determine if the user can update the given resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function authorizedToUpdate(Request $request)
    {
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
        return false;
    }
}
