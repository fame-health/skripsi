<?php

namespace App\Filament\Resources\PengajuanMagangResource\Pages;

use App\Filament\Resources\PengajuanMagangResource;
use App\Models\PengajuanMagang;          // <-- ditambah
use App\Models\TemplateSurat;           // <-- ditambah
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;       // <-- ditambah
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Blade;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\Grid;


class ViewPengajuanMagang extends ViewRecord
{
    protected static string $resource = PengajuanMagangResource::class;

protected function getHeaderActions(): array
{
    $user    = Auth::user();
    $record  = $this->record;
    $isAdmin = $user->role === 'admin';

    $actions = [];

    // 1. Tombol Verifikasi (hanya admin + status pending)
    if ($isAdmin && $record->status === PengajuanMagang::STATUS_PENDING) {
        $actions[] = Action::make('verify')
            ->label('Verifikasi Pengajuan')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->modalHeading('Verifikasi Pengajuan Magang')
            ->modalSubmitActionLabel('Simpan')
            ->form([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        PengajuanMagang::STATUS_DITERIMA => 'Diterima',
                        PengajuanMagang::STATUS_DITOLAK => 'Ditolak',
                    ])
                    ->required()
                    ->reactive(),

                Forms\Components\Textarea::make('alasan_penolakan')
                    ->label('Alasan Penolakan')
                    ->rows(4)
                    ->visible(fn ($get) => $get('status') === PengajuanMagang::STATUS_DITOLAK)
                    ->required(fn ($get) => $get('status') === PengajuanMagang::STATUS_DITOLAK),

                // PERBAIKAN UTAMA: ganti arrow fn → fungsi biasa
                Forms\Components\Select::make('pembimbing_id')
                    ->label('Pembimbing Magang')
                    ->relationship('pembimbing', 'user_id')
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        // $record di sini adalah model Pembimbing
                        return $record->user?->name ?? 'Tanpa Nama';
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('status') === PengajuanMagang::STATUS_DITERIMA)
                    ->required(fn ($get) => $get('status') === PengajuanMagang::STATUS_DITERIMA),
            ])
            ->action(function (array $data) use ($record, $user) {
                $record->status = $data['status'];

                if ($data['status'] === PengajuanMagang::STATUS_DITOLAK) {
                    $record->alasan_penolakan = $data['alasan_penolakan'];
                }

                if ($data['status'] === PengajuanMagang::STATUS_DITERIMA) {
                    $record->pembimbing_id = $data['pembimbing_id'] ?? null;

                    // Generate QR Code
                    $validationUrl = url('/validate-internship/' . $record->id);
                    $qrCode = QrCode::create($validationUrl)->setSize(200)->setMargin(10);
                    $writer = new PngWriter();
                    $result = $writer->write($qrCode);
                    $qrPath = 'pengajuan-magang/qr-codes/qr_' . $record->id . '.png';
                    Storage::disk('public')->put($qrPath, $result->getString());

                    // Generate Surat Balasan
                    $template = TemplateSurat::where('jenis_surat', TemplateSurat::JENIS_PENERIMAAN)
                        ->where('is_active', true)
                        ->firstOrFail();

                    $nimAkhir   = substr($record->mahasiswa->nim, -3);
                    $tahun      = now()->format('Y');
                    $nomerSurat = $template->nomer_surat . '/' . $nimAkhir . '/' . $tahun;

                    $pdfData = [
                        'mahasiswa_name'     => $record->mahasiswa->user->name,
                        'nim'                => $record->mahasiswa->nim,
                        'pembimbing_name'    => $record->pembimbing?->user->name ?? '-',
                        'tanggal_mulai'      => Carbon::parse($record->tanggal_mulai)->translatedFormat('d F Y'),
                        'tanggal_selesai'    => Carbon::parse($record->tanggal_selesai)->translatedFormat('d F Y'),
                        'bidang_diminati'    => $record->bidang_diminati,
                        'qr_code_path'       => Storage::disk('public')->path($qrPath),
                        'tanggal_verifikasi' => now()->translatedFormat('d F Y'),
                        'verified_by'        => $user->name,
                        'nomer_surat'        => $nomerSurat,
                        'id_pengajuan'       => $record->id,
                    ];

                    $html    = Blade::render($template->content_template, $pdfData);
                    $pdf     = Pdf::loadHTML($html);
                    $pdfPath = 'pengajuan-magang/surat-balasan/surat_balasan_' . $record->id . '.pdf';
                    Storage::disk('public')->put($pdfPath, $pdf->output());

                    $record->surat_balasan = $pdfPath;
                }

                $record->tanggal_verifikasi = now();
                $record->verified_by        = $user->id;
                $record->save();

                Notification::make()
                    ->title('Pengajuan berhasil diverifikasi')
                    ->success()
                    ->send();
            });
    }

    // 2. Tombol Hapus
    if (
        $isAdmin ||
        ($user->role === 'mahasiswa' &&
         $user->mahasiswa &&
         $record->mahasiswa_id === $user->mahasiswa->id &&
         $record->status === PengajuanMagang::STATUS_PENDING)
    ) {
        $actions[] = DeleteAction::make()
            ->requiresConfirmation()
            ->modalHeading('Hapus Pengajuan Magang')
            ->modalDescription('Apakah Anda yakin ingin menghapus pengajuan ini? Tindakan ini tidak dapat dibatalkan.')
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title('Pengajuan berhasil dihapus')
            );
    }

    return $actions;
}






