<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PengajuanMagang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;

class GenerateSertifikat extends Command
{
    protected $signature = 'generate:sertifikat {id}';
    protected $description = 'Generate sertifikat PDF untuk pengajuan magang tertentu';

    public function handle()
    {
        $id = $this->argument('id');

        $record = PengajuanMagang::with(['mahasiswa.user', 'pembimbing.user'])->find($id);

        if (!$record) {
            $this->error("PengajuanMagang dengan ID $id tidak ditemukan!");
            return 1;
        }

        // Kita pakai user admin sementara untuk verified_by
        $adminUser = \App\Models\User::where('role', 'admin')->first();

        // Ambil template aktif
        $template = \App\Models\TemplateSurat::where('jenis_surat', \App\Models\TemplateSurat::JENIS_SERTIFIKAT)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            $this->error('Tidak ada template sertifikat aktif!');
            return 1;
        }

        $pdfData = [
            'mahasiswa_name' => $record->mahasiswa->user->name,
            'nim' => $record->mahasiswa->nim,
            'pembimbing_name' => $record->pembimbing->user->name ?? 'N/A',
            'tanggal_mulai' => $record->tanggal_mulai->format('d F Y'),
            'tanggal_selesai' => $record->tanggal_selesai->format('d F Y'),
            'bidang_diminati' => $record->bidang_diminati,
            'qr_code_path' => public_path('images/sample-qr.png'), // sementara pakai QR dummy
            'tanggal_verifikasi' => now()->format('d F Y'),
            'verified_by' => $adminUser->name ?? 'Admin',
        ];

        $renderedContent = Blade::render($template->content_template, $pdfData);

        $pdf = Pdf::loadHTML($renderedContent);

        $pdfPath = storage_path("app/public/pengajuan-magang/sertifikat_test_{$id}.pdf");

        Storage::disk('public')->put("pengajuan-magang/sertifikat_test_{$id}.pdf", $pdf->output());

        $this->info("PDF berhasil dibuat di: " . $pdfPath);

        return 0;
    }
}

