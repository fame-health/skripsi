<?php

namespace App\Filament\Resources\LaporanMingguanResource\Pages;

use App\Filament\Resources\LaporanMingguanResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Actions;
use App\Models\LaporanMingguan;

class ListLaporanMingguans extends ListRecords
{
    protected static string $resource = LaporanMingguanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => static::$resource::canCreate()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn ($query) => $query->where('status_approve', 0))
                ->badge(LaporanMingguan::where('status_approve', 0)->count())
                ->badgeColor('warning'), // kuning

            'setujui' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn ($query) => $query->where('status_approve', 1))
                ->badge(LaporanMingguan::where('status_approve', 1)->count())
                ->badgeColor('success'), // hijau

            'ditolak' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn ($query) => $query->where('status_approve', 2))
                ->badge(LaporanMingguan::where('status_approve', 2)->count())
                ->badgeColor('danger'), // merah

            'all' => Tab::make('Semua')
                ->badge(LaporanMingguan::count())
                ->badgeColor('gray'), // abu
        ];
    }

    public function getDefaultActiveTab(): ?string
    {
        return 'pending';
    }

    public function getTitle(): string
    {
        return match ($this->activeTab) {
            'pending' => 'Laporan Pending',
            'setujui' => 'Laporan Disetujui',
            'ditolak' => 'Laporan Ditolak',
            'all' => 'Semua Laporan',
            default => 'Data Laporan Mingguan',
        };
    }
}
