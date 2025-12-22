<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Http\Requests\NovaRequest;

class VisitAlert extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\VisitAlert>
     */
    public static $model = \App\Models\VisitAlert::class;

    /**
     * Get the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        if (!$this->client) {
            return 'Alert #' . $this->id;
        }

        $date = $this->alerted_at ? $this->alerted_at->format('d M Y') : 'N/A';
        $typeLabel = ucfirst(str_replace('_', ' ', $this->type));

        return 'Alert ' . $typeLabel . ' - ' . $this->client->name . ' (' . $date . ')';
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'comment',
        'custom_type',
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
        'alerted_at' => 'desc',
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
            ID::make()->sortable()->readonly(),

            // Relations
            BelongsTo::make('Visit', 'visit', Visit::class)
                ->sortable()
                ->readonly()
                ->display(function ($visit) {
                    if (!$visit || !$visit->client) {
                        return 'Visit #' . ($visit->id ?? 'N/A');
                    }
                    $date = $visit->started_at ? $visit->started_at->format('d M Y') : 'N/A';
                    return 'Visit - ' . $visit->client->name . ' (' . $date . ')';
                }),

            BelongsTo::make('Client', 'client', Client::class)
                ->sortable()
                ->readonly(),

            BelongsTo::make('Commercial', 'user', User::class)
                ->sortable()
                ->readonly()
                ->hideFromIndex(),

            BelongsTo::make('Base Commerciale', 'baseCommerciale', BaseCommerciale::class)
                ->readonly()
                ->hideFromIndex(),

            BelongsTo::make('Zone', 'zone', Zone::class)
                ->readonly()
                ->hideFromIndex(),

            // Alert
            Badge::make('Type', 'type')
                ->map([
                    'rupture_grave' => 'danger',
                    'litige_paiement' => 'warning',
                    'probleme_rayon' => 'warning',
                    'risque_perte_client' => 'danger',
                    'demande_speciale' => 'info',
                    'nouvelle_opportunite' => 'success',
                    'autre' => 'info',
                ])
                ->labels([
                    'rupture_grave' => 'Rupture Grave',
                    'litige_paiement' => 'Litige Paiement',
                    'probleme_rayon' => 'Problème Rayon',
                    'risque_perte_client' => 'Risque Perte Client',
                    'demande_speciale' => 'Demande Spéciale',
                    'nouvelle_opportunite' => 'Nouvelle Opportunité',
                    'autre' => 'Autre',
                ])
                ->sortable()
                ->readonly(),

            Text::make('Custom Type', 'custom_type')
                ->readonly()
                ->hideFromIndex()
                ->onlyOnDetail(),

            Textarea::make('Comment', 'comment')
                ->readonly()
                ->alwaysShow(),

            // Geolocation
            Number::make('Latitude', 'latitude')
                ->step(0.0000001)
                ->readonly()
                ->hideFromIndex(),

            Number::make('Longitude', 'longitude')
                ->step(0.0000001)
                ->readonly()
                ->hideFromIndex(),

            // Status & processing
            Badge::make('Status', 'status')
                ->map([
                    'pending' => 'warning',
                    'in_progress' => 'info',
                    'resolved' => 'success',
                    'closed' => 'default',
                ])
                ->labels([
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'resolved' => 'Resolved',
                    'closed' => 'Closed',
                ])
                ->sortable(),

            Select::make('Status', 'status')
                ->options([
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'resolved' => 'Resolved',
                    'closed' => 'Closed',
                ])
                ->displayUsingLabels()
                ->onlyOnForms()
                ->rules('required'),

            BelongsTo::make('Handled By', 'handler', User::class)
                ->nullable()
                ->hideFromIndex(),

            DateTime::make('Handled At', 'handled_at')
                ->readonly()
                ->hideFromIndex(),

            Textarea::make('Handling Comment', 'handling_comment')
                ->hideFromIndex(),

            // Timing
            DateTime::make('Alerted At', 'alerted_at')
                ->sortable()
                ->readonly(),

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
            new Filters\VisitAlertTypeFilter,
            new Filters\VisitAlertStatusFilter,
            new Filters\BaseCommercialeFilter,
            new Filters\ZoneFilter,
            new Filters\CommercialFilter,
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

        // ROLE_BASE_MANAGER sees alerts of their base
        if ($user->hasRole('ROLE_BASE_MANAGER')) {
            $baseIds = $user->basesCommerciales()->pluck('bases_commerciales.id');
            return $query->whereIn('base_commerciale_id', $baseIds);
        }

        // ROLE_AGENT sees only own alerts (read-only)
        if ($user->hasRole('ROLE_AGENT')) {
            return $query->where('user_id', $user->id);
        }

        // ROLE_FINANCE read-only access
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
        return false;
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

        // ROLE_AGENT and ROLE_FINANCE have read-only access
        if ($user->hasRole('ROLE_AGENT') || $user->hasRole('ROLE_FINANCE')) {
            return false;
        }

        // ROLE_BASE_MANAGER can update alerts for their base
        if ($user->hasRole('ROLE_BASE_MANAGER')) {
            $baseIds = $user->basesCommerciales()->pluck('bases_commerciales.id');
            return $baseIds->contains($this->base_commerciale_id);
        }

        // ROLE_SUPER_ADMIN and ROLE_COMMERCIAL_ADMIN can update all
        if ($user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('ROLE_COMMERCIAL_ADMIN')) {
            return true;
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
