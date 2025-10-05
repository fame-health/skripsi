<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanMingguanResource\Pages;
use App\Filament\Resources\LaporanMingguanResource\RelationManagers;
use App\Models\LaporanMingguan;
use App\Models\PengajuanMagang;
use App\Models\Mahasiswa;
use App\Models\Pembimbing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class LaporanMingguanResource extends Resource
{
    protected static ?string $model = LaporanMingguan::class;

    protected static ?string $navigationGroup = 'ALUR PELAKSANAAN PKL';

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Laporan Mingguan';

    protected static ?string $modelLabel = 'Laporan Mingguan';

    protected static ?string $pluralModelLabel = 'Laporan Mingguan';

public static function shouldRegisterNavigation(): bool
{
    $user = Auth::user();
    if (!$user) {
        Log::warning('No authenticated user for shouldRegisterNavigation check');
        return false;
    }

    if ($user->role === 'pembimbing') {
        // Pembimbing can see the resource if they have approved PengajuanMagang
        $pembimbing = Pembimbing::where('user_id', $user->id)->first();
        if ($pembimbing) {
            $hasApprovedPengajuan = PengajuanMagang::where('pembimbing_id', $pembimbing->id)
                ->where('status', PengajuanMagang::STATUS_DITERIMA)
                ->exists();
            Log::info('shouldRegisterNavigation check for pembimbing', [
                'user_id' => $user->id,
                'pembimbing_id' => $pembimbing->id,
                'has_approved_pengajuan' => $hasApprovedPengajuan,
            ]);
            return $hasApprovedPengajuan;
        }
        return false;
    }

    if ($user->role === 'mahasiswa') {
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        if (!$mahasiswa) {
            Log::info('Hiding LaporanMingguanResource: No Mahasiswa record', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        // Check all required Mahasiswa fields
        $requiredFields = [
            'nim' => $mahasiswa->nim,
            'universitas' => $mahasiswa->universitas,
            'fakultas' => $mahasiswa->fakultas,
            'jurusan' => $mahasiswa->jurusan,
            'semester' => $mahasiswa->semester,
            'ipk' => $mahasiswa->ipk,
            'alamat' => $mahasiswa->alamat,
            'tanggal_lahir' => $mahasiswa->tanggal_lahir,
            'jenis_kelamin' => $mahasiswa->jenis_kelamin,
            'user_name' => $mahasiswa->user ? $mahasiswa->user->name : null,
        ];

        $isMahasiswaDataFilled = true;
        foreach ($requiredFields as $field => $value) {
            if (is_null($value) || $value === '') {
                $isMahasiswaDataFilled = false;
                break;
            }
        }

        Log::info('shouldRegisterNavigation check for mahasiswa', [
            'user_id' => $user->id,
            'mahasiswa_id' => $mahasiswa->id,
            'required_fields' => $requiredFields,
            'is_mahasiswa_data_filled' => $isMahasiswaDataFilled,
        ]);

        if (!$isMahasiswaDataFilled) {
            Log::info('Hiding LaporanMingguanResource: Mahasiswa data incomplete', [
                'user_id' => $user->id,
                'mahasiswa_id' => $mahasiswa->id,
            ]);
            return false;
        }

        // Check for approved PengajuanMagang
        $hasApprovedPengajuan = PengajuanMagang::where('mahasiswa_id', $mahasiswa->id)
            ->where('status', PengajuanMagang::STATUS_DITERIMA)
            ->exists();

        Log::info('Mahasiswa pengajuan check in shouldRegisterNavigation', [
            'user_id' => $user->id,
            'mahasiswa_id' => $mahasiswa->id,
            'has_approved_pengajuan' => $hasApprovedPengajuan,
        ]);

        return $hasApprovedPengajuan;
    }

    // Admin can see the resource if there are approved PengajuanMagang
    $hasApprovedPengajuan = PengajuanMagang::where('status', PengajuanMagang::STATUS_DITERIMA)->exists();
    Log::info('shouldRegisterNavigation check for admin', [
        'user_id' => $user->id,
        'has_approved_pengajuan' => $hasApprovedPengajuan,
    ]);
    return $hasApprovedPengajuan;
}

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $mahasiswa = null;
        $pembimbing = null;
        $isMahasiswa = false;
        $isPembimbing = false;
        $isAdmin = true;

        if ($user) {
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            $pembimbing = Pembimbing::where('user_id', $user->id)->first();
            $isMahasiswa = !is_null($mahasiswa);
            $isPembimbing = !is_null($pembimbing);
            $isAdmin = !$isMahasiswa && !$isPembimbing;
        }

        $pengajuanMagang = $isMahasiswa
            ? PengajuanMagang::where('mahasiswa_id', $mahasiswa->id)
                ->where('status', PengajuanMagang::STATUS_DITERIMA)
                ->first()
            : null;

        Log::info('Form loaded for user', [
            'user_id' => $user?->id,
            'is_mahasiswa' => $isMahasiswa,
            'is_pembimbing' => $isPembimbing,
            'is_admin' => $isAdmin,
            'pengajuan_magang_id' => $pengajuanMagang?->id,
        ]);

        $mahasiswaSchema = [
            Section::make('Informasi Dasar')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Hidden::make('pengajuan_magang_id')
                                ->default($pengajuanMagang ? $pengajuanMagang->id : null),

                            Hidden::make('mahasiswa_id')
                                ->default($mahasiswa ? $mahasiswa->id : null),

                            TextInput::make('minggu_ke')
                                ->label('Minggu Ke')
                                ->numeric()
                                ->required()
                                ->minValue(1),


                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('tanggal_mulai')
                                        ->label('Tanggal Mulai')
                                        ->required()
                                        ->native(false),

                                    DatePicker::make('tanggal_selesai')
                                        ->label('Tanggal Selesai')
                                        ->required()
                                        ->native(false)
                                        ->after('tanggal_mulai'),
                                ]),
                        ]),
                ]),

            Section::make('Laporan Kegiatan')
                ->schema([
                    Textarea::make('kegiatan')
                        ->label('Kegiatan')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Textarea::make('pencapaian')
                        ->label('Pencapaian')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Textarea::make('kendala')
                        ->label('Kendala')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('rencana_minggu_depan')
                        ->label('Rencana Minggu Depan')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Status Persetujuan')
                ->schema([
                    TextInput::make('status_approve')
                        ->label('Status Approve')
                        ->formatStateUsing(fn ($state) => $state ? 'Disetujui' : 'Pending')
                        ->disabled()
                        ->default(false),

                    TextInput::make('pembimbingApprover.user.name')
                        ->label('Disetujui Oleh')
                        ->disabled()
                        ->visible(fn ($get) => $get('status_approve')),

                    Textarea::make('catatan_pembimbing')
                        ->label('Catatan Pembimbing')
                        ->disabled()
                        ->rows(3)
                        ->columnSpanFull()
                        ->visible(fn ($get) => $get('status_approve')),
                ])
                ->columns(2),
        ];

        $pembimbingSchema = [
            Section::make('Informasi Laporan')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('minggu_ke')
                                ->label('Minggu Ke')
                                ->disabled(),

                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('tanggal_mulai')
                                        ->label('Tanggal Mulai')
                                        ->disabled()
                                        ->native(false),

                                    DatePicker::make('tanggal_selesai')
                                        ->label('Tanggal Selesai')
                                        ->disabled()
                                        ->native(false),
                                ]),
                        ]),
                ]),

            Section::make('Laporan Kegiatan')
                ->schema([
                    Textarea::make('kegiatan')
                        ->label('Kegiatan')
                        ->disabled()
                        ->rows(4)
                        ->columnSpanFull(),

                    Textarea::make('pencapaian')
                        ->label('Pencapaian')
                        ->disabled()
                        ->rows(4)
                        ->columnSpanFull(),

                    Textarea::make('kendala')
                        ->label('Kendala')
                        ->disabled()
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('rencana_minggu_depan')
                        ->label('Rencana Minggu Depan')
                        ->disabled()
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Persetujuan Pembimbing')
                ->schema([
                    Toggle::make('status_approve')
                        ->label('Setujui Laporan')
                        ->default(false)
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set) use ($pembimbing) {
                            if ($state && $pembimbing) {
                                $set('approved_by', $pembimbing->user_id);
                                $set('approved_at', now());
                            } else {
                                $set('approved_by', null);
                                $set('approved_at', null);
                            }
                        }),

                    Hidden::make('approved_by'),
                    Hidden::make('approved_at'),

                    TextInput::make('pembimbingApprover.user.name')
                        ->label('Disetujui Oleh')
                        ->disabled()
                        ->visible(fn ($get) => $get('status_approve')),

                    Textarea::make('catatan_pembimbing')
                        ->label('Catatan Pembimbing')
                        ->rows(3)
                        ->columnSpanFull()
                        ->required(fn ($get) => $get('status_approve')),
                ])
                ->columns(2),
        ];

        $adminSchema = [
            Section::make('Informasi Dasar')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('mahasiswa_id')
                                ->label('Mahasiswa')
                                ->relationship('mahasiswa', 'nim')
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name . ' - ' . $record->nim)
                                ->required()
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state) {
                                        $pengajuan = PengajuanMagang::where('mahasiswa_id', $state)
                                            ->where('status', PengajuanMagang::STATUS_DITERIMA)
                                            ->first();
                                        $set('pengajuan_magang_id', $pengajuan ? $pengajuan->id : null);
                                    }
                                }),

                            Hidden::make('pengajuan_magang_id'),

                            TextInput::make('minggu_ke')
                                ->label('Minggu Ke')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(52),

                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('tanggal_mulai')
                                        ->label('Tanggal Mulai')
                                        ->required()
                                        ->native(false),

                                    DatePicker::make('tanggal_selesai')
                                        ->label('Tanggal Selesai')
                                        ->required()
                                        ->native(false)
                                        ->after('tanggal_mulai'),
                                ]),
                        ]),
                ]),

            Section::make('Laporan Kegiatan')
                ->schema([
                    Textarea::make('kegiatan')
                        ->label('Kegiatan')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Textarea::make('pencapaian')
                        ->label('Pencapaian')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Textarea::make('kendala')
                        ->label('Kendala')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('rencana_minggu_depan')
                        ->label('Rencana Minggu Depan')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Status Persetujuan')
                ->schema([
                    Toggle::make('status_approve')
                        ->label('Status Approve')
                        ->default(false)
                        ->reactive(),

                    Select::make('approved_by')
                        ->label('Disetujui Oleh')
                        ->relationship('pembimbingApprover', 'user_id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name ?? 'Tanpa Nama')
                        ->searchable()
                        ->preload()
                        ->visible(fn ($get) => $get('status_approve')),

                    TextInput::make('pembimbingApprover.user.name')
                        ->label('Nama Penyetuju')
                        ->disabled()
                        ->visible(fn ($get) => $get('status_approve') && $get('approved_by')),
                ])
                ->columns(2),

            Section::make('Catatan')
                ->schema([
                    Textarea::make('catatan_pembimbing')
                        ->label('Catatan Pembimbing')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ];

        $schema = $isMahasiswa ? $mahasiswaSchema : ($isPembimbing ? $pembimbingSchema : $adminSchema);

        return $form->schema($schema);
    }

    protected static function applyRoleBasedFilters(Builder $query, $mahasiswa, $pembimbing, bool $isMahasiswa, bool $isPembimbing): Builder
    {
        $query->with(['mahasiswa.user', 'pengajuanMagang', 'pembimbingApprover.user']);

        if ($isMahasiswa && $mahasiswa) {
            Log::info('Filtering laporan for mahasiswa_id: ' . $mahasiswa->id);
            $query->where('mahasiswa_id', $mahasiswa->id);
        } elseif ($isPembimbing && $pembimbing) {
            Log::info('Filtering laporan for pembimbing_id: ' . $pembimbing->id);
            $query->whereHas('pengajuanMagang', function ($q) use ($pembimbing) {
                $q->where('pembimbing_id', $pembimbing->id)
                  ->where('status', PengajuanMagang::STATUS_DITERIMA);
            });
        } else {
            Log::info('Applying admin filter for laporan');
            $query->whereHas('pengajuanMagang', function ($q) {
                $q->where('status', PengajuanMagang::STATUS_DITERIMA);
            });
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $mahasiswa = null;
        $pembimbing = null;
        $isMahasiswa = false;
        $isPembimbing = false;
        $isAdmin = true;

        if ($user) {
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            $pembimbing = Pembimbing::where('user_id', $user->id)->first();
            $isMahasiswa = !is_null($mahasiswa);
            $isPembimbing = !is_null($pembimbing);
            $isAdmin = !$isMahasiswa && !$isPembimbing;
        }

        Log::info('Table loaded for user', [
            'user_id' => $user?->id,
            'is_mahasiswa' => $isMahasiswa,
            'is_pembimbing' => $isPembimbing,
            'is_admin' => $isAdmin,
        ]);

        $pendingCount = $isPembimbing && $pembimbing
            ? LaporanMingguan::whereHas('pengajuanMagang', function ($q) use ($pembimbing) {
                $q->where('pembimbing_id', $pembimbing->id)
                  ->where('status', PengajuanMagang::STATUS_DITERIMA);
            })->where('status_approve', false)->count()
            : 0;

        return $table
            ->heading(function () use ($mahasiswa, $pembimbing, $isMahasiswa, $isPembimbing, $pendingCount) {
                if ($isMahasiswa && $mahasiswa) {
                    $pengajuan = PengajuanMagang::where('mahasiswa_id', $mahasiswa->id)
                        ->where('status', PengajuanMagang::STATUS_DITERIMA)
                        ->first();

                    if (!$pengajuan || !$pengajuan->durasi_magang) {
                        Log::warning('No approved pengajuan found for mahasiswa_id: ' . $mahasiswa->id);
                        return null;
                    }

                    return new HtmlString('
                        <div style="background: linear-gradient(135deg, #e0f2fe 0%, #bfdbfe 100%);
                                   border: 2px solid #2563eb;
                                   border-radius: 12px;
                                   padding: 20px;
                                   margin: 16px 0;
                                   box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1);">
                            <div style="display: flex; align-items: center; margin-bottom: 12px;">
                                <div style="background: #2563eb;
                                           color: white;
                                           border-radius: 50%;
                                           width: 40px;
                                           height: 40px;
                                           display: flex;
                                           align-items: center;
                                           justify-content: center;
                                           font-size: 24px;
                                           margin-right: 16px;
                                           box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                                           transition: transform 0.2s;"
                                           title="Durasi Magang">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <h3 style="color: #1e40af;
                                          font-size: 20px;
                                          font-weight: 700;
                                          margin: 0;">ISI SAMPAI MINGGU KE: ' . htmlspecialchars($pengajuan->durasi_magang) . ' Boleh Lebih dan Tidak boleh kurang dari yang telah ditetapkanjl/</h3>
                            </div>
                            <div style="background: #bfdbfe;
                                       border-left: 4px solid #2563eb;
                                       padding: 12px 16px;
                                       border-radius: 6px;">
                                <p style="color: #1e40af;
                                         font-size: 16px;
                                         margin: 0;
                                         line-height: 1.5;">
                                    Anda harus mengisi laporan mingguan sampai minggu ke-' . htmlspecialchars($pengajuan->durasi_magang) . ' sesuai durasi magang yang disetujui. Setelah mengisi laporan mingguan, konfirmasi kepada pemimimbing agar bisa disetujui
                                </p>
                            </div>
                        </div>
                    ');
                }


                return null;
            })
            ->modifyQueryUsing(fn (Builder $query) => static::applyRoleBasedFilters($query, $mahasiswa, $pembimbing, $isMahasiswa, $isPembimbing))
            ->columns([
                TextColumn::make('mahasiswa.user.name')
                    ->label('Nama Mahasiswa')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->size('sm')
                    ->visible(fn () => !$isMahasiswa),

                TextColumn::make('mahasiswa.nim')
                    ->label('NIM')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->size('sm')
                    ->visible(fn () => !$isMahasiswa),

                TextColumn::make('minggu_ke')
                    ->label('Minggu Ke')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->size('sm')
                    ->color('primary'),

                TextColumn::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->date('d/m/Y')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->date('d/m/Y')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('kegiatan')
                    ->label('Kegiatan')
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->kegiatan;
                    })
                    ->size('sm'),

                TextColumn::make('status_approve')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Disetujui' : 'Pending')
                    ->colors([
                        'success' => true,
                        'warning' => false,
                    ])
                    ->size('sm'),

                TextColumn::make('pembimbingApprover.user.name')
                    ->label('Disetujui Oleh')
                    ->default('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size('sm'),

                TextColumn::make('approved_at')
                    ->label('Tanggal Disetujui')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size('sm'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size('sm'),
            ])
            ->filters([
                SelectFilter::make('mahasiswa_id')
                    ->label('Nama Mahasiswa')
                    ->relationship('mahasiswa', 'nim')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name . ' (' . $record->nim . ')')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->visible(fn () => !$isMahasiswa),

                SelectFilter::make('status_approve')
                    ->label('Status Approve')
                    ->options([
                        1 => 'Disetujui',
                        0 => 'Pending',
                    ]),

                Filter::make('minggu_ke')
                    ->form([
                        TextInput::make('minggu_dari')
                            ->label('Minggu Dari')
                            ->numeric(),
                        TextInput::make('minggu_sampai')
                            ->label('Minggu Sampai')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['minggu_dari'],
                                fn (Builder $query, $minggu): Builder => $query->where('minggu_ke', '>=', $minggu),
                            )
                            ->when(
                                $data['minggu_sampai'],
                                fn (Builder $query, $minggu): Builder => $query->where('minggu_ke', '<=', $minggu),
                            );
                    }),
            ])
            ->actions([
                // Aksi View untuk Mahasiswa
                Tables\Actions\ViewAction::make()
                    ->color('info')
                    ->visible(fn ($record) =>
                        Auth::user() &&
                        Mahasiswa::where('user_id', Auth::id())->exists() &&
                        $record->mahasiswa_id === Mahasiswa::where('user_id', Auth::id())->first()->id
                    ),

                // Aksi Edit untuk Mahasiswa
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) =>
                        Auth::user() &&
                        Mahasiswa::where('user_id', Auth::id())->exists() &&
                        $record->mahasiswa_id === Mahasiswa::where('user_id', Auth::id())->first()->id &&
                        !$record->status_approve
                    ),

                // Aksi View untuk Pembimbing (Baru Ditambahkan)
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->color('info')
                    ->icon('heroicon-o-eye')
                    ->visible(fn ($record) =>
                        Auth::user() &&
                        Pembimbing::where('user_id', Auth::id())->exists() &&
                        $record->pengajuanMagang &&
                        $record->pengajuanMagang->pembimbing_id === Pembimbing::where('user_id', Auth::id())->first()->id
                    ),

                // Aksi Approve untuk Pembimbing
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) =>
                        Auth::user() &&
                        Pembimbing::where('user_id', Auth::id())->exists() &&
                        $record->pengajuanMagang &&
                        $record->pengajuanMagang->pembimbing_id === Pembimbing::where('user_id', Auth::id())->first()->id &&
                        !$record->status_approve
                    )
                    ->form([
                        Textarea::make('catatan_pembimbing')
                            ->label('Catatan Pembimbing')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $pembimbing = Pembimbing::where('user_id', Auth::id())->first();

                        if ($record->pengajuanMagang->pembimbing_id !== $pembimbing->id) {
                            Log::warning('Unauthorized approval attempt', [
                                'pembimbing_id' => $pembimbing->id,
                                'laporan_id' => $record->id,
                            ]);
                            throw ValidationException::withMessages([
                                'catatan_pembimbing' => 'Anda tidak berwenang menyetujui laporan ini.',
                            ]);
                        }

                        $record->update([
                            'status_approve' => true,
                            'approved_by' => $pembimbing ? $pembimbing->user_id : null,
                            'approved_at' => now(),
                            'catatan_pembimbing' => $data['catatan_pembimbing'],
                        ]);

                        Log::info('Laporan approved', [
                            'laporan_id' => $record->id,
                            'approved_by' => $pembimbing->user_id,
                        ]);

                        Notification::make()
                            ->title('Laporan telah disetujui')
                            ->success()
                            ->send();
                    }),

                // Aksi Reject untuk Pembimbing
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) =>
                        Auth::user() &&
                        Pembimbing::where('user_id', Auth::id())->exists() &&
                        $record->pengajuanMagang &&
                        $record->pengajuanMagang->pembimbing_id === Pembimbing::where('user_id', Auth::id())->first()->id &&
                        $record->status_approve
                    )
                    ->form([
                        Textarea::make('catatan_pembimbing')
                            ->label('Catatan Pembimbing')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $pembimbing = Pembimbing::where('user_id', Auth::id())->first();

                        if ($record->pengajuanMagang->pembimbing_id !== $pembimbing->id) {
                            Log::warning('Unauthorized rejection attempt', [
                                'pembimbing_id' => $pembimbing->id,
                                'laporan_id' => $record->id,
                            ]);
                            throw ValidationException::withMessages([
                                'catatan_pembimbing' => 'Anda tidak berwenang menolak laporan ini.',
                            ]);
                        }

                        $record->update([
                            'status_approve' => false,
                            'approved_by' => null,
                            'approved_at' => null,
                            'catatan_pembimbing' => $data['catatan_pembimbing'],
                        ]);

                        Log::info('Laporan rejected', [
                            'laporan_id' => $record->id,
                            'rejected_by' => $pembimbing->user_id,
                        ]);

                        Notification::make()
                            ->title('Persetujuan laporan dibatalkan')
                            ->warning()
                            ->send();
                    }),

                // Aksi Delete untuk Mahasiswa
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) =>
                        Auth::user() &&
                        Mahasiswa::where('user_id', Auth::id())->exists() &&
                        $record->mahasiswa_id === Mahasiswa::where('user_id', Auth::id())->first()->id &&
                        !$record->status_approve
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () =>
                            Auth::user() &&
                            !Mahasiswa::where('user_id', Auth::id())->exists() &&
                            !Pembimbing::where('user_id', Auth::id())->exists()
                        ),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->extremePaginationLinks()
            ->defaultPaginationPageOption(25);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanMingguans::route('/'),
            'create' => Pages\CreateLaporanMingguan::route('/create'),
            'edit' => Pages\EditLaporanMingguan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (Auth::check()) {
            $user = Auth::user();
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            $pembimbing = Pembimbing::where('user_id', $user->id)->first();

            if ($pembimbing) {
                return (string) LaporanMingguan::whereHas('pengajuanMagang', function ($q) use ($pembimbing) {
                    $q->where('pembimbing_id', $pembimbing->id)
                      ->where('status', PengajuanMagang::STATUS_DITERIMA);
                })->count();
            }

            if ($mahasiswa) {
                return (string) LaporanMingguan::where('mahasiswa_id', $mahasiswa->id)->count();
            }

            return (string) static::getModel()::count();
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        if (Auth::check()) {
            $user = Auth::user();
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if ($mahasiswa) {
                $count = LaporanMingguan::where('mahasiswa_id', $mahasiswa->id)->count();
                return $count > 0 ? 'success' : 'warning';
            }
        }

        return 'primary';
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        if (!$user) {
            Log::warning('No authenticated user for canCreate check');
            return false;
        }

        $pembimbing = Pembimbing::where('user_id', $user->id)->first();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if ($pembimbing) {
            Log::info('Pembimbing cannot create laporan', ['user_id' => $user->id]);
            return false;
        }

        if ($mahasiswa) {
            $hasApprovedPengajuan = PengajuanMagang::where('mahasiswa_id', $mahasiswa->id)
                ->where('status', PengajuanMagang::STATUS_DITERIMA)
                ->exists();
            Log::info('Mahasiswa create permission check', [
                'mahasiswa_id' => $mahasiswa->id,
                'has_approved_pengajuan' => $hasApprovedPengajuan,
            ]);
            return $hasApprovedPengajuan;
        }

        Log::info('Admin can create laporan', ['user_id' => $user->id]);
        return true;
    }


