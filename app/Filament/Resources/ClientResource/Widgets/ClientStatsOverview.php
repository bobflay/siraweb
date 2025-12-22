<?php

namespace App\Filament\Resources\ClientResource\Widgets;

use App\Models\Client;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Clients', Client::count())
                ->description('All registered clients')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Active Clients', Client::where('is_active', true)->count())
                ->description('Currently active')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Inactive Clients', Client::where('is_active', false)->count())
                ->description('Inactive clients')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('High Potential (A)', Client::where('potential', 'A')->count())
                ->description('A-grade potential')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('Medium Potential (B)', Client::where('potential', 'B')->count())
                ->description('B-grade potential')
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),

            Stat::make('Low Potential (C)', Client::where('potential', 'C')->count())
                ->description('C-grade potential')
                ->descriptionIcon('heroicon-m-star')
                ->color('gray'),
        ];
    }
}
