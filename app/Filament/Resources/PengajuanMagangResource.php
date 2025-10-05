<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanMagangResource\Pages;
use App\Models\PengajuanMagang;
use App\Models\TemplateSurat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Colors\Color;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Blade;

class PengajuanMagangResource extends Resource
{
    protected static ?string $model = PengajuanMagang::class;

    protected static ?string $navigationGroup = 'ALUR PELAKSANAAN PKL';

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pengajuan Magang';

    protected static ?string $pluralModelLabel = 'Silakan klik Pengajuan Manggang';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['admin', 'mahasiswa']);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery()->with(['mahasiswa.user', 'pembimbing']);

        if ($user->role === 'admin') {
            return $query;
        }

        if ($user->role === 'mahasiswa' && $user->mahasiswa) {
            return $query->where('mahasiswa_id', $user->mahasiswa->id);
        }

        return $query->whereNull('id');
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        if (!$user || !in_array($user->role, ['admin', 'mahasiswa'])) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'mahasiswa') {
            if (!$user->mahasiswa) {
                return false;
            }

            $lastPengajuan = PengajuanMagang::where('mahasiswa_id', $user->mahasiswa->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$lastPengajuan) {
                return true;
            }

            if ($lastPengajuan->status === PengajuanMagang::STATUS_PENDING) {
                return false;
            }

            if ($lastPengajuan->status === PengajuanMagang::STATUS_DITERIMA) {
                return false;
            }

            if (in_array($lastPengajuan->status, [PengajuanMagang::STATUS_DITOLAK, PengajuanMagang::STATUS_SELESAI])) {
                return true;
            }
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $isMahasiswa = $user->role === 'mahasiswa';

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Mahasiswa')
                    ->schema([
                        Forms\Components\Select::make('mahasiswa_id')
                            ->relationship('mahasiswa', 'nim')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(function () use ($user, $isMahasiswa) {
                                return $isMahasiswa && $user->mahasiswa ? $user->mahasiswa->id : null;
                            })
                            ->disabled($isMahasiswa)
                            ->dehydrated(true)
                            ->visible($isAdmin || $isMahasiswa),
                        Forms\Components\Select::make('pembimbing_id')
                            ->relationship('pembimbing', 'user_id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name ?? 'Tanpa Nama')
                            ->searchable()
                            ->preload()
                            ->disabled($isMahasiswa),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Detail Pengajuan')
                    ->schema([
                        Forms\Components\FileUpload::make('surat_permohonan')
                            ->required()
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('pengajuan-magang/surat-permohonan')
                            ->disabled(!$isAdmin && !$isMahasiswa),
                        Forms\Components\FileUpload::make('ktm')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->directory('pengajuan-magang/ktm')
                            ->disabled(!$isAdmin && !$isMahasiswa),
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->required()
                            ->disabled(!$isAdmin && !$isMahasiswa)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $startDate = Carbon::parse($state);
                                $endDate = Carbon::parse($get('tanggal_selesai'));
                                if ($startDate && $endDate && $endDate->gte($startDate)) {
                                    $weeks = $startDate->diffInWeeks($endDate);
                                    $set('durasi_magang', $weeks);
                                } else {
                                    $set('durasi_magang', null);
                                }
                            }),
                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->required()
                            ->disabled(!$isAdmin && !$isMahasiswa)
                            ->reactive()
                            ->rules(['after_or_equal:tanggal_mulai'])
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $endDate = Carbon::parse($state);
                                $startDate = Carbon::parse($get('tanggal_mulai'));
                                if ($startDate && $endDate && $endDate->gte($startDate)) {
                                    $weeks = $startDate->diffInWeeks($endDate);
                                    $set('durasi_magang', $weeks);
                                } else {
                                    $set('durasi_magang', null);
                                }
                            }),
                        Forms\Components\TextInput::make('durasi_magang')
                            ->numeric()
                            ->suffix('minggu')
                            ->required()
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('bidang_diminati')
                            ->required()
                            ->disabled(!$isAdmin && !$isMahasiswa),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status dan Verifikasi')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                PengajuanMagang::STATUS_PENDING => 'Pending',
                                PengajuanMagang::STATUS_DITERIMA => 'Diterima',
                                PengajuanMagang::STATUS_DITOLAK => 'Ditolak',
                                PengajuanMagang::STATUS_SELESAI => 'Selesai',
                            ])
                            ->required()
                            ->default(PengajuanMagang::STATUS_PENDING)
                            ->disabled($isMahasiswa)
                            ->visible($isAdmin),
                        Forms\Components\Textarea::make('alasan_penolakan')
                            ->visible(fn ($get) => $get('status') === PengajuanMagang::STATUS_DITOLAK && $isAdmin)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('tanggal_verifikasi')
                            ->visible($isAdmin),
                        Forms\Components\Select::make('verified_by')
                            ->relationship('verifikator', 'name')
                            ->searchable()
                            ->preload()
                            ->visible($isAdmin),
                    ])
                    ->columns(2)
                    ->visible($isAdmin),

                Forms\Components\Section::make('Dokumen Tambahan')
                    ->schema([
                        Forms\Components\FileUpload::make('surat_balasan')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('pengajuan-magang/surat-balasan')
                            ->disabled(true) // Disabled since it will be auto-generated
                            ->visible($isAdmin),
                        Forms\Components\FileUpload::make('final_laporan')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('pengajuan-magang/laporan')
                            ->disabled($isMahasiswa)
                            ->visible($isAdmin),
                        Forms\Components\FileUpload::make('sertifikat')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('pengajuan-magang/sertifikat')
                            ->disabled($isMahasiswa)
                            ->visible($isAdmin),
                    ])
                    ->columns(2)
                    ->visible($isAdmin),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        return $table
            ->heading(function () use ($user) {
                if ($user->role !== 'mahasiswa' || !$user->mahasiswa) {
                    return null;
                }

                $pengajuan = PengajuanMagang::where('mahasiswa_id', $user->mahasiswa->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$pengajuan) {
                    return null;
                }

                $html = '';

                if ($pengajuan->isDitolak()) {
                    $alasan = $pengajuan->alasan_penolakan ?: 'Tidak ada alasan yang diberikan';
                    $html = '
                        <div style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
                                   border: 2px solid #dc2626;
                                   border-radius: 12px;
                                   padding: 20px;
                                   margin: 16px 0;
                                   box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.1);">
                            <div style="display: flex; align-items: center; margin-bottom: 12px;">
                                <div style="background: #dc2626;
                                           color: white;
                                           border-radius: 50%;
                                           width: 32px;
                                           height: 32px;
                                           display: flex;
                                           align-items: center;
                                           justify-content: center;
                                           font-weight: bold;
                                           margin-right: 12px;">❌</div>
                                <h3 style="color: #991b1b;
                                          font-size: 18px;
                                          font-weight: 700;
                                          margin: 0;">PENGAJUAN MAGANG DITOLAK</h3>
                            </div>
                            <div style="background: #fecaca;
                                       border-left: 4px solid #dc2626;
                                       padding: 12px 16px;
                                       border-radius: 6px;">
                                <p style="color: #7f1d1d;
                                         font-weight: 600;
                                         margin: 0 0 8px 0;
                                         font-size: 14px;">ALASAN PENOLAKAN:</p>
                                <p style="color: #991b1b;
                                         font-size: 16px;
                                         margin: 0;
                                         line-height: 1.5;">' . htmlspecialchars($alasan) . '</p>
                            </div>
                            <div style="margin-top: 12px;
                                       padding: 8px 12px;
                                       background: rgba(220, 38, 38, 0.1);
                                       border-radius: 6px;
                                       border-left: 3px solid #dc2626;">
                                <p style="color: #7f1d1d;
                                         font-size: 14px;
                                         margin: 0;
                                         font-style: italic;">
                                    💡 Anda dapat memperbaiki pengajuan dan mengajukan kembali setelah memperbaiki masalah yang disebutkan.
                                </p>
                            </div>
                        </div>';
                } elseif ($pengajuan->isDiterima()) {
                    $html = '
                        <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                                   border: 2px solid #16a34a;
                                   border-radius: 12px;
                                   padding: 20px;
                                   margin: 16px 0;
                                   box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.1);">
                            <div style="display: flex; align-items: center; margin-bottom: 12px;">
                                <div style="background: #16a34a;
                                           color: white;
                                           border-radius: 50%;
                                           width: 32px;
                                           height: 32px;
                                           display: flex;
                                           align-items: center;
                                           justify-content: center;
                                           font-weight: bold;
                                           margin-right: 12px;">✅</div>
                                <h3 style="color: #15803d;
                                          font-size: 18px;
                                          font-weight: 700;
                                          margin: 0;">SELAMAT! PENGAJUAN MAGANG DITERIMA</h3>
                            </div>
                            <div style="background: #bbf7d0;
                                       border-left: 4px solid #16a34a;
                                       padding: 12px 16px;
                                       border-radius: 6px;
                                       margin-bottom: 12px;">
                                <p style="color: #15803d;
                                         font-size: 16px;
                                         margin: 0;
                                         line-height: 1.5;">
                                    🎉 Pengajuan magang Anda telah disetujui! Silakan lanjutkan ke tahap berikutnya.
                                </p>
                            </div>';

                    if ($pengajuan->surat_balasan) {
                        $html .= '
                            <div style="background: rgba(22, 163, 74, 0.1);
                                       border: 1px solid #16a34a;
                                       border-radius: 8px;
                                       padding: 12px;">
                                <div style="display: flex;
                                          align-items: center;
                                          justify-content: space-between;">
                                    <div style="display: flex; align-items: center;">
                                        <span style="color: #15803d;
                                                    font-size: 16px;
                                                    margin-right: 8px;">📄</span>
                                        <span style="color: #15803d;
                                                    font-weight: 600;">Surat Balasan Tersedia</span>
                                    </div>
                                    <a href="' . asset('storage/' . $pengajuan->surat_balasan) . '"
                                       target="_blank"
                                       style="background: #16a34a;
                                              color: white;
                                              padding: 8px 16px;
                                              border-radius: 6px;
                                              text-decoration: none;
                                              font-weight: 600;
                                              font-size: 14px;
                                              display: inline-flex;
                                              align-items: center;
                                              gap: 6px;
                                              transition: background-color 0.2s;"
                                       onmouseover="this.style.background=\'#059669\'"
                                       onmouseout="this.style.background=\'#16a34a\'">
                                        📥 Unduh Surat Balasan
                                    </a>
                                </div>
                            </div>';
                    }

                    $html .= '
                            <div style="margin-top: 12px;
                                       padding: 8px 12px;
                                       background: rgba(22, 163, 74, 0.1);
                                       border-radius: 6px;
                                       border-left: 3px solid #16a34a;">
                                <p style="color: #15803d;
                                         font-size: 14px;
                                         margin: 0;
                                         font-style: italic;">
                                    ℹ️ Pengajuan magang Anda sudah disetujui. Anda tidak dapat mengajukan magang lagi.
                                </p>
                            </div>
                        </div>';
                } elseif ($pengajuan->isPending()) {
                    $html = '
                        <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
                                   border: 2px solid #f59e0b;
                                   border-radius: 12px;
                                   padding: 20px;
                                   margin: 16px 0;
                                   box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.1);">
                            <div style="display: flex; align-items: center; margin-bottom: 12px;">
                                <div style="background: #f59e0b;
                                           color: white;
                                           border-radius: 50%;
                                           width: 32px;
                                           height: 32px;
                                           display: flex;
                                           align-items: center;
                                           justify-content: center;
                                           font-weight: bold;
                                           margin-right: 12px;">⏳</div>
                                <h3 style="color: #92400e;
                                          font-size: 18px;
                                          font-weight: 700;
                                          margin: 0;">PENGAJUAN MAGANG SEDANG DIPROSES</h3>
                            </div>
                            <div style="background: #fde68a;
                                       border-left: 4px solid #f59e0b;
                                       padding: 12px 16px;
                                       border-radius: 6px;">
                                <p style="color: #92400e;
                                         font-size: 16px;
                                         margin: 0;
                                         line-height: 1.5;">
                                    📋 Pengajuan Anda sedang dalam proses verifikasi. Mohon bersabar menunggu konfirmasi dari admin.
                                </p>
                            </div>
                            <div style="margin-top: 12px;
                                       padding: 8px 12px;
                                       background: rgba(245, 158, 11, 0.1);
                                       border-radius: 6px;
                                       border-left: 3px solid #f59e0b;">
                                <p style="color: #92400e;
                                         font-size: 14px;
                                         margin: 0;
                                         font-style: italic;">
                                    ⚠️ Anda tidak dapat mengajukan magang lagi sampai pengajuan ini selesai diproses.
                                </p>
                            </div>
                        </div>';
                }

                return new \Illuminate\Support\HtmlString($html);
            })
            ->columns([
                Tables\Columns\TextColumn::make('mahasiswa.nim')
                    ->label('NIM')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mahasiswa.user.name')
                    ->label('Nama Mahasiswa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pembimbing.user.name')
                    ->label('Nama Pembimbing')
                    ->searchable()
                    ->sortable()
                    ->visible($isAdmin),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PengajuanMagang::STATUS_PENDING => 'warning',
                        PengajuanMagang::STATUS_DITERIMA => 'success',
                        PengajuanMagang::STATUS_DITOLAK => 'danger',
                        PengajuanMagang::STATUS_SELESAI => 'success',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('alasan_penolakan')
                    ->label('Alasan Penolakan')
                    ->searchable()
                    ->sortable()
                    ->visible($isAdmin)
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        PengajuanMagang::STATUS_PENDING => 'Pending',
                        PengajuanMagang::STATUS_DITERIMA => 'Diterima',
                        PengajuanMagang::STATUS_DITOLAK => 'Ditolak',
                        PengajuanMagang::STATUS_SELESAI => 'Selesai',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $isAdmin || ($user->role === 'mahasiswa' && $record->status === PengajuanMagang::STATUS_PENDING)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $isAdmin || ($user->role === 'mahasiswa' && $record->status === PengajuanMagang::STATUS_PENDING))
                    ->requiresConfirmation(),
                Action::make('download_surat_balasan_row')
                    ->label('Unduh Surat Balasan')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->visible(fn ($record) => $user->role === 'mahasiswa' && $record->isDiterima() && $record->surat_balasan)
                    ->url(fn ($record) => asset('storage/' . $record->surat_balasan))
                    ->openUrlInNewTab(),
                Action::make('view_documents')
                    ->label('Lihat Dokumen')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->visible(fn ($record) => (
                        $isAdmin ||
                        ($user->role === 'mahasiswa' && $record->mahasiswa_id === $user->mahasiswa->id)
                    ) && (
                        $record->surat_permohonan ||
                        $record->ktm ||
                        ($isAdmin && ($record->surat_balasan || $record->final_laporan || $record->sertifikat)) ||
                        ($user->role === 'mahasiswa' && $record->isDiterima() && $record->surat_balasan)
                    ))
                    ->form([
                        Forms\Components\Section::make('Dokumen Terkait')
                            ->schema(function ($record) use ($isAdmin) {
                                $components = [];

                                if ($record->surat_permohonan) {
                                    $components[] = Forms\Components\Placeholder::make('surat_permohonan')
                                        ->label('Surat Permohonan')
                                        ->content(new \Illuminate\Support\HtmlString(
                                            '<a href="' . asset('storage/' . $record->surat_permohonan) . '" target="_blank" class="text-primary-600 hover:text-primary-800 font-medium">Lihat Surat Permohonan</a>'
                                        ));
                                }

                                if ($record->ktm) {
                                    $components[] = Forms\Components\Placeholder::make('ktm')
                                        ->label('Kartu Tanda Mahasiswa')
                                        ->content(new \Illuminate\Support\HtmlString(
                                            '<a href="' . asset('storage/' . $record->ktm) . '" target="_blank" class="text-primary-600 hover:text-primary-800 font-medium">Lihat KTM</a>'
                                        ));
                                }

                                if ($isAdmin || ($record->isDiterima() && $record->surat_balasan)) {
                                    if ($record->surat_balasan) {
                                        $components[] = Forms\Components\Placeholder::make('surat_balasan')
                                            ->label('Surat Balasan')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<a href="' . asset('storage/' . $record->surat_balasan) . '" target="_blank" class="text-primary-600 hover:text-primary-800 font-medium">Lihat Surat Balasan</a>'
                                            ));
                                    }
                                }

                                if ($isAdmin) {
                                    if ($record->final_laporan) {
                                        $components[] = Forms\Components\Placeholder::make('final_laporan')
                                            ->label('Laporan Akhir')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<a href="' . asset('storage/' . $record->final_laporan) . '" target="_blank" class="text-primary-600 hover:text-primary-800 font-medium">Lihat Laporan Akhir</a>'
                                            ));
                                    }

                                    if ($record->sertifikat) {
                                        $components[] = Forms\Components\Placeholder::make('sertifikat')
                                            ->label('Sertifikat')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<a href="' . asset('storage/' . $record->sertifikat) . '" target="_blank" class="text-primary-600 hover:text-primary-800 font-medium">Lihat Sertifikat</a>'
                                            ));
                                    }
                                }

                                return $components;
                            })
                            ->columns(1),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $isAdmin && $record->status === PengajuanMagang::STATUS_PENDING)
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                PengajuanMagang::STATUS_DITERIMA => 'Diterima',
                                PengajuanMagang::STATUS_DITOLAK => 'Ditolak',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Textarea::make('alasan_penolakan')
                            ->label('Alasan Penolakan')
                            ->visible(fn ($get) => $get('status') === PengajuanMagang::STATUS_DITOLAK)
                            ->required(fn ($get) => $get('status') === PengajuanMagang::STATUS_DITOLAK)
                            ->placeholder('Masukkan alasan penolakan yang jelas dan konstruktif')
                            ->rows(3),
                        Forms\Components\Select::make('pembimbing_id')
                            ->label('Pembimbing')
                            ->relationship('pembimbing', 'user_id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name ?? 'Tanpa Nama')
                            ->searchable()
                            ->preload()
                            ->required(fn ($get) => $get('status') === PengajuanMagang::STATUS_DITERIMA)
                            ->visible(fn ($get) => $get('status') === PengajuanMagang::STATUS_DITERIMA)
                            ->helperText('Pilih pembimbing untuk mahasiswa yang diterima'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->status = $data['status'];

                        if ($data['status'] === PengajuanMagang::STATUS_DITOLAK) {
                            $record->alasan_penolakan = $data['alasan_penolakan'];
                        }

                        if ($data['status'] === PengajuanMagang::STATUS_DITERIMA) {
                            if (isset($data['pembimbing_id'])) {
                                $record->pembimbing_id = $data['pembimbing_id'];
                            }

                            // Generate QR code
                            $validationUrl = url('/validate-internship/' . $record->id);
                            $qrCode = QrCode::create($validationUrl)
                                ->setSize(100)
                                ->setMargin(10);
                            $writer = new PngWriter();
                            $qrCodeImage = $writer->write($qrCode);
                            $qrCodePath = 'pengajuan-magang/qr-codes/qr_' . $record->id . '.png';
                            Storage::disk('public')->put($qrCodePath, $qrCodeImage->getString());

                            // Fetch the active template for 'penerimaan'
                            $template = TemplateSurat::where('jenis_surat', TemplateSurat::JENIS_PENERIMAAN)
                                ->where('is_active', true)
                                ->first();

                            if (!$template) {
                                throw new \Exception('No active template found for penerimaan');
                            }

                            // Generate nomer_surat with format [nomer_surat_template]/[3 digit akhir NIM]/[tahun sekarang]
                            $nim_akhir = substr($record->mahasiswa->nim, -3);
                            $tahun = now()->format('Y');
                            $nomer_surat_final = $template->nomer_surat . '/' . $nim_akhir . '/' . $tahun;

                            // Prepare data for the template
                            $pdfData = [
                                'nomer_surat' => $nomer_surat_final,
                                'mahasiswa_name' => $record->mahasiswa->user->name,
                                'nim' => $record->mahasiswa->nim,
                                'pembimbing_name' => $record->pembimbing->user->name ?? 'N/A',
                                'tanggal_mulai' => Carbon::parse($record->tanggal_mulai)->format('d F Y'),
                                'tanggal_selesai' => Carbon::parse($record->tanggal_selesai)->format('d F Y'),
                                'bidang_diminati' => $record->bidang_diminati,
                                'qr_code_path' => Storage::disk('public')->path($qrCodePath),
                                'tanggal_verifikasi' => now()->format('d F Y'),
                                'verified_by' => Auth::user()->name,
                                'id_pengajuan' => $record->id,

                            ];

                            // Render the template content
                            $renderedContent = Blade::render($template->content_template, $pdfData);

                            // Generate PDF
                            $pdf = Pdf::loadHTML($renderedContent);
                            $pdfPath = 'pengajuan-magang/surat-balasan/surat_balasan_' . $record->id . '.pdf';
                            Storage::disk('public')->put($pdfPath, $pdf->output());
                            $record->surat_balasan = $pdfPath;
                        }

                        $record->tanggal_verifikasi = now();
                        $record->verified_by = Auth::user()->id;
                        $record->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible($isAdmin)
                    ->requiresConfirmation(),
            ]);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'mahasiswa' && $user->mahasiswa) {
            return $record->mahasiswa_id === $user->mahasiswa->id && $record->status === PengajuanMagang::STATUS_PENDING;
        }

        return false;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'mahasiswa' && $user->mahasiswa) {
            return $record->mahasiswa_id === $user->mahasiswa->id && $record->status === PengajuanMagang::STATUS_PENDING;
        }

        return false;
    }

    public static function canView($record): bool
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'mahasiswa' && $user->mahasiswa) {
            return $record->mahasiswa_id === $user->mahasiswa->id;
        }

        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuanMagangs::route('/'),
            'create' => Pages\CreatePengajuanMagang::route('/create'),
            'edit' => Pages\EditPengajuanMagang::route('/{record}/edit'),
            'view' => Pages\ViewPengajuanMagang::route('/{record}/view'),
        ];
    }
}

