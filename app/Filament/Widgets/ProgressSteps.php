<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\Mahasiswa;
use App\Models\PengajuanMagang;

class ProgressSteps extends Widget
{
    protected static string $view = 'filament.widgets.progress-steps';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->role === 'mahasiswa';
    }

    public function getStepsData(): array
    {
        $user = Auth::user();

        $steps = [
            [
                'title' => 'Isi Data Mahasiswa',
                'description' => 'Mahasiswa wajib mengisi data mahasiswa.',
                'completed' => false,
                'active' => false,
                'url' => route('filament.dashboard.resources.mahasiswas.create'),
                'button_text' => 'Isi Data',
                'status' => null,
                'completed_at' => null,
                'keterangan' => null,
            ],
            [
                'title' => 'Pengajuan Magang',
                'description' => 'Submit pengajuan magang dengan surat permohonan dan KTM.',
                'completed' => false,
                'active' => false,
                'url' => route('filament.dashboard.resources.pengajuan-magangs.create'),
                'button_text' => 'Ajukan Magang',
                'status' => null,
                'completed_at' => null,
                'keterangan' => null,
            ],
            [
                'title' => 'Logbook',
                'description' => 'Mahasiswa wajib mengisi laporan mingguan sesuai durasi magang.',
                'completed' => false,
                'active' => false,
                'url' => route('filament.dashboard.resources.laporan-mingguans.create'),
                'button_text' => 'Isi Logbook',
                'status' => null,
                'completed_at' => null,
                'keterangan' => null,
            ],
            [
                'title' => 'Penilaian',
                'description' => 'Menunggu penilaian dari pembimbing.',
                'completed' => false,
                'active' => false,
                'url' => route('filament.dashboard.resources.penilaians.index'),
                'button_text' => 'Lihat Penilaian',
                'status' => null,
                'completed_at' => null,
                'keterangan' => null,
            ],
            [
                'title' => 'Laporan Akhir',
                'description' => 'Upload laporan akhir dan dapatkan sertifikat.',
                'completed' => false,
                'active' => false,
                'url' => route('filament.dashboard.resources.final-laporans.index'),
                'button_text' => 'Upload Laporan',
                'status' => null,
                'completed_at' => null,
                'keterangan' => null,
            ],
        ];

        if ($user && $user->role === 'mahasiswa') {
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            // Step 1: Mahasiswa
            if ($mahasiswa) {
                $steps[0]['completed'] = true;
                $steps[0]['url'] = route('filament.dashboard.resources.mahasiswas.edit', $mahasiswa->id);
                $steps[0]['button_text'] = 'Edit Data';
                $steps[0]['completed_at'] = $mahasiswa->created_at->format('d M Y');
                $steps[0]['keterangan'] = 'Data mahasiswa telah diisi. Anda dapat mengedit jika diperlukan.';
            } else {
                $steps[0]['active'] = true;
                $steps[0]['keterangan'] = 'Lengkapi data mahasiswa untuk memulai proses magang.';
            }

            // Step 2: Pengajuan Magang
            if ($mahasiswa) {
                $pengajuan = PengajuanMagang::where('mahasiswa_id', $mahasiswa->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($pengajuan) {
                    $steps[1]['status'] = match ($pengajuan->status) {
                        PengajuanMagang::STATUS_PENDING => 'Sedang Diproses',
                        PengajuanMagang::STATUS_DITERIMA => 'Diterima',
                        PengajuanMagang::STATUS_DITOLAK => 'Ditolak',
                        PengajuanMagang::STATUS_SELESAI => 'Selesai',
                        default => 'Sedang Diproses',
                    };

                    $steps[1]['keterangan'] = match ($pengajuan->status) {
                        PengajuanMagang::STATUS_PENDING => 'Pengajuan sedang diverifikasi oleh admin. Harap tunggu konfirmasi.',
                        PengajuanMagang::STATUS_DITERIMA => 'Pengajuan Anda telah disetujui. Lanjutkan ke tahap Logbook.',
                        PengajuanMagang::STATUS_DITOLAK => $pengajuan->alasan_penolakan ?: 'Pengajuan ditolak. Silakan perbaiki dan ajukan ulang.',
                        PengajuanMagang::STATUS_SELESAI => 'Magang telah selesai. Lanjutkan ke tahap Laporan Akhir.',
                        default => 'Pengajuan sedang dalam proses verifikasi.',
                    };

                    if (in_array($pengajuan->status, [PengajuanMagang::STATUS_DITERIMA, PengajuanMagang::STATUS_SELESAI])) {
                        $steps[1]['completed'] = true;
                        $steps[1]['url'] = route('filament.dashboard.resources.pengajuan-magangs.index');
                        $steps[1]['button_text'] = 'Lihat Pengajuan';
                        $steps[1]['completed_at'] = $pengajuan->tanggal_verifikasi?->format('d M Y');
                    } elseif ($pengajuan->status === PengajuanMagang::STATUS_PENDING) {
                        $steps[1]['url'] = route('filament.dashboard.resources.pengajuan-magangs.edit', $pengajuan->id);
                        $steps[1]['button_text'] = 'Edit Pengajuan';
                    } elseif ($pengajuan->status === PengajuanMagang::STATUS_DITOLAK) {
                        $steps[1]['url'] = route('filament.dashboard.resources.pengajuan-magangs.create');
                        $steps[1]['button_text'] = 'Ajukan Ulang';
                    }

                    $steps[1]['active'] = !$steps[0]['active'] && !$steps[1]['completed'];
                } else {
                    $steps[1]['active'] = !$steps[0]['active'];
                    $steps[1]['status'] = 'Belum Diajukan';
                    $steps[1]['keterangan'] = 'Silakan ajukan magang dengan mengunggah surat permohonan dan KTM.';
                }
            }
        }

        // Tentukan warna status
foreach ($steps as &$step) {
    // CEK DITOLAK DULU (PRIORITAS TERTINGGI)
    if (isset($step['status']) && $step['status'] === 'Ditolak') {
        $step['status_color'] = 'danger';
    } elseif ($step['completed']) {
        $step['status_color'] = 'success';
    } elseif ($step['active']) {
        $step['status_color'] = 'warning';
    } else {
        $step['status_color'] = 'neutral';
    }
}

        return $steps;
    }
}
