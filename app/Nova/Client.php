<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Http\Requests\NovaRequest;

class Client extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Client>
     */
    public static $model = \App\Models\Client::class;

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
        'phone',
        'city',
    ];

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Commercial';

    /**
     * Default ordering for index queries.
     *
     * @var array
     */
    public static $orderBy = [
        'created_at' => 'desc',
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
                ->readonly(fn() => $this->resource->exists)
                ->rules('required', 'max:255', 'unique:clients,code,{{resourceId}}'),

            Text::make('Name', 'name')
                ->sortable()
                ->rules('required', 'max:255'),

            // Classification
            Select::make('Type', 'type')
                ->options([
                    'Boutique' => 'Boutique',
                    'Supermarché' => 'Supermarché',
                    'Demi-grossiste' => 'Demi-grossiste',
                    'Grossiste' => 'Grossiste',
                    'Distributeur' => 'Distributeur',
                    'Autre' => 'Autre',
                ])
                ->sortable()
                ->rules('required')
                ->displayUsingLabels(),

            Select::make('Potential', 'potential')
                ->options([
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                ])
                ->sortable()
                ->rules('required')
                ->displayUsingLabels(),

            // Scope
            BelongsTo::make('Base Commerciale', 'baseCommerciale', BaseCommerciale::class)
                ->sortable()
                ->searchable()
                ->readonly(fn() => $this->resource->exists)
                ->rules('required'),

            BelongsTo::make('Zone', 'zone', Zone::class)
                ->sortable()
                ->searchable()
                ->rules('required'),

            // Contact
            Text::make('Manager Name', 'manager_name')
                ->nullable(),

            Text::make('Phone', 'phone')
                ->rules('required', 'max:255'),

            Text::make('WhatsApp', 'whatsapp')
                ->nullable(),

            Text::make('Email', 'email')
                ->nullable()
                ->rules('nullable', 'email', 'max:255'),

            // Address
            Text::make('City', 'city')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('District', 'district')
                ->nullable(),

            Textarea::make('Address Description', 'address_description')
                ->nullable()
                ->hideFromIndex(),

            // Geolocation
            Number::make('Latitude', 'latitude')
                ->step(0.0000001)
                ->readonly()
                ->rules('required', 'numeric', 'between:-90,90')
                ->hideFromIndex(),

            Number::make('Longitude', 'longitude')
                ->step(0.0000001)
                ->readonly()
                ->rules('required', 'numeric', 'between:-180,180')
                ->hideFromIndex(),

            // Commercial
            Select::make('Visit Frequency', 'visit_frequency')
                ->options([
                    'weekly' => 'Weekly',
                    'biweekly' => 'Biweekly',
                    'monthly' => 'Monthly',
                    'other' => 'Other',
                ])
                ->rules('required')
                ->displayUsingLabels(),

            // Status
            Boolean::make('Active', 'is_active')
                ->sortable(),

            // Assigned Commercials (Many-to-Many)
            BelongsToMany::make('Commerciaux assignés', 'assignedUsers', User::class)
                ->fields(function () {
                    return [
                        Select::make('Role', 'role')
                            ->options([
                                'primary' => 'Primary',
                                'secondary' => 'Secondary',
                            ])
                            ->default('secondary')
                            ->rules('required'),

                        DateTime::make('Assigned At', 'assigned_at')
                            ->default(now())
                            ->hideWhenCreating()
                            ->readonly(),

                        Boolean::make('Active', 'active')
                            ->default(true),
                    ];
                })
                ->searchable()
                ->singularLabel('Commercial')
                ->rules('required', 'min:1')
                ->help('At least one commercial must be assigned')
                ->readonly(function ($request) {
                    $user = $request->user();
                    // Commercial users cannot modify assignments
                    return $user && ($user->hasRole('ROLE_AGENT') || $user->hasRole('commercial'));
                })
                ->canSee(function ($request) {
                    return true; // Everyone can see, but readonly logic applies
                })
                ->prunable(), // Allow removing assignments

            // Audit
            BelongsTo::make('Created By', 'creator', User::class)
                ->readonly()
                ->exceptOnForms(),

            DateTime::make('Created At', 'created_at')
                ->readonly()
                ->exceptOnForms(),

            // Photos
            MorphMany::make('Photos', 'photos', VisitPhoto::class)
                ->singularLabel('Photo')
                ->collapsable()
                ->canSee(function ($request) {
                    return true;
                }),
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
            new Filters\ClientBaseCommercialeFilter,
            new Filters\ClientZoneFilter,
            new Filters\ClientTypeFilter,
            new Filters\ClientPotentialFilter,
            new Filters\ClientActiveFilter,
            new Filters\ClientAssignedCommercialFilter,
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

        // Super Admin, Admin or Direction: see all clients
        if ($user->hasRole('ROLE_SUPER_ADMIN')
            || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
            || $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('direction')) {
            return $query;
        }

        // Responsable de base: see clients in their commercial bases
        if ($user->hasRole('ROLE_BASE_MANAGER') || $user->hasRole('responsable_base')) {
            $baseIds = $user->basesCommerciales()->pluck('bases_commerciales.id');
            return $query->whereIn('base_commerciale_id', $baseIds);
        }

        // Commercial: see only assigned clients (pivot-based)
        if ($user->hasRole('ROLE_AGENT') || $user->hasRole('commercial')) {
            return $query->whereHas('assignedUsers', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                  ->where('client_user.active', true);
            });
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

        // Commercial, Admin, Direction, Super Admin can create clients
        return $user->hasRole('ROLE_AGENT')
            || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
            || $user->hasRole('ROLE_SUPER_ADMIN')
            || $user->hasRole('commercial')
            || $user->hasRole('admin')
            || $user->hasRole('direction')
            || $user->hasRole('super_admin');
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

        // ROLE_FINANCE has read-only access
        if ($user->hasRole('ROLE_FINANCE')) {
            return false;
        }

        // Use policy authorization
        return $request->user()->can('update', $this->resource);
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

        // Prevent delete if client has visits or orders
        if ($this->resource->visits()->exists() || $this->resource->orders()->exists()) {
            return false;
        }

        // ROLE_FINANCE cannot delete
        if ($user->hasRole('ROLE_FINANCE')) {
            return false;
        }

        // Use policy authorization
        return $request->user()->can('delete', $this->resource);
    }

    /**
     * Build a "relatable" query for zones.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableZones(NovaRequest $request, $query)
    {
        $user = $request->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // ROLE_AGENT can only select zones assigned to them
        if ($user->hasRole('ROLE_AGENT')) {
            $zoneIds = $user->zones()->pluck('zones.id');
            return $query->whereIn('zones.id', $zoneIds);
        }

        return $query;
    }

    /**
     * Build a "relatable" query for base commerciales.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableBaseCommerciales(NovaRequest $request, $query)
    {
        $user = $request->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // ROLE_AGENT can only select their assigned base
        if ($user->hasRole('ROLE_AGENT')) {
            $baseIds = $user->basesCommerciales()->pluck('bases_commerciales.id');
            return $query->whereIn('bases_commerciales.id', $baseIds);
        }

        return $query;
    }

    /**
     * Build a "relatable" query for assigned users.
     * Only show users with 'commercial' or 'ROLE_AGENT' role.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableAssignedUsers(NovaRequest $request, $query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('code', ['commercial', 'ROLE_AGENT']);
        });
    }

    /**
     * Handle any post-creation tasks.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public static function afterCreate(NovaRequest $request, $model)
    {
        // Update pivot records to set assigned_by for new attachments
        static::updatePivotAssignedBy($request, $model);
    }

    /**
     * Handle any post-update tasks.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public static function afterUpdate(NovaRequest $request, $model)
    {
        // Update pivot records to set assigned_by for new attachments
        static::updatePivotAssignedBy($request, $model);
    }

    /**
     * Update pivot records to set assigned_by and assigned_at.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    protected static function updatePivotAssignedBy(NovaRequest $request, $model)
    {
        $user = $request->user();

        // Update all pivot records that don't have assigned_by set
        \DB::table('client_user')
            ->where('client_id', $model->id)
            ->whereNull('assigned_by')
            ->update([
                'assigned_by' => $user->id,
                'assigned_at' => now(),
            ]);
    }
}
