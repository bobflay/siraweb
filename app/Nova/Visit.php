<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Http\Requests\NovaRequest;

class Visit extends Resource
{
    public static $model = \App\Models\Visit::class;

    /**
     * Get the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        if (!$this->client) {
            return 'Visit #' . $this->id;
        }

        $date = $this->started_at ? $this->started_at->format('d M Y') : 'N/A';
        return 'Visit - ' . $this->client->name . ' (' . $date . ')';
    }

    public static $search = [
        'id',
    ];

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Visits & Reports';

    public static function searchableColumns()
    {
        return ['id', 'client.name', 'user.name'];
    }

    public static function indexQuery(NovaRequest $request, $query)
    {
        $user = $request->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('ROLE_AGENT')) {
            return $query->where('user_id', $user->id)->orderBy('started_at', 'desc');
        }

        if ($user->hasRole('ROLE_BASE_MANAGER')) {
            $baseIds = $user->bases()->pluck('bases_commerciales.id');
            return $query->whereIn('base_commerciale_id', $baseIds)->orderBy('started_at', 'desc');
        }

        return $query->orderBy('started_at', 'desc');
    }

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable()->readonly(),

            BelongsTo::make('Client')->readonly(),
            BelongsTo::make('Commercial', 'user', User::class)->readonly(),
            BelongsTo::make('Base Commerciale', 'baseCommerciale', BaseCommerciale::class)->readonly(),
            BelongsTo::make('Zone')->readonly(),

            DateTime::make('Started At')->readonly(),
            DateTime::make('Ended At')->readonly()->nullable(),
            Number::make('Duration Seconds')->readonly()->nullable(),

            Badge::make('Status')
                ->map([
                    'started' => 'warning',
                    'completed' => 'success',
                    'aborted' => 'danger',
                    null => 'info',
                ])
                ->labels([
                    'started' => 'Started',
                    'completed' => 'Completed',
                    'aborted' => 'Aborted',
                    null => 'Planned',
                ])
                ->readonly(),

            DateTime::make('Created At')->hideFromIndex()->readonly(),

            // Relationships
            HasOne::make('Report', 'report', VisitReport::class),
            HasMany::make('Alerts', 'alerts', VisitAlert::class),
        ];
    }

    public function filters(NovaRequest $request)
    {
        return [
            new Filters\BaseCommercialeFilter,
            new Filters\ZoneFilter,
            new Filters\CommercialFilter,
            new Filters\VisitStatusFilter,
            new Filters\VisitDateRangeFilter,
        ];
    }

    public static function authorizedToCreate(Request $request)
    {
        return false;
    }

    public function authorizedToUpdate(Request $request)
    {
        return false;
    }

    public function authorizedToDelete(Request $request)
    {
        $user = $request->user();

        return $user && ($user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('super_admin'));
    }
}
