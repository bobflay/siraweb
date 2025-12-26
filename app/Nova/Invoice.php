<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Http\Requests\NovaRequest;

class Invoice extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Invoice>
     */
    public static $model = \App\Models\Invoice::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'invoice_number';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'invoice_number',
        'supplier',
        'client_name',
        'client_code',
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
            ID::make()->sortable(),

            // Relationships
            BelongsTo::make('User', 'user', User::class)
                ->sortable()
                ->readonly(),

            BelongsTo::make('Visit Photo', 'visitPhoto', VisitPhoto::class)
                ->nullable()
                ->readonly()
                ->hideFromIndex(),

            // Status
            Badge::make('Status', 'status')
                ->map([
                    'pending' => 'warning',
                    'delivered' => 'success',
                    'cancelled' => 'danger',
                ])
                ->labels([
                    'pending' => 'En attente',
                    'delivered' => 'Livré',
                    'cancelled' => 'Annulé',
                ])
                ->sortable(),

            DateTime::make('Delivered At', 'delivered_at')
                ->readonly()
                ->hideFromIndex(),

            // Invoice Info
            Text::make('Supplier', 'supplier')
                ->sortable()
                ->readonly(),

            Text::make('Document Type', 'document_type')
                ->readonly()
                ->hideFromIndex(),

            Text::make('Invoice Number', 'invoice_number')
                ->sortable()
                ->readonly(),

            Date::make('Invoice Date', 'invoice_date')
                ->sortable()
                ->readonly(),

            Text::make('Print Time', 'print_time')
                ->readonly()
                ->hideFromIndex(),

            Text::make('Operator', 'operator')
                ->readonly()
                ->hideFromIndex(),

            // Client Info
            Text::make('Client Name', 'client_name')
                ->sortable()
                ->readonly(),

            Text::make('Client Code', 'client_code')
                ->readonly()
                ->hideFromIndex(),

            Text::make('Client Reference', 'client_reference')
                ->readonly()
                ->hideFromIndex(),

            // Totals
            Currency::make('Total HT', 'total_ht')
                ->currency('XOF')
                ->readonly()
                ->hideFromIndex(),

            Currency::make('Total Tax', 'total_tax')
                ->currency('XOF')
                ->readonly()
                ->hideFromIndex(),

            Currency::make('Total TTC', 'total_ttc')
                ->currency('XOF')
                ->sortable()
                ->readonly(),

            Currency::make('Port HT', 'port_ht')
                ->currency('XOF')
                ->readonly()
                ->hideFromIndex(),

            Currency::make('Net to Pay', 'net_to_pay')
                ->currency('XOF')
                ->readonly()
                ->hideFromIndex(),

            Text::make('Net to Pay (Words)', 'net_to_pay_words')
                ->readonly()
                ->hideFromIndex(),

            // Logistics
            Number::make('Packages Count', 'packages_count')
                ->readonly()
                ->hideFromIndex(),

            Number::make('Total Weight (kg)', 'total_weight')
                ->step(0.01)
                ->readonly()
                ->hideFromIndex(),

            // Source Image
            Image::make('Source Image', 'source_image_path')
                ->disk('public')
                ->readonly()
                ->hideFromIndex(),

            // Taxes (JSON)
            Code::make('Taxes', 'taxes')
                ->json()
                ->readonly()
                ->hideFromIndex(),

            // Raw OCR Data
            Code::make('Raw OCR Data', 'raw_ocr_data')
                ->json()
                ->readonly()
                ->hideFromIndex(),

            // Invoice Items
            HasMany::make('Items', 'items', InvoiceItem::class),

            // Invoice Photos
            MorphMany::make('Photos', 'photos', VisitPhoto::class),

            // Timestamps
            DateTime::make('Created At', 'created_at')
                ->readonly()
                ->sortable(),

            DateTime::make('Updated At', 'updated_at')
                ->readonly()
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function authorizedToCreate(Request $request)
    {
        return false; // Invoices are created via OCR only
    }

    /**
     * Determine if the current user can update the given resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function authorizedToUpdate(Request $request)
    {
        return false; // Invoices are read-only
    }
}
