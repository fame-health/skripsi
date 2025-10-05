<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PengajuanMagang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;

class GenerateSurat extends Command
{
    protected $signature = 'generate:surat {id}';
    protected $description = 'Generate surat penerimaan magang untuk pengajuan tertentu';

    public function handle()
    {
        $id = $this->argument('id');

        $record = PengajuanMagang::with(['mahasiswa.user', 'pembimbing.user'])->find($id);

        if (!$record) {
            $this->error("PengajuanMagang dengan ID $id tidak ditemukan!");
            return 1;
        }

        // Ambil user admin sebagai verifikator
        $adminUser = \App\Models\User::where('role', 'admin')->first();

        // Ambil template aktif untuk surat penerimaan
        $template = \App\Models\TemplateSurat::where('jenis_surat', \App\Models\TemplateSurat::JENIS_PENERIMAAN)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            $this->error('Tidak ada template surat penerimaan aktif!');
            return 1;
        }

        // Data untuk dimasukkan ke Blade
        $pdfData = [
            'nomer_surat'      => $record->nomor_surat ?? '070/DISBUD/' . date('Y') . '/' . str_pad($record->id, 3, '0', STR_PAD_LEFT),
            'id_pengajuan'     => $record->id,
            'mahasiswa_name'   => $record->mahasiswa->user->name ?? '-',
            'nim'              => $record->mahasiswa->nim ?? '-',
            'pembimbing_name'  => $record->pembimbing->user->name ?? '-',
            'tanggal_mulai'    => $record->tanggal_mulai ? $record->tanggal_mulai->format('d F Y') : '-',
            'tanggal_selesai'  => $record->tanggal_selesai ? $record->tanggal_selesai->format('d F Y') : '-',
            'bidang_diminati'  => $record->bidang_diminati ?? '-',
            'qr_code_path'     => public_path('images/sample-qr.png'),
            'verified_by'      => $adminUser->name ?? 'Admin',
            'template'         => $template,
        ];

        // Render Blade dari template surat penerimaan
        $renderedContent = Blade::render($template->content_template, $pdfData);

        // Buat PDF
        $pdf = Pdf::loadHTML($renderedContent);

        $pdfPath = storage_path("app/public/pengajuan-magang/surat_penerimaan_{$id}.pdf");

        Storage::disk('public')->put("pengajuan-magang/surat_penerimaan_{$id}.pdf", $pdf->output());

        $this->info("Surat penerimaan berhasil dibuat di: " . $pdfPath);

        return 0;
    }
}
