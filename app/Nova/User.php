<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Badge;

use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Database\Eloquent\Builder;

class User extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\User::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * Get the search result subtitle for the resource.
     *
     * @return string
     */
    public function subtitle()
    {
        // Check if the user is active
        $isActive = $this->isActive();
    
        // Use Unicode characters for the dots
        $greenDot = "\u{1F7E2}"; // Green dot
        $redDot = "\u{1F534}";   // Red dot
    
        // Return the subtitle with the appropriate dot
        return $isActive ? "{$greenDot} Active" : "{$redDot} Inactive";
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'email',
    ];

    public static $group = 'Clients';


    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),

            Gravatar::make()->maxWidth(50),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:254')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}'),

            Text::make('Phone')
                ->sortable()
                ->rules('required', 'max:15')
                ->creationRules('unique:users,phone')
                ->updateRules('unique:users,phone,{{resourceId}}'),

            Date::make('Date of Birth', 'dob')
                ->sortable()
                ->rules('required', 'date'),

            Image::make('Photo', 'photo')
                ->store(function (Request $request, $model) {
                    if (!$request->hasFile('photo')) {
                        return null;
                    }

                    $file = $request->file('photo');

                    if (!$file->isValid()) {
                        \Log::error('Nova Photo - File is not valid', ['error' => $file->getErrorMessage()]);
                        return null;
                    }

                    // Delete old photo if exists
                    if ($model->photo) {
                        // Handle both old format (photos/filename) and new format (filename)
                        $oldFilename = str_starts_with($model->photo, 'photos/')
                            ? substr($model->photo, 7)
                            : $model->photo;
                        $oldPath = public_path('photos/' . $oldFilename);
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }

                    // Generate unique filename
                    $filename = uniqid() . '.' . $file->getClientOriginalExtension();

                    // Store directly in public/photos
                    try {
                        $destinationPath = public_path('photos');

                        // Ensure directory exists
                        if (!is_dir($destinationPath)) {
                            @mkdir($destinationPath, 0755, true);
                        }

                        $file->move($destinationPath, $filename);

                        \Log::info('Nova Photo - File stored successfully', [
                            'path' => 'photos/' . $filename
                        ]);

                        return $filename;
                    } catch (\Exception $e) {
                        \Log::error('Nova Photo - Storage exception', [
                            'message' => $e->getMessage(),
                        ]);
                        return null;
                    }
                })
                ->delete(function (Request $request, $model) {
                    if ($model->photo) {
                        // Handle both old format (photos/filename) and new format (filename)
                        $filename = str_starts_with($model->photo, 'photos/')
                            ? substr($model->photo, 7)
                            : $model->photo;
                        $path = public_path('photos/' . $filename);
                        if (file_exists($path)) {
                            @unlink($path);
                            \Log::info('Nova Photo - Deleted', ['path' => $model->photo]);
                        }
                    }
                    return true;
                })
                ->preview(function ($value, $disk) {
                    if (!$value) return null;
                    // Handle both old format (photos/filename) and new format (filename)
                    $path = str_starts_with($value, 'photos/') ? $value : 'photos/' . $value;
                    return asset($path);
                })
                ->thumbnail(function ($value, $disk) {
                    if (!$value) return null;
                    // Handle both old format (photos/filename) and new format (filename)
                    $path = str_starts_with($value, 'photos/') ? $value : 'photos/' . $value;
                    return asset($path);
                })
                ->rules('nullable', 'image', 'mimes:jpg,jpeg,png,heic,webp')
                ->prunable(),

            Badge::make('Role', function () {
                return $this->roles->pluck('name')->implode(', ') ?: 'No Role';
            })->map([
                'Super Admin' => 'danger',
                'Direction Commerciale' => 'warning',
                'Responsable Base' => 'info',
                'Commercial Terrain' => 'success',
                'Direction Financière' => 'warning',
                'No Role' => 'danger',
            ])->sortable(false),

            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', 'string', 'min:8')
                ->updateRules('nullable', 'string', 'min:8'),

            BelongsToMany::make('Roles')
                ->searchable(),

            BelongsToMany::make('Bases Commerciales', 'basesCommerciales', BaseCommerciale::class)
                ->searchable(),

            BelongsToMany::make('Zones')
                ->searchable(),

            // Assigned Clients (for commercial users)
            BelongsToMany::make('Clients Assignés', 'assignedClients', Client::class)
                ->fields(function () {
                    return [
                        Text::make('Role', 'role')->readonly(),
                        Text::make('Assigned At', 'assigned_at')
                            ->displayUsing(function ($value) {
                                return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i') : null;
                            })
                            ->readonly(),
                        Badge::make('Active', 'active')
                            ->map([
                                true => 'success',
                                false => 'danger',
                            ])
                            ->labels([
                                true => 'Active',
                                false => 'Inactive',
                            ]),
                    ];
                })
                ->searchable()
                ->singularLabel('Client')
                ->help('List of clients assigned to this commercial user')
                ->canSee(function ($request) {
                    // Only show for users with commercial role
                    return $this->resource->hasRole('ROLE_AGENT') || $this->resource->hasRole('commercial');
                }),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
