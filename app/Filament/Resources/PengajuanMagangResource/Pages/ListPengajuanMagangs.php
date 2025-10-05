<?php

namespace App\Filament\Resources\PengajuanMagangResource\Pages;

use App\Filament\Resources\PengajuanMagangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use App\Models\PengajuanMagang;

class ListPengajuanMagangs extends ListRecords
{
    protected static string $resource = PengajuanMagangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Data Pengajuan Magang';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(PengajuanMagang::count())
                ->badgeColor('gray'),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'pending'))
                ->badge(PengajuanMagang::where('status', 'pending')->count())
                ->badgeColor('warning'), // kuning

            'ditolak' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'ditolak'))
                ->badge(PengajuanMagang::where('status', 'ditolak')->count())
                ->badgeColor('danger'), // merah

            'diterima' => Tab::make('Diterima')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'diterima'))
                ->badge(PengajuanMagang::where('status', 'diterima')->count())
                ->badgeColor('success'), // hijau

            'selesai' => Tab::make('Selesai')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'selesai'))
                ->badge(PengajuanMagang::where('status', 'selesai')->count())
                ->badgeColor('info'), // biru
        ];
    }
}
