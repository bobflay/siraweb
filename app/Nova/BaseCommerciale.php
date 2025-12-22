<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class BaseCommerciale extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\BaseCommerciale>
     */
    public static $model = \App\Models\BaseCommerciale::class;

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
        'id', 'code', 'name', 'city',
    ];

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Commercial';

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

            // Identity & Business Keys
            Text::make('Code', 'code')
                ->sortable()
                ->rules('required', 'unique:bases_commerciales,code,{{resourceId}}')
                ->help('Ex: BASE_ABJ_NORD'),

            Text::make('Name', 'name')
                ->sortable()
                ->rules('required'),

            Textarea::make('Description', 'description')
                ->rows(3)
                ->nullable(),

            Boolean::make('Active', 'is_active')
                ->sortable(),

            new Panel('Géographie', [
                Text::make('City', 'city')
                    ->sortable()
                    ->rules('required'),

                Text::make('Region', 'region')
                    ->nullable(),

                Number::make('Latitude', 'latitude')
                    ->step(0.0000001)
                    ->nullable()
                    ->help('Ex: 5.3599517'),

                Number::make('Longitude', 'longitude')
                    ->step(0.0000001)
                    ->nullable()
                    ->help('Ex: -4.0082563'),
            ]),

            new Panel('Règles Commerciales', [
                Select::make('Default Currency', 'default_currency')
                    ->options([
                        'XOF' => 'XOF (Franc CFA)',
                        'EUR' => 'EUR (Euro)',
                        'USD' => 'USD (Dollar)',
                    ])
                    ->default('XOF')
                    ->rules('required'),

                Number::make('Default Tax Rate (%)', 'default_tax_rate')
                    ->step(0.01)
                    ->default(0.00)
                    ->help('Ex: 18.00 pour 18%'),

                Boolean::make('Allow Discount', 'allow_discount')
                    ->default(true),

                Number::make('Max Discount (%)', 'max_discount_percent')
                    ->step(0.01)
                    ->default(0.00)
                    ->help('Ex: 15.00 pour 15%'),

                Text::make('Order Cutoff Time', 'order_cutoff_time')
                    ->nullable()
                    ->help('Format: HH:MM:SS (ex: 18:00:00)'),
            ]),

            BelongsToMany::make('Users')
                ->searchable(),
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
}
