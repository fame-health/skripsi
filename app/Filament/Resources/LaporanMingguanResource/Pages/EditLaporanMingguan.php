<?php

namespace App\Filament\Resources\LaporanMingguanResource\Pages;

use App\Filament\Resources\LaporanMingguanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaporanMingguan extends EditRecord
{
    protected static string $resource = LaporanMingguanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
