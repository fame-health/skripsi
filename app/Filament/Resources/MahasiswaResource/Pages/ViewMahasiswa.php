<?php

namespace App\Filament\Resources\MahasiswaResource\Pages;

use App\Filament\Resources\MahasiswaResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Carbon\Carbon;

class ViewMahasiswa extends ViewRecord
{
    protected static string $resource = MahasiswaResource::class;

    protected static ?string $title = 'Detail Mahasiswa';

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
                ->modalSubmitActionLabel('Ya, Hapus')
                ->action(fn () => $this->record->delete())
                ->successRedirectUrl(static::getResource()::getUrl('index')),

            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Grid responsif: Desktop 3 kolom (2:1), Mobile 1 kolom
                Grid::make([
                    'default' => 1,
                    'md' => 3,
                ])
                ->schema([

                    /* ================================
                     * KOLOM KIRI: DATA AKADEMIK
                     * ================================ */
                    Section::make('Data Akademik')
                        ->icon('heroicon-o-academic-cap')
                        ->description('Informasi akademik mahasiswa')
                        ->schema([
                            TextEntry::make('nama_mahasiswa')
                                ->label('Nama Mahasiswa')
                                ->getStateUsing(fn ($record) => $record->user?->name ?? '-')
                                ->weight(FontWeight::Bold)
                                ->icon('heroicon-m-user')
                                ->iconColor('primary'),

                            TextEntry::make('nim')
                                ->label('NIM / NIP Siswa')
                                ->weight(FontWeight::Medium)
                                ->icon('heroicon-m-identification')
                                ->copyable()
                                ->copyMessage('NIM berhasil disalin')
                                ->copyMessageDuration(1500),

                            TextEntry::make('universitas')
                                ->label('Universitas / Sekolah')
                                ->weight(FontWeight::Medium)
                                ->icon('heroicon-m-building-library'),

                            TextEntry::make('fakultas')
                                ->label('Fakultas')
                                ->weight(FontWeight::Medium)
                                ->icon('heroicon-m-building-office-2'),

                            TextEntry::make('jurusan')
                                ->label('Jurusan / Program Studi')
                                ->weight(FontWeight::Medium)
                                ->icon('heroicon-m-book-open'),

                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('semester')
                                        ->label('Semester / Kelas')
                                        ->weight(FontWeight::Medium)
                                        ->badge()
                                        ->color('info'),

                                    TextEntry::make('ipk')
                                        ->label('IPK / Nilai')
                                        ->weight(FontWeight::Medium)
                                        ->badge()
                                        ->color(fn ($state) => match (true) {
                                            $state >= 3.5 => 'success',
                                            $state >= 3.0 => 'warning',
                                            $state >= 2.5 => 'info',
                                            default => 'danger',
                                        }),
                                ]),
                        ])
                        ->columnSpan([
                            'default' => 1,
                            'md' => 2,
                        ])
                        ->collapsible(),

                    /* ================================
                     * KOLOM KANAN: DATA PRIBADI
                     * ================================ */
                    Section::make('Data Pribadi')
                        ->icon('heroicon-o-user-circle')
                        ->description('Informasi pribadi mahasiswa')
                        ->schema([
                            TextEntry::make('jenis_kelamin')
                                ->label('Jenis Kelamin')
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    'L' => 'Laki-laki',
                                    'P' => 'Perempuan',
                                    default => 'N/A',
                                })
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'L' => 'info',
                                    'P' => 'danger',
                                    default => 'gray',
                                })
                                ->icon(fn ($state) => match ($state) {
                                    'L' => 'heroicon-m-user',
                                    'P' => 'heroicon-m-user',
                                    default => 'heroicon-m-question-mark-circle',
                                }),

                            TextEntry::make('tanggal_lahir')
                                ->label('Tanggal Lahir')
                                ->formatStateUsing(fn ($state) => $state
                                    ? Carbon::parse($state)->translatedFormat('d F Y')
                                    : 'N/A'
                                )
                                ->weight(FontWeight::Medium)
                                ->icon('heroicon-m-cake'),

                            TextEntry::make('umur')
                                ->label('Umur')
                                ->getStateUsing(fn ($record) => $record->tanggal_lahir
                                    ? Carbon::parse($record->tanggal_lahir)->age . ' tahun'
                                    : 'N/A'
                                )
                                ->weight(FontWeight::Medium)
                                ->icon('heroicon-m-calendar-days')
                                ->badge()
                                ->color('gray'),

                            TextEntry::make('alamat')
                                ->label('Domisili')
                                ->formatStateUsing(fn ($state) => $state ?: 'N/A')
                                ->weight(FontWeight::Medium)
                                ->icon('heroicon-m-map-pin')
                                ->columnSpanFull(),
                        ])
                        ->columnSpan([
                            'default' => 1,
                            'md' => 1,
                        ])
                        ->collapsible(),
                ]),

                /* ================================
                 * SECTION TAMBAHAN (FULL WIDTH)
                 * ================================ */
                Section::make('Informasi Tambahan')
                    ->icon('heroicon-o-information-circle')
                    ->description('Data pendukung lainnya')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Terdaftar Sejak')
                                    ->dateTime('d F Y, H:i')
                                    ->icon('heroicon-m-clock')
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('updated_at')
                                    ->label('Terakhir Diperbarui')
                                    ->dateTime('d F Y, H:i')
                                    ->icon('heroicon-m-arrow-path')
                                    ->badge()
                                    ->color('warning')
                                    ->since(),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->getStateUsing(fn () => 'Aktif')
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-m-check-circle'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