public static function canViewAny(): bool
{
    $user = Auth::user();
    if (!$user) {
        Log::warning('No authenticated user for canViewAny check');
        return false;
    }

    if ($user->role === 'mahasiswa') {
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        if (!$mahasiswa) {
            Log::info('Hiding LaporanMingguanResource: No Mahasiswa record', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        // Check all required Mahasiswa fields
        $requiredFields = [
            'nim' => $mahasiswa->nim,
            'universitas' => $mahasiswa->universitas,
            'fakultas' => $mahasiswa->fakultas,
            'jurusan' => $mahasiswa->jurusan,
            'semester' => $mahasiswa->semester,
            'ipk' => $mahasiswa->ipk,
            'alamat' => $mahasiswa->alamat,
            'tanggal_lahir' => $mahasiswa->tanggal_lahir,
            'jenis_kelamin' => $mahasiswa->jenis_kelamin,
            'user_name' => $mahasiswa->user ? $mahasiswa->user->name : null,
        ];

        $isMahasiswaDataFilled = true;
        foreach ($requiredFields as $field => $value) {
            if (is_null($value) || $value === '') {
                $isMahasiswaDataFilled = false;
                break;
            }
        }

        Log::info('Can view any check for mahasiswa', [
            'user_id' => $user->id,
            'mahasiswa_id' => $mahasiswa->id,
            'required_fields' => $requiredFields,
            'is_mahasiswa_data_filled' => $isMahasiswaDataFilled,
        ]);

        if (!$isMahasiswaDataFilled) {
            Log::info('Hiding LaporanMingguanResource: Mahasiswa data incomplete', [
                'user_id' => $user->id,
                'mahasiswa_id' => $mahasiswa->id,
            ]);
            return false;
        }

        // Check for approved PengajuanMagang
        $hasApprovedPengajuan = PengajuanMagang::where('mahasiswa_id', $mahasiswa->id)
            ->where('status', PengajuanMagang::STATUS_DITERIMA)
            ->exists();

        Log::info('Mahasiswa pengajuan check', [
            'user_id' => $user->id,
            'mahasiswa_id' => $mahasiswa->id,
            'has_approved_pengajuan' => $hasApprovedPengajuan,
        ]);

        return $hasApprovedPengajuan;
    }

    if ($user->role === 'pembimbing') {
        $pembimbing = Pembimbing::where('user_id', $user->id)->first();
        if ($pembimbing) {
            $hasApprovedPengajuan = PengajuanMagang::where('pembimbing_id', $pembimbing->id)
                ->where('status', PengajuanMagang::STATUS_DITERIMA)
                ->exists();
            Log::info('Can view any check for pembimbing', [
                'user_id' => $user->id,
                'pembimbing_id' => $pembimbing->id,
                'has_approved_pengajuan' => $hasApprovedPengajuan,
            ]);
            return $hasApprovedPengajuan;
        }
        return false;
    }

    // Admin can view if there are approved PengajuanMagang
    $hasApprovedPengajuan = PengajuanMagang::where('status', PengajuanMagang::STATUS_DITERIMA)->exists();
    Log::info('Can view any check for admin', [
        'user_id' => $user->id,
        'has_approved_pengajuan' => $hasApprovedPengajuan,
    ]);
    return $hasApprovedPengajuan;
}


    public static function canView($record): bool
    {
        if (!Auth::check()) {
            Log::warning('No authenticated user for canView check');
            return false;
        }

        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        $pembimbing = Pembimbing::where('user_id', $user->id)->first();

        if ($mahasiswa) {
            $canView = $record->mahasiswa_id === $mahasiswa->id;
            Log::info('Can view check for mahasiswa', [
                'laporan_id' => $record->id,
                'mahasiswa_id' => $mahasiswa->id,
                'can_view' => $canView,
            ]);
            return $canView;
        }

        if ($pembimbing) {
            $canView = $record->pengajuanMagang && $record->pengajuanMagang->pembimbing_id === $pembimbing->id;
            Log::info('Can view check for pembimbing', [
                'laporan_id' => $record->id,
                'pembimbing_id' => $pembimbing->id,
                'can_view' => $canView,
            ]);
            return $canView;
        }

        Log::info('Admin can view laporan', ['laporan_id' => $record->id]);
        return true;
    }

    public static function canEdit($record): bool
    {
        if (!Auth::check()) {
            Log::warning('No authenticated user for canEdit check');
            return false;
        }

        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        $pembimbing = Pembimbing::where('user_id', $user->id)->first();

        if ($mahasiswa) {
            $canEdit = $record->mahasiswa_id === $mahasiswa->id && !$record->status_approve;
            Log::info('Can edit check for mahasiswa', [
                'laporan_id' => $record->id,
                'mahasiswa_id' => $mahasiswa->id,
                'can_edit' => $canEdit,
            ]);
            return $canEdit;
        }

        if ($pembimbing) {
            Log::info('Pembimbing cannot edit laporan', ['laporan_id' => $record->id]);
            return false;
        }

        Log::info('Admin can edit laporan', ['laporan_id' => $record->id]);
        return true;
    }

    public static function canDelete($record): bool
    {
        if (!Auth::check()) {
            Log::warning('No authenticated user for canDelete check');
            return false;
        }

        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        $pembimbing = Pembimbing::where('user_id', $user->id)->first();

        if ($mahasiswa) {
            $canDelete = $record->mahasiswa_id === $mahasiswa->id && !$record->status_approve;
            Log::info('Can delete check for mahasiswa', [
                'laporan_id' => $record->id,
                'mahasiswa_id' => $mahasiswa->id,
                'can_delete' => $canDelete,
            ]);
            return $canDelete;
        }

        if ($pembimbing) {
            Log::info('Pembimbing cannot delete laporan', ['laporan_id' => $record->id]);
            return false;
        }

        Log::info('Admin can delete laporan', ['laporan_id' => $record->id]);
        return true;
    }

    public static function canDeleteAny(): bool
    {
        $user = Auth::user();
        if (!$user) {
            Log::warning('No authenticated user for canDeleteAny check');
            return false;
        }

        $pembimbing = Pembimbing::where('user_id', $user->id)->first();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        $canDeleteAny = is_null($pembimbing) && is_null($mahasiswa);
        Log::info('Can delete any check', [
            'user_id' => $user->id,
            'can_delete_any' => $canDeleteAny,
        ]);
        return $canDeleteAny;
    }
}
