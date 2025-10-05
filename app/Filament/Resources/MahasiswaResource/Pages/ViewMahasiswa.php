<?php

namespace App\Filament\Resources\MahasiswaResource\Pages;

use App\Filament\Resources\MahasiswaResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class ViewMahasiswa extends ViewRecord
{
    protected static string $resource = MahasiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Edit Data')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->url(fn () => static::getResource()::getUrl('edit', ['record' => $this->record])),
            Action::make('delete')
                ->label('Hapus Data')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Data Mahasiswa')
                ->modalDescription('Apakah Anda yakin ingin menghapus data ini?')
                ->modalSubmitActionLabel('Ya, Hapus'),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getInfolist(string $name = 'default'): ?Infolist
    {
        return Infolist::make()
            ->record($this->record)
            ->schema([
                Section::make('Data Akademik')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        TextEntry::make('nama_mahasiswa')
                            ->label('Nama Mahasiswa')
                            ->getStateUsing(fn ($record) => $record->user?->name ?? '-')
                            ->weight(FontWeight::Medium),
                        TextEntry::make('nim')
                            ->label('NIM / NIP Siswa')
                            ->weight(FontWeight::Medium),
                        TextEntry::make('universitas')
                            ->label('Universitas / Sekolah')
                            ->weight(FontWeight::Medium),
                        TextEntry::make('fakultas')
                            ->label('Fakultas')
                            ->weight(FontWeight::Medium),
                        TextEntry::make('jurusan')
                            ->label('Jurusan / Program Studi')
                            ->weight(FontWeight::Medium),
                        TextEntry::make('semester')
                            ->label('Semester / Kelas')
                            ->weight(FontWeight::Medium),
                        TextEntry::make('ipk')
                            ->label('IPK / Nilai')
                            ->weight(FontWeight::Medium),
                    ])
                    ->columns(2)
                    ->extraAttributes(['class' => 'shadow-md rounded-lg border border-gray-200']),

                Section::make('Data Pribadi')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('alamat')
                            ->label('Domisili')
                            ->formatStateUsing(fn ($state) => $state ?: 'N/A')
                            ->weight(FontWeight::Medium),
                        TextEntry::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d/m/Y') : 'N/A')
                            ->weight(FontWeight::Medium),
                        TextEntry::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                                default => 'N/A',
                            })
                            ->weight(FontWeight::Medium),
                    ])
                    ->columns(1)
                    ->extraAttributes(['class' => 'shadow-md rounded-lg border border-gray-200 mt-6']),
            ]);
    }
}
