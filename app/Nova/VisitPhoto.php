<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Models\Client as ClientModel;
use App\Models\VisitReport as VisitReportModel;
use App\Models\VisitAlert as VisitAlertModel;

class VisitPhoto extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\VisitPhoto>
     */
    public static $model = \App\Models\VisitPhoto::class;

    /**
     * Get the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        $date = $this->taken_at ? $this->taken_at->format('d M Y') : 'N/A';
        $type = ucfirst($this->type);

        // Get client name from visit
        $clientName = $this->visit && $this->visit->client
            ? $this->visit->client->name
            : 'Unknown Client';

        return 'Photo ' . $type . ' - ' . $clientName . ' (' . $date . ')';
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'title',
        'description',
    ];

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = true;

    /**
     * Default ordering for index queries.
     *
     * @var array
     */
    public static $orderBy = [
        'taken_at' => 'desc',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        // Check if we're creating from a polymorphic context (via MorphMany)
        $isClientContext = $request->viaResource === 'clients';
        $isVisitReportContext = $request->viaResource === 'visit-reports';
        $isEditableContext = $isClientContext || $isVisitReportContext;

        return [
            // Identity
            ID::make()->sortable()->readonly(),

            // Relations - show only on existing records
            BelongsTo::make('Visit', 'visit', Visit::class)
                ->sortable()
                ->nullable()
                ->exceptOnForms()
                ->display(function ($visit) {
                    if (!$visit || !$visit->client) {
                        return 'Visit #' . ($visit->id ?? 'N/A');
                    }
                    $date = $visit->started_at ? $visit->started_at->format('d M Y') : 'N/A';
                    return 'Visit - ' . $visit->client->name . ' (' . $date . ')';
                }),

            MorphTo::make('Attached To', 'photoable')
                ->types([
                    Client::class => ClientModel::class,
                    VisitReport::class => VisitReportModel::class,
                    VisitAlert::class => VisitAlertModel::class,
                ])
                ->sortable()
                ->exceptOnForms()
                ->searchable(),

            // Photo - editable when creating from Client
            Image::make('Photo', 'file_path')
                ->disk('public')
                ->path('client_photos')
                ->storeAs(function (Request $request) {
                    return uniqid() . '.' . $request->file('file_path')->getClientOriginalExtension();
                })
                ->preview(function ($value, $disk) {
                    if (!$value) return null;
                    return asset('storage/' . $value);
                })
                ->thumbnail(function ($value, $disk) {
                    if (!$value) return null;
                    return asset('storage/' . $value);
                })
                ->prunable()
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    if ($request->hasFile($requestAttribute)) {
                        $file = $request->file($requestAttribute);

                        // Store the file
                        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('client_photos', $filename, 'public');

                        // Set file path
                        $model->{$attribute} = $path;

                        // Automatically set metadata
                        $model->file_name = $file->getClientOriginalName();
                        $model->mime_type = $file->getMimeType();
                        $model->file_size = $file->getSize();

                        // Set defaults for required fields if not set
                        if (!$model->taken_at) {
                            $model->taken_at = now();
                        }
                        if (!$model->latitude) {
                            $model->latitude = 0;
                        }
                        if (!$model->longitude) {
                            $model->longitude = 0;
                        }
                    }
                })
                ->rules($isEditableContext ? ['required', 'image', 'mimes:jpg,jpeg,png,heic,webp', 'max:10240'] : [])
                ->readonly(!$isEditableContext)
                ->help($isEditableContext ? 'Upload a photo (max 10MB)' : null),

            Select::make('Type', 'type')
                ->options([
                    'facade' => 'Facade',
                    'shelves' => 'Shelves',
                    'stock' => 'Stock',
                    'anomaly' => 'Anomaly',
                    'other' => 'Other',
                ])
                ->displayUsingLabels()
                ->sortable()
                ->rules($isEditableContext ? 'required' : [])
                ->default('other')
                ->hideFromIndex()
                ->readonly(!$isEditableContext),

            Text::make('Title', 'title')
                ->nullable()
                ->hideFromIndex()
                ->readonly(!$isEditableContext),

            Textarea::make('Description', 'description')
                ->nullable()
                ->hideFromIndex()
                ->readonly(!$isEditableContext),

            Text::make('File Name', 'file_name')
                ->hideFromIndex()
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->exceptOnForms(),

            Text::make('Mime Type', 'mime_type')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->exceptOnForms(),

            Number::make('File Size (KB)', 'file_size')
                ->displayUsing(fn ($value) => $value ? round($value / 1024, 2) : null)
                ->hideFromIndex()
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->exceptOnForms(),

            // Geolocation
            Number::make('Latitude', 'latitude')
                ->step(0.0000001)
                ->hideFromIndex()
                ->default(0)
                ->hideWhenCreating()
                ->readonly(!$isEditableContext),

            Number::make('Longitude', 'longitude')
                ->step(0.0000001)
                ->hideFromIndex()
                ->default(0)
                ->hideWhenCreating()
                ->readonly(!$isEditableContext),

            Text::make('GPS Location', function () {
                if ($this->latitude && $this->longitude && ($this->latitude != 0 || $this->longitude != 0)) {
                    $lat = number_format($this->latitude, 7);
                    $lng = number_format($this->longitude, 7);
                    $mapsUrl = "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
                    return "<a href=\"{$mapsUrl}\" target=\"_blank\" class=\"link-default\">{$lat}, {$lng}</a>";
                }
                return 'No GPS data';
            })
                ->asHtml()
                ->onlyOnDetail(),

            // Timing - hidden when creating
            DateTime::make('Taken At', 'taken_at')
                ->sortable()
                ->default(now())
                ->hideWhenCreating()
                ->readonly(!$isEditableContext),

            // Audit
            DateTime::make('Created At', 'created_at')
                ->readonly()
                ->hideFromIndex()
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
            new Filters\VisitPhotoTypeFilter,
            new Filters\BaseCommercialeFilter,
            new Filters\ZoneFilter,
            new Filters\CommercialFilter,
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

        // ROLE_BASE_MANAGER sees photos of their base
        if ($user->hasRole('ROLE_BASE_MANAGER')) {
            $baseIds = $user->basesCommerciales()->pluck('bases_commerciales.id');
            return $query->whereHas('visit', function ($q) use ($baseIds) {
                $q->whereIn('base_commerciale_id', $baseIds);
            });
        }

        // ROLE_AGENT sees only photos from own visits
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

        // Super admins, commercial admins, agents can create photos
        // Only ROLE_FINANCE cannot create
        if ($user->hasRole('ROLE_SUPER_ADMIN')
            || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
            || $user->hasRole('ROLE_AGENT')
            || $user->hasRole('ROLE_BASE_MANAGER')
            || $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('direction')
            || $user->hasRole('commercial')) {
            return true;
        }

        // Finance users cannot create
        if ($user->hasRole('ROLE_FINANCE')) {
            return false;
        }

        // Default: deny creation for unknown roles
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

        // Allow updating photos attached to clients
        if ($this->resource->photoable_type === 'App\Models\Client') {
            // Same permissions as client update
            return !$user->hasRole('ROLE_FINANCE');
        }

        // Allow super admins to update photos attached to visit reports
        if ($this->resource->photoable_type === 'App\Models\VisitReport') {
            return $user->hasRole('ROLE_SUPER_ADMIN');
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

        // Allow deleting photos attached to clients
        if ($this->resource->photoable_type === 'App\Models\Client') {
            // Same permissions as client update
            return !$user->hasRole('ROLE_FINANCE');
        }

        // Allow super admins to delete photos attached to visit reports
        if ($this->resource->photoable_type === 'App\Models\VisitReport') {
            return $user->hasRole('ROLE_SUPER_ADMIN');
        }

        return false;
    }
}