public function getInfolist(string $name = 'default'): ?Infolist
{
    $user = Auth::user();
    $isAdmin = $user->role === 'admin';
    $isMahasiswa = $user->role === 'mahasiswa' && $user->mahasiswa && $this->record->mahasiswa_id === $user->mahasiswa->id;

    return Infolist::make()
        ->record($this->record)
        ->schema([
            // Status Section dengan Visual Timeline
            Section::make()
                ->schema([
                    TextEntry::make('status')
                        ->label('Status Pengajuan')
                        ->badge()
                        ->size('lg')
                        ->formatStateUsing(fn(string $state): string => match ($state) {
                            PengajuanMagang::STATUS_PENDING => 'Sedang Diproses',
                            PengajuanMagang::STATUS_DITERIMA => 'Diterima',
                            PengajuanMagang::STATUS_DITOLAK => 'Ditolak',
                            PengajuanMagang::STATUS_SELESAI => 'Selesai',
                        })
                        ->color(fn(string $state): string => match ($state) {
                            PengajuanMagang::STATUS_PENDING => 'warning',
                            PengajuanMagang::STATUS_DITERIMA => 'success',
                            PengajuanMagang::STATUS_DITOLAK => 'danger',
                            PengajuanMagang::STATUS_SELESAI => 'info',
                        })
                        ->icon(fn(string $state): string => match ($state) {
                            PengajuanMagang::STATUS_PENDING => 'heroicon-o-clock',
                            PengajuanMagang::STATUS_DITERIMA => 'heroicon-o-check-circle',
                            PengajuanMagang::STATUS_DITOLAK => 'heroicon-o-x-circle',
                            PengajuanMagang::STATUS_SELESAI => 'heroicon-o-academic-cap',
                        })
                        ->weight(FontWeight::Bold),

                    TextEntry::make('tanggal_verifikasi')
                        ->label('Tanggal Verifikasi')
                        ->icon('heroicon-o-calendar')
                        ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->translatedFormat('l, d F Y - H:i') . ' WIB' : 'Menunggu verifikasi')
                        ->visible(fn() => $this->record->tanggal_verifikasi || $isAdmin)
                        ->weight(FontWeight::Medium)
                        ->color('gray'),

                    TextEntry::make('verified_by')
                        ->label('Diverifikasi Oleh')
                        ->icon('heroicon-o-user-circle')
                        ->getStateUsing(fn($record) => $record->verifikator?->name ?? 'Belum diverifikasi')
                        ->visible(fn() => $this->record->verified_by || $isAdmin)
                        ->weight(FontWeight::Medium)
                        ->color('gray'),

                    TextEntry::make('alasan_penolakan')
                        ->label('Keterangan')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->visible(fn() => $this->record->status === PengajuanMagang::STATUS_DITOLAK && ($isAdmin || $isMahasiswa))
                        ->weight(FontWeight::Medium)
                        ->color('danger')
                        ->columnSpanFull()
                        ->formatStateUsing(fn($state) => $state ?: 'Tidak ada keterangan')
                        ->extraAttributes([
                            'class' => 'bg-red-50 border border-red-200 rounded-lg p-3',
                        ]),
                ])
                ->heading('Status Pengajuan')
                ->description(fn() => $this->getStatusDescription())
                ->icon('heroicon-o-clipboard-document-check')
                ->columns(3) // Grid 3 kolom untuk Section
                ->compact(),

            // Grid untuk Section Informasi Mahasiswa, Periode Magang, dan Dokumen
            Grid::make(3)
                ->schema([
                    // Informasi Mahasiswa
                    Section::make('Informasi Mahasiswa')
                        ->icon('heroicon-o-user')
                        ->description('Data pribadi dan akademik mahasiswa')
                        ->schema([
                            TextEntry::make('mahasiswa.nim')
                                ->label('Nomor Induk Mahasiswa')
                                ->icon('heroicon-o-identification')
                                ->weight(FontWeight::Bold)
                                ->color('primary')
                                ->copyable()
                                ->copyMessage('NIM berhasil disalin!')
                                ->copyMessageDuration(1500),

                            TextEntry::make('mahasiswa.user.name')
                                ->label('Nama Lengkap')
                                ->icon('heroicon-o-user')
                                ->weight(FontWeight::Bold)
                                ->color('primary')
                                ->size('lg'),

                            TextEntry::make('mahasiswa.user.email')
                                ->label('Email')
                                ->icon('heroicon-o-envelope')
                                ->copyable()
                                ->copyMessage('Email berhasil disalin!')
                                ->visible($isAdmin),

                            TextEntry::make('pembimbing.user.name')
                                ->label('Pembimbing Magang')
                                ->icon('heroicon-o-academic-cap')
                                ->default('Belum ditentukan')
                                ->placeholder('Menunggu penugasan pembimbing')
                                ->visible($isAdmin || ($isMahasiswa && $this->record->pembimbing_id))
                                ->weight(FontWeight::SemiBold)
                                ->badge()
                                ->color(fn($state) => $state === 'Belum ditentukan' ? 'gray' : 'success'),
                        ])
                        ->collapsible()
                        ->columnSpan(1),

                    // Detail Periode Magang
                    Section::make('Periode Magang')
                        ->icon('heroicon-o-calendar-days')
                        ->description('Jadwal dan durasi pelaksanaan magang')
                        ->schema([
                            TextEntry::make('tanggal_mulai')
                                ->label('Tanggal Mulai')
                                ->icon('heroicon-o-play-circle')
                                ->date('l, d F Y')
                                ->weight(FontWeight::Bold)
                                ->color('success'),

                            TextEntry::make('tanggal_selesai')
                                ->label('Tanggal Selesai')
                                ->icon('heroicon-o-flag')
                                ->date('l, d F Y')
                                ->weight(FontWeight::Bold)
                                ->color('danger'),

                            TextEntry::make('durasi_magang')
                                ->label('Total Durasi')
                                ->icon('heroicon-o-clock')
                                ->suffix(' minggu')
                                ->badge()
                                ->color('info')
                                ->weight(FontWeight::Bold),

                            TextEntry::make('bidang_diminati')
                                ->label('Bidang/Divisi')
                                ->icon('heroicon-o-briefcase')
                                ->badge()
                                ->color('warning')
                                ->weight(FontWeight::SemiBold)
                                ->size('lg'),
                        ])
                        ->collapsible()
                        ->columnSpan(1),

                    // Dokumen Pendukung
                    Section::make('Dokumen Pendukung')
                        ->icon('heroicon-o-document-text')
                        ->description('Berkas dan dokumen yang dilampirkan')
                        ->schema([
                            TextEntry::make('surat_permohonan')
                                ->label('Surat Permohonan')
                                ->icon('heroicon-o-document-text')
                                ->formatStateUsing(fn($state) => $state ? 'Tersedia' : 'Tidak tersedia')
                                ->badge()
                                ->color(fn($state) => $state ? 'success' : 'gray')
                                ->url(fn($state) => $state ? asset('storage/' . $state) : null)
                                ->openUrlInNewTab()
                                ->suffixAction(
                                    \Filament\Infolists\Components\Actions\Action::make('download_surat_permohonan')
                                        ->label('Unduh')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->color('primary')
                                        ->button()
                                        ->url(fn($record) => $record->surat_permohonan ? asset('storage/' . $record->surat_permohonan) : null)
                                        ->openUrlInNewTab()
                                        ->visible(fn($record) => $record->surat_permohonan)
                                ),

                            TextEntry::make('ktm')
                                ->label('Kartu Tanda Mahasiswa')
                                ->icon('heroicon-o-identification')
                                ->formatStateUsing(fn($state) => $state ? 'Tersedia' : 'Tidak tersedia')
                                ->badge()
                                ->color(fn($state) => $state ? 'success' : 'gray')
                                ->url(fn($state) => $state ? asset('storage/' . $state) : null)
                                ->openUrlInNewTab()
                                ->suffixAction(
                                    \Filament\Infolists\Components\Actions\Action::make('download_ktm')
                                        ->label('Unduh')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->color('success')
                                        ->button()
                                        ->url(fn($record) => $record->ktm ? asset('storage/' . $record->ktm) : null)
                                        ->openUrlInNewTab()
                                        ->visible(fn($record) => $record->ktm)
                                ),

                            TextEntry::make('surat_balasan')
                                ->label('Surat Penerimaan')
                                ->icon('heroicon-o-document-check')
                                ->formatStateUsing(fn($state) => $state ? 'Tersedia' : 'Belum tersedia')
                                ->badge()
                                ->color(fn($state) => $state ? 'success' : 'gray')
                                ->url(fn($state) => $state ? asset('storage/' . $state) : null)
                                ->openUrlInNewTab()
                                ->suffixAction(
                                    \Filament\Infolists\Components\Actions\Action::make('download_surat_balasan')
                                        ->label('Unduh')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->color('info')
                                        ->button()
                                        ->url(fn($record) => $record->surat_balasan ? asset('storage/' . $record->surat_balasan) : null)
                                        ->openUrlInNewTab()
                                        ->visible(fn($record) => $record->surat_balasan)
                                )
                                ->visible($isAdmin || ($isMahasiswa && $this->record->isDiterima())),

                            TextEntry::make('final_laporan')
                                ->label('Laporan Akhir Magang')
                                ->icon('heroicon-o-document-chart-bar')
                                ->formatStateUsing(fn($state) => $state ? 'Tersedia' : 'Belum tersedia')
                                ->badge()
                                ->color(fn($state) => $state ? 'success' : 'gray')
                                ->url(fn($state) => $state ? asset('storage/' . $state) : null)
                                ->openUrlInNewTab()
                                ->suffixAction(
                                    \Filament\Infolists\Components\Actions\Action::make('download_laporan')
                                        ->label('Unduh')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->color('warning')
                                        ->button()
                                        ->url(fn($record) => $record->final_laporan ? asset('storage/' . $record->final_laporan) : null)
                                        ->openUrlInNewTab()
                                        ->visible(fn($record) => $record->final_laporan)
                                )
                                ->visible($isAdmin),

                            TextEntry::make('sertifikat')
                                ->label('Sertifikat Magang')
                                ->icon('heroicon-o-trophy')
                                ->formatStateUsing(fn($state) => $state ? 'Tersedia' : 'Belum tersedia')
                                ->badge()
                                ->color(fn($state) => $state ? 'success' : 'gray')
                                ->url(fn($state) => $state ? asset('storage/' . $state) : null)
                                ->openUrlInNewTab()
                                ->suffixAction(
                                    \Filament\Infolists\Components\Actions\Action::make('download_sertifikat')
                                        ->label('Unduh')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->color('danger')
                                        ->button()
                                        ->url(fn($record) => $record->sertifikat ? asset('storage/' . $record->sertifikat) : null)
                                        ->openUrlInNewTab()
                                        ->visible(fn($record) => $record->sertifikat)
                                )
                                ->visible($isAdmin),
                        ])
                        ->collapsible()
                        ->columnSpan(1),
                ]),
        ]);
}

protected function getStatusDescription(): string
{
    return match ($this->record->status) {
        PengajuanMagang::STATUS_PENDING => 'Pengajuan sedang dalam proses peninjauan oleh admin',
        PengajuanMagang::STATUS_DITERIMA => 'Selamat! Pengajuan Anda telah disetujui',
        PengajuanMagang::STATUS_DITOLAK => 'Pengajuan tidak dapat diproses saat ini',
        PengajuanMagang::STATUS_SELESAI => 'Magang telah selesai dilaksanakan',
        default => 'Status tidak diketahui',
    };
}


}
