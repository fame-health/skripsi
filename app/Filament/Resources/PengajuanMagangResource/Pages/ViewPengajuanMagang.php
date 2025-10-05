<?php

namespace App\Filament\Resources\PengajuanMagangResource\Pages;

use App\Filament\Resources\PengajuanMagangResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
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
        $isMahasiswa = $user->role === 'mahasiswa' && $user->mahasiswa && $this->record->mahasiswa_id === $user->mahasiswa->id;

        if ($isAdmin && $this->record->status === \App\Models\PengajuanMagang::STATUS_PENDING) {
            $actions[] = Action::make('verify')
                ->label('Verifikasi')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Status Verifikasi')
                        ->options([
                            \App\Models\PengajuanMagang::STATUS_DITERIMA => 'Diterima',
                            \App\Models\PengajuanMagang::STATUS_DITOLAK => 'Ditolak',
                        ])
                        ->required()
                        ->reactive(),
                    Forms\Components\Textarea::make('alasan_penolakan')
                        ->label('Alasan Penolakan')
                        ->rows(4)
                        ->visible(fn ($get) => $get('status') === \App\Models\PengajuanMagang::STATUS_DITOLAK)
                        ->required(fn ($get) => $get('status') === \App\Models\PengajuanMagang::STATUS_DITOLAK)
                        ->placeholder('Masukkan alasan penolakan yang jelas dan konstruktif'),
                    Forms\Components\Select::make('pembimbing_id')
                        ->label('Pembimbing')
                        ->relationship('pembimbing', 'user_id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name ?? 'Tanpa Nama')
                        ->searchable()
                        ->preload()
                        ->required(fn ($get) => $get('status') === \App\Models\PengajuanMagang::STATUS_DITERIMA)
                        ->visible(fn ($get) => $get('status') === \App\Models\PengajuanMagang::STATUS_DITERIMA)
                        ->helperText('Pilih pembimbing untuk mahasiswa yang diterima'),
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

                            // Generate QR code
                            $validationUrl = url('/validate-internship/' . $this->record->id);
                            $qrCode = QrCode::create($validationUrl)
                                ->setSize(100)
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
                                throw new \Exception('Tidak ada template surat penerimaan aktif!');
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
                            $pdf = Pdf::loadHTML($renderedContent);
                            $pdfPath = 'pengajuan-magang/surat-balasan/surat_balasan_' . $this->record->id . '.pdf';
                            Storage::disk('public')->put($pdfPath, $pdf->output());

                            // Simpan path ke database
                            $this->record->surat_balasan = $pdfPath;
                        }

                        $this->record->save();

                        Notification::make()
                            ->title('Sukses')
                            ->body('Pengajuan berhasil diverifikasi.')
                            ->success()
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
                        \Illuminate\Support\Facades\Log::error('Verifikasi Error: ' . $e->getMessage());
                        Notification::make()
                            ->title('Error')
                            ->body('Gagal memverifikasi pengajuan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Verifikasi Pengajuan')
                ->modalDescription('Silakan pilih status verifikasi untuk pengajuan ini.')
                ->modalSubmitActionLabel('Simpan Verifikasi');
        }

        // Tombol edit / hapus
        if ($isAdmin || ($isMahasiswa && $this->record->status === \App\Models\PengajuanMagang::STATUS_PENDING)) {
            $actions[] = \Filament\Actions\EditAction::make();
            $actions[] = \Filament\Actions\DeleteAction::make()->requiresConfirmation();
        }

        // Tombol kembali
        $actions[] = Action::make('back')
            ->label('Kembali')
            ->icon('heroicon-o-arrow-left')
            ->url(fn() => $this->getResource()::getUrl('index'))
            ->color('gray');

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
                Section::make('Informasi Mahasiswa')
                    ->schema([
                        TextEntry::make('mahasiswa.nim')->label('NIM')->weight(FontWeight::Medium),
                        TextEntry::make('mahasiswa.user.name')->label('Nama Mahasiswa')->weight(FontWeight::Medium),
                        TextEntry::make('pembimbing.user.name')
                            ->label('Nama Pembimbing')
                            ->default('Belum Ditentukan')
                            ->visible($isAdmin || ($isMahasiswa && $this->record->pembimbing_id)),
                    ])
                    ->columns(2)
                    ->extraAttributes(['class' => 'shadow-md rounded-lg border border-gray-200']),

                Section::make('Detail Pengajuan')
                    ->schema([
                        TextEntry::make('tanggal_mulai')->label('Tanggal Mulai')->date('d F Y')->weight(FontWeight::Medium),
                        TextEntry::make('tanggal_selesai')->label('Tanggal Selesai')->date('d F Y')->weight(FontWeight::Medium),
                        TextEntry::make('durasi_magang')->label('Durasi Magang')->suffix(' minggu')->weight(FontWeight::Medium),
                        TextEntry::make('bidang_diminati')->label('Bidang Diminati')->weight(FontWeight::Medium),
                    ])
                    ->columns(2)
                    ->extraAttributes(['class' => 'shadow-md rounded-lg border border-gray-200']),

                Section::make('Status dan Verifikasi')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                \App\Models\PengajuanMagang::STATUS_PENDING => 'warning',
                                \App\Models\PengajuanMagang::STATUS_DITERIMA => 'success',
                                \App\Models\PengajuanMagang::STATUS_DITOLAK => 'danger',
                                \App\Models\PengajuanMagang::STATUS_SELESAI => 'success',
                            }),
                        TextEntry::make('alasan_penolakan')
                            ->label('Alasan Penolakan')
                            ->default('Tidak ada alasan penolakan')
                            ->visible(fn() => $this->record->status === \App\Models\PengajuanMagang::STATUS_DITOLAK && ($isAdmin || $isMahasiswa)),
                        TextEntry::make('tanggal_verifikasi')
                            ->label('Tanggal Verifikasi')
                            ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('d F Y H:i') : 'Belum Diverifikasi')
                            ->visible($isAdmin),
                        TextEntry::make('verified_by')
                            ->label('Diverifikasi Oleh')
                            ->getStateUsing(fn($record) => $record->verifikator?->name ?? 'Belum Diverifikasi')
                            ->visible($isAdmin),
                    ])
                    ->columns(2)
                    ->extraAttributes(['class' => 'shadow-md rounded-lg border border-gray-200']),

                Section::make('Dokumen Terkait')
                    ->schema([
                        TextEntry::make('surat_permohonan')
                            ->label('Surat Permohonan')
                            ->formatStateUsing(fn($state) => $state
                                ? new HtmlString('<a href="' . asset('storage/' . $state) . '" target="_blank" class="inline-flex items-center px-3 py-1 border border-primary-600 text-primary-600 text-sm rounded-md hover:bg-primary-50 hover:text-primary-800 font-medium transition-colors">Unduh Surat Permohonan</a>')
                                : 'Tidak Tersedia'),
                        TextEntry::make('ktm')
                            ->label('Kartu Tanda Mahasiswa')
                            ->formatStateUsing(fn($state) => $state
                                ? new HtmlString('<a href="' . asset('storage/' . $state) . '" target="_blank" class="inline-flex items-center px-3 py-1 border border-primary-600 text-primary-600 text-sm rounded-md hover:bg-primary-50 hover:text-primary-800 font-medium transition-colors">Unduh KTM</a>')
                                : 'Tidak Tersedia'),
                        TextEntry::make('surat_balasan')
                            ->label('Surat Balasan')
                            ->formatStateUsing(fn($state) => $state
                                ? new HtmlString('<a href="' . asset('storage/' . $state) . '" target="_blank" class="inline-flex items-center px-3 py-1 border border-primary-600 text-primary-600 text-sm rounded-md hover:bg-primary-50 hover:text-primary-800 font-medium transition-colors">Unduh Surat Balasan</a>')
                                : 'Tidak Tersedia')
                            ->visible($isAdmin || ($isMahasiswa && $this->record->isDiterima())),
                        TextEntry::make('final_laporan')
                            ->label('Laporan Akhir')
                            ->formatStateUsing(fn($state) => $state
                                ? new HtmlString('<a href="' . asset('storage/' . $state) . '" target="_blank" class="inline-flex items-center px-3 py-1 border border-primary-600 text-primary-600 text-sm rounded-md hover:bg-primary-50 hover:text-primary-800 font-medium transition-colors">Unduh Laporan Akhir</a>')
                                : 'Tidak Tersedia')
                            ->visible($isAdmin),
                        TextEntry::make('sertifikat')
                            ->label('Sertifikat')
                            ->formatStateUsing(fn($state) => $state
                                ? new HtmlString('<a href="' . asset('storage/' . $state) . '" target="_blank" class="inline-flex items-center px-3 py-1 border border-primary-600 text-primary-600 text-sm rounded-md hover:bg-primary-50 hover:text-primary-800 font-medium transition-colors">Unduh Sertifikat</a>')
                                : 'Tidak Tersedia')
                            ->visible($isAdmin),
                    ])
                    ->columns(1)
                    ->extraAttributes(['class' => 'shadow-md rounded-lg border border-gray-200']),
            ]);
    }
}
