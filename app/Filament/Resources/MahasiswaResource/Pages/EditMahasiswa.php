<?php

namespace App\Filament\Resources\MahasiswaResource\Pages;

use App\Filament\Resources\MahasiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;

class EditMahasiswa extends EditRecord
{
    protected static string $resource = MahasiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ❌ Mahasiswa tidak bisa hapus, hanya admin
            Actions\DeleteAction::make()
                ->visible(fn () => Auth::check() && Auth::user()->role === 'admin'),
        ];
    }

    // ✅ Schema form edit
    public function form(Form $form): Form
    {
        $user = Auth::user();
        $isMahasiswa = $user && $user->role === 'mahasiswa';

        return $form->schema([
            // ✅ Nama Mahasiswa (editable)

            Forms\Components\Section::make('Data Akademik')
                ->icon('heroicon-o-academic-cap')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('nim')
                            ->label('NIM / NIP Siswa')
                            ->disabled($isMahasiswa),

                        Forms\Components\TextInput::make('universitas')
                            ->label('Universitas / Sekolah')
                            ->disabled($isMahasiswa),

                        Forms\Components\TextInput::make('fakultas')
                            ->label('Fakultas')
                            ->disabled($isMahasiswa),

                        Forms\Components\TextInput::make('jurusan')
                            ->label('Jurusan/Program Studi')
                            ->disabled($isMahasiswa),

                        Forms\Components\TextInput::make('semester')
                            ->label('Semester / Kelas')
                            ->disabled($isMahasiswa),

                        Forms\Components\TextInput::make('ipk')
                            ->label('IPK / Nilai')
                            ->disabled($isMahasiswa),
                    ]),
                ])
                ->extraAttributes(['class' => 'shadow-md rounded-lg border border-gray-200']),

            Forms\Components\Section::make('Data Pribadi')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\Textarea::make('alamat')
                        ->label('Domisili')
                        ->rows(4),

                    Forms\Components\DatePicker::make('tanggal_lahir')
                        ->label('Tanggal Lahir'),

                    Forms\Components\Select::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->options([
                            'L' => 'Laki-laki',
                            'P' => 'Perempuan',
                        ]),
                ])
                ->extraAttributes(['class' => 'shadow-md rounded-lg border border-gray-200 mt-6']),
        ]);
    }

    // ✅ Simpan juga perubahan nama user
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['user']['name'])) {
            $this->record->user->name = $data['user']['name'];
            $this->record->user->save();
            unset($data['user']); // biar gak error karena field relasi
        }

        return $data;
    }

    // ✅ Akses kontrol (admin bebas, mahasiswa hanya data miliknya)
    protected function authorizeAccess(): void
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return; // admin bisa edit siapa saja
        }

        if ($user->role === 'mahasiswa') {
            if ($this->record->user_id !== $user->id) {
                abort(403, 'Anda tidak boleh mengedit data mahasiswa lain.');
            }
            return;
        }

        abort(403, 'Anda tidak memiliki akses.');
    }
}
