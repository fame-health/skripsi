<?php

namespace App\Livewire;

use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use App\Models\Mahasiswa;
use App\Models\LaporanMingguan;
use App\Models\CatatanPembimbing;
use App\Models\PengajuanMagang;
use App\Models\Penilaian;

class MahasiswaPrint extends Component
{
    public $steps = [];
    public $pengajuanMagang;
    public $penilaian;
    public $mahasiswa;

    public function mount()
    {
        $user = Auth::user();
        $this->mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        // Jika belum ada data mahasiswa, jangan lanjutkan proses lainnya
        if (!$this->mahasiswa) {
            return;
        }

        $this->pengajuanMagang = PengajuanMagang::where('mahasiswa_id', $this->mahasiswa->id)
            ->whereIn('status', [PengajuanMagang::STATUS_DITERIMA, PengajuanMagang::STATUS_SELESAI])
            ->latest()
            ->first();

        $laporanMingguan = LaporanMingguan::where('mahasiswa_id', $this->mahasiswa->id)
            ->where('pengajuan_magang_id', optional($this->pengajuanMagang)->id)
            ->orderBy('minggu_ke')
            ->get();

        $this->steps = $laporanMingguan->map(function ($laporan) {
            return [
                'title' => "Minggu ke-{$laporan->minggu_ke}",
                'description' => $laporan->kegiatan,
                'completed' => $laporan->status_approve == 1,
                'active' => $laporan->status_approve == 0,
                'completed_at' => $laporan->approved_at ? \Carbon\Carbon::parse($laporan->approved_at)->format('d M Y') : null,
                'pencapaian' => $laporan->pencapaian,
                'kendala' => $laporan->kendala,
                'rencana_minggu_depan' => $laporan->rencana_minggu_depan,
                'catatan_pembimbing' => $laporan->catatan_pembimbing,
            ];
        })->toArray();

        $this->penilaian = Penilaian::where('mahasiswa_id', $this->mahasiswa->id)
            ->when($this->pengajuanMagang && $this->pengajuanMagang->pembimbing_id, function ($query) {
                return $query->where('pembimbing_id', $this->pengajuanMagang->pembimbing_id);
            })
            ->get();
    }

    public function printPdf()
    {
        $user = Auth::user();
        $mahasiswa = Mahasiswa::with('user')->where('user_id', $user->id)->first();

        if (!$mahasiswa) {
            return back()->with('error', 'Data mahasiswa belum tersedia.');
        }

        $catatanPembimbing = CatatanPembimbing::where('mahasiswa_id', $mahasiswa->id)
            ->where('pengajuan_magang_id', optional($this->pengajuanMagang)->id)
            ->get();

        $data = [
            'mahasiswa' => $mahasiswa,
            'steps' => $this->steps,
            'completedCount' => count(array_filter($this->steps, fn($step) => $step['completed'])),
            'totalSteps' => count($this->steps),
            'pengajuanMagang' => $this->pengajuanMagang,
            'penilaian' => $this->penilaian,
            'catatanPembimbing' => $catatanPembimbing,
        ];

        $pdf = Pdf::loadView('livewire.pdf.mahasiswa-progress', $data);
        return response()->streamDownload(
            fn () => print($pdf->output()),
            'Laporan-Magang-' . ($mahasiswa->user->name ?? 'mahasiswa') . '.pdf'
        );
    }

    public function render()
    {
        return view('livewire.mahasiswa-print', [
            'mahasiswa' => $this->mahasiswa,
        ]);
    }
}
