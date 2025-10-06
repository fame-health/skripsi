<?php

namespace App\Filament\Resources\PengajuanMagangResource\Pages;

use App\Filament\Resources\PengajuanMagangResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Filament\Forms;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Blade;
use Filament\Notifications\Notification;

class ViewPengajuanMagang extends ViewRecord
{
    protected static string $resource = PengajuanMagangResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $isMahasiswa = $user->role === 'mahasiswa' &&
                       $user->mahasiswa &&
                       $this->record->mahasiswa_id === $user->mahasiswa->id;

        // Action Verifikasi untuk Admin
        if ($isAdmin && $this->record->status === \App\Models\PengajuanMagang::STATUS_PENDING) {
            $actions[] = Action::make('verify')
                ->label('Verifikasi Pengajuan')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->size('lg')
                ->form([
                    Forms\Components\Grid::make(1)
                        ->schema([
                            Forms\Components\Section::make('Status Verifikasi')
                                ->description('Pilih status dan lengkapi informasi yang diperlukan')
                                ->icon('heroicon-o-clipboard-document-check')
                                ->schema([
                                    Forms\Components\Select::make('status')
                                        ->label('Status Verifikasi')
                                        ->options([
                                            \App\Models\PengajuanMagang::STATUS_DITERIMA => 'Terima Pengajuan',
                                            \App\Models\PengajuanMagang::STATUS_DITOLAK => 'Tolak Pengajuan',
                                        ])
                                        ->required()
                                        ->reactive()
                                        ->native(false)
                                        ->helperText('Pilih status verifikasi untuk pengajuan ini'),

                                    Forms\Components\Select::make('pembimbing_id')
                                        ->label('Pembimbing Magang')
                                        ->relationship('pembimbing', 'user_id')
                                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name ?? 'Tanpa Nama')
                                        ->searchable()
                                        ->preload()
                                        ->required(fn ($get) => $get('status') === \App\Models\PengajuanMagang::STATUS_DITERIMA)
                                        ->visible(fn ($get) => $get('status') === \App\Models\PengajuanMagang::STATUS_DITERIMA)
                                        ->helperText('Pilih pembimbing yang akan membimbing mahasiswa selama magang')
                                        ->native(false),

                                    Forms\Components\Textarea::make('alasan_penolakan')
                                        ->label('Alasan Penolakan')
                                        ->rows(5)
                                        ->visible(fn ($get) => $get('status') === \App\Models\PengajuanMagang::STATUS_DITOLAK)
                                        ->required(fn ($get) => $get('status') === \App\Models\PengajuanMagang::STATUS_DITOLAK)
                                        ->placeholder('Jelaskan alasan penolakan secara detail dan konstruktif...')
                                        ->helperText('Berikan alasan yang jelas untuk membantu mahasiswa memahami penolakan'),
                                ])
                                ->collapsible(),
                        ]),
                ])
                ->action(function (array $data) use ($user) {
                    try {
                        $this->record->status = $data['status'];
                        $this->record->alasan_penolakan =
                            $data['status'] === \App\Models\PengajuanMagang::STATUS_DITOLAK
                                ? $data['alasan_penolakan']
                                : null;
                        $this->record->tanggal_verifikasi = Carbon::now();
                        $this->record->verified_by = $user->id;

                        if ($data['status'] === \App\Models\PengajuanMagang::STATUS_DITERIMA) {
                            if (isset($data['pembimbing_id'])) {
                                $this->record->pembimbing_id = $data['pembimbing_id'];
                            }

                            // Generate QR Code
                            $validationUrl = url('/validate-internship/' . $this->record->id);
                            $qrCode = QrCode::create($validationUrl)
                                ->setSize(200)
                                ->setMargin(10);
                            $writer = new PngWriter();
                            $qrCodeImage = $writer->write($qrCode);
                            $qrCodePath = 'pengajuan-magang/qr-codes/qr_' . $this->record->id . '.png';
                            Storage::disk('public')->put($qrCodePath, $qrCodeImage->getString());

                            // Ambil template aktif
                            $template = \App\Models\TemplateSurat::where('jenis_surat', \App\Models\TemplateSurat::JENIS_PENERIMAAN)
                                ->where('is_active', true)
                                ->first();

                            if (!$template) {
                                throw new \Exception('Template surat penerimaan tidak ditemukan. Silakan aktifkan template terlebih dahulu.');
                            }

                            // Siapkan data untuk template
                            $pdfData = [
                                'mahasiswa_name' => $this->record->mahasiswa->user->name,
                                'nim' => $this->record->mahasiswa->nim,
                                'pembimbing_name' => $this->record->pembimbing->user->name ?? 'N/A',
                                'tanggal_mulai' => Carbon::parse($this->record->tanggal_mulai)->format('d F Y'),
                                'tanggal_selesai' => Carbon::parse($this->record->tanggal_selesai)->format('d F Y'),
                                'bidang_diminati' => $this->record->bidang_diminati,
                                'qr_code_path' => Storage::disk('public')->path($qrCodePath),
                                'tanggal_verifikasi' => now()->format('d F Y'),
                                'verified_by' => $user->name,
                                'id_pengajuan' => $this->record->id,
                            ];

                            // Render Blade template
                            $renderedContent = Blade::render($template->content_template, $pdfData);

                            // Generate PDF
                            $pdf = Pdf::loadHTML($renderedContent)
                                ->setPaper('a4', 'portrait')
                                ->setOptions([
                                    'isHtml5ParserEnabled' => true,
                                    'isPhpEnabled' => true,
                                    'defaultFont' => 'Arial'
                                ]);

                            $pdfPath = 'pengajuan-magang/surat-balasan/surat_balasan_' . $this->record->id . '.pdf';
                            Storage::disk('public')->put($pdfPath, $pdf->output());

                            // Simpan path ke database
                            $this->record->surat_balasan = $pdfPath;
                        }

                        $this->record->save();

                        Notification::make()
                            ->title('Verifikasi Berhasil!')
                            ->body('Pengajuan magang telah berhasil diverifikasi.')
                            ->success()
                            ->duration(5000)
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'alasan_penolakan',
                            'tanggal_verifikasi',
                            'verified_by',
                            'surat_balasan',
                            'pembimbing_id',
                        ]);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Verifikasi Error: ' . $e->getMessage(), [
                            'pengajuan_id' => $this->record->id,
                            'user_id' => $user->id,
                            'trace' => $e->getTraceAsString()
                        ]);

                        Notification::make()
                            ->title('Terjadi Kesalahan')
                            ->body('Gagal memverifikasi pengajuan: ' . $e->getMessage())
                            ->danger()
                            ->duration(7000)
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Verifikasi Pengajuan Magang')
                ->modalDescription('Pastikan Anda telah memeriksa semua dokumen dan informasi sebelum melakukan verifikasi.')
                ->modalSubmitActionLabel('Verifikasi Sekarang')
                ->modalWidth('2xl');
        }

        // Tombol Edit & Hapus
        if ($isAdmin || ($isMahasiswa && $this->record->status === \App\Models\PengajuanMagang::STATUS_PENDING)) {
            $actions[] = \Filament\Actions\EditAction::make()
                ->color('warning')
                ->icon('heroicon-o-pencil-square');

            $actions[] = \Filament\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Hapus Pengajuan')
                ->modalDescription('Apakah Anda yakin ingin menghapus pengajuan ini? Tindakan ini tidak dapat dibatalkan.')
                ->color('danger')
                ->icon('heroicon-o-trash');
        }

        // Tombol Ajukan Ulang untuk Mahasiswa
        if ($isMahasiswa && $this->record->status === \App\Models\PengajuanMagang::STATUS_DITOLAK) {
            $actions[] = Action::make('ajukan_ulang')
                ->label('Ajukan Ulang')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->url(fn() => $this->getResource()::getUrl('create'))
                ->tooltip('Buat pengajuan baru berdasarkan feedback yang diberikan');
        }

        // Action Download Surat Balasan (untuk mahasiswa yang diterima)
        if ($isMahasiswa && $this->record->isDiterima() && $this->record->surat_balasan) {
            $actions[] = Action::make('download_surat')
                ->label('Unduh Surat Penerimaan')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn() => asset('storage/' . $this->record->surat_balasan))
                ->openUrlInNewTab();
        }

        return $actions;
    }

    public function getInfolist(string $name = 'default'): ?Infolist
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $isMahasiswa = $user->role === 'mahasiswa' &&
                       $user->mahasiswa &&
                       $this->record->mahasiswa_id === $user->mahasiswa->id;

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
                                \App\Models\PengajuanMagang::STATUS_PENDING => 'Sedang Diproses',
                                \App\Models\PengajuanMagang::STATUS_DITERIMA => 'Diterima',
                                \App\Models\PengajuanMagang::STATUS_DITOLAK => 'Ditolak',
                                \App\Models\PengajuanMagang::STATUS_SELESAI => 'Selesai',
                            })
                            ->color(fn(string $state): string => match ($state) {
                                \App\Models\PengajuanMagang::STATUS_PENDING => 'warning',
                                \App\Models\PengajuanMagang::STATUS_DITERIMA => 'success',
                                \App\Models\PengajuanMagang::STATUS_DITOLAK => 'danger',
                                \App\Models\PengajuanMagang::STATUS_SELESAI => 'info',
                            })
                            ->icon(fn(string $state): string => match ($state) {
                                \App\Models\PengajuanMagang::STATUS_PENDING => 'heroicon-o-clock',
                                \App\Models\PengajuanMagang::STATUS_DITERIMA => 'heroicon-o-check-circle',
                                \App\Models\PengajuanMagang::STATUS_DITOLAK => 'heroicon-o-x-circle',
                                \App\Models\PengajuanMagang::STATUS_SELESAI => 'heroicon-o-academic-cap',
                            })
                            ->weight(FontWeight::Bold),

                        TextEntry::make('alasan_penolakan')
                            ->label('Keterangan')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->visible(fn() => $this->record->status === \App\Models\PengajuanMagang::STATUS_DITOLAK && ($isAdmin || $isMahasiswa))
                            ->weight(FontWeight::Medium)
                            ->color('danger')
                            ->columnSpanFull()
                            ->formatStateUsing(fn($state) => $state ?: 'Tidak ada keterangan')
                            ->extraAttributes([
                                'class' => 'bg-red-50 border border-red-200 rounded-lg p-3',
                            ]),

                        TextEntry::make('tanggal_verifikasi')
                            ->label('Tanggal Verifikasi')
                            ->icon('heroicon-o-calendar')
                            ->formatStateUsing(fn($state) => $state
                                ? Carbon::parse($state)->translatedFormat('l, d F Y - H:i') . ' WIB'
                                : 'Menunggu verifikasi')
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


                    ])
                    ->heading('Status Pengajuan')
                    ->description(fn() => $this->getStatusDescription())
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(3)
                    ->compact(),

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
                    ->columns(2)
                    ->collapsible(),

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
                    ->columns(2)
                    ->collapsible(),

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
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    protected function getStatusDescription(): string
    {
        return match ($this->record->status) {
            \App\Models\PengajuanMagang::STATUS_PENDING => 'Pengajuan sedang dalam proses peninjauan oleh admin',
            \App\Models\PengajuanMagang::STATUS_DITERIMA => 'Selamat! Pengajuan Anda telah disetujui',
            \App\Models\PengajuanMagang::STATUS_DITOLAK => 'Pengajuan tidak dapat diproses saat ini',
            \App\Models\PengajuanMagang::STATUS_SELESAI => 'Magang telah selesai dilaksanakan',
            default => 'Status tidak diketahui',
        };
    }
}
