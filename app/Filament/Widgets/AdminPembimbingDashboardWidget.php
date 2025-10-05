<?php

namespace App\Filament\Widgets;

use App\Models\PengajuanMagang;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class AdminPembimbingDashboardWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $stats = [];
        $now = Carbon::today('Asia/Jakarta'); // Use date only for comparison

        if ($user?->isAdmin()) {
            // Statistik untuk Admin
            $pendingPengajuan = PengajuanMagang::where('status', PengajuanMagang::STATUS_PENDING)->count();
            $completedMahasiswa = PengajuanMagang::where('status', PengajuanMagang::STATUS_SELESAI)
                ->distinct('mahasiswa_id')
                ->count('mahasiswa_id');
            $activeMahasiswa = PengajuanMagang::where('status', PengajuanMagang::STATUS_DITERIMA)
                ->where('tanggal_mulai', '<=', $now)
                ->where('tanggal_selesai', '>=', $now)
                ->distinct('mahasiswa_id')
                ->count('mahasiswa_id');
            $activePembimbing = PengajuanMagang::where('status', PengajuanMagang::STATUS_DITERIMA)
                ->where('tanggal_mulai', '<=', $now)
                ->where('tanggal_selesai', '>=', $now)
                ->whereNotNull('pembimbing_id')
                ->distinct('pembimbing_id')
                ->count('pembimbing_id');
            $totalPengajuan = PengajuanMagang::count();

            $stats = [
                Stat::make('Pengajuan Pending', $pendingPengajuan)
                    ->description(new HtmlString('<span style="color: #d97706; font-weight: 600;">Pengajuan yang menunggu persetujuan</span>'))
                    ->descriptionIcon('heroicon-m-clock')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->chart([7, 4, 6, 8, 5, 9, 7])
                    ->color('warning')
                    ->extraAttributes([
                        'style' => 'background: linear-gradient(135deg, #fef3c7 0%, #fde68a 50%, #f59e0b 100%); border-left: 6px solid #f59e0b; border-radius: 12px; box-shadow: 0 8px 25px rgba(245, 158, 11, 0.15); transition: all 0.3s ease;',
                        'onmouseover' => 'this.style.transform="translateY(-4px)"; this.style.boxShadow="0 12px 35px rgba(245, 158, 11, 0.25)";',
                        'onmouseout' => 'this.style.transform="translateY(0px)"; this.style.boxShadow="0 8px 25px rgba(245, 158, 11, 0.15)";'
                    ]),

                Stat::make('Mahasiswa Aktif', $activeMahasiswa)
                    ->description(new HtmlString('<span style="color: #2563eb; font-weight: 600;">Mahasiswa yang sedang melaksanakan magang</span>'))
                    ->descriptionIcon('heroicon-m-user-group')
                    ->icon('heroicon-o-users')
                    ->chart([10, 8, 12, 9, 15, 11, 14])
                    ->color('primary')
                    ->extraAttributes([
                        'style' => 'background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 50%, #3b82f6 100%); border-left: 6px solid #3b82f6; border-radius: 12px; box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15); transition: all 0.3s ease;',
                        'onmouseover' => 'this.style.transform="translateY(-4px)"; this.style.boxShadow="0 12px 35px rgba(59, 130, 246, 0.25)";',
                        'onmouseout' => 'this.style.transform="translateY(0px)"; this.style.boxShadow="0 8px 25px rgba(59, 130, 246, 0.15)";'
                    ]),

                Stat::make('Mahasiswa Selesai', $completedMahasiswa)
                    ->description(new HtmlString('<span style="color: #059669; font-weight: 600;">Total mahasiswa yang telah menyelesaikan magang</span>'))
                    ->descriptionIcon('heroicon-m-check-badge')
                    ->icon('heroicon-o-academic-cap')
                    ->chart([2, 5, 8, 12, 15, 18, 20])
                    ->color('success')
                    ->extraAttributes([
                        'style' => 'background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 50%, #10b981 100%); border-left: 6px solid #10b981; border-radius: 12px; box-shadow: 0 8px 25px rgba(16, 185, 129, 0.15); transition: all 0.3s ease;',
                        'onmouseover' => 'this.style.transform="translateY(-4px)"; this.style.boxShadow="0 12px 35px rgba(16, 185, 129, 0.25)";',
                        'onmouseout' => 'this.style.transform="translateY(0px)"; this.style.boxShadow="0 8px 25px rgba(16, 185, 129, 0.15)";'
                    ]),

                Stat::make('Pembimbing Aktif', $activePembimbing)
                    ->description(new HtmlString('<span style="color: #0891b2; font-weight: 600;">Dosen pembimbing yang sedang mengawasi</span>'))
                    ->descriptionIcon('heroicon-m-user-circle')
                    ->icon('heroicon-o-identification')
                    ->chart([3, 4, 5, 4, 6, 5, 7])
                    ->color('info')
                    ->extraAttributes([
                        'style' => 'background: linear-gradient(135deg, #cffafe 0%, #67e8f9 50%, #06b6d4 100%); border-left: 6px solid #06b6d4; border-radius: 12px; box-shadow: 0 8px 25px rgba(6, 182, 212, 0.15); transition: all 0.3s ease;',
                        'onmouseover' => 'this.style.transform="translateY(-4px)"; this.style.boxShadow="0 12px 35px rgba(6, 182, 212, 0.25)";',
                        'onmouseout' => 'this.style.transform="translateY(0px)"; this.style.boxShadow="0 8px 25px rgba(6, 182, 212, 0.15)";'
                    ]),

                Stat::make('Total Pengajuan', $totalPengajuan)
                    ->description(new HtmlString('<span style="color: #7c3aed; font-weight: 600;">Seluruh pengajuan magang yang masuk</span>'))
                    ->descriptionIcon('heroicon-m-document-text')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->chart([15, 18, 22, 25, 28, 32, 35])
                    ->color('secondary')
                    ->extraAttributes([
                        'style' => 'background: linear-gradient(135deg, #ede9fe 0%, #c4b5fd 50%, #8b5cf6 100%); border-left: 6px solid #8b5cf6; border-radius: 12px; box-shadow: 0 8px 25px rgba(139, 92, 246, 0.15); transition: all 0.3s ease;',
                        'onmouseover' => 'this.style.transform="translateY(-4px)"; this.style.boxShadow="0 12px 35px rgba(139, 92, 246, 0.25)";',
                        'onmouseout' => 'this.style.transform="translateY(0px)"; this.style.boxShadow="0 8px 25px rgba(139, 92, 246, 0.15)";'
                    ]),
            ];
        } elseif ($user?->isPembimbing()) {
            // Statistik untuk Pembimbing
            $pembimbingId = $user->pembimbing?->id;

            if ($pembimbingId) {
                $pendingPengajuan = PengajuanMagang::where('pembimbing_id', $pembimbingId)
                    ->where('status', PengajuanMagang::STATUS_PENDING)
                    ->count();
                $completedMahasiswa = PengajuanMagang::where('pembimbing_id', $pembimbingId)
                    ->where('status', PengajuanMagang::STATUS_SELESAI)
                    ->distinct('mahasiswa_id')
                    ->count('mahasiswa_id');
                $activeMahasiswa = PengajuanMagang::where('pembimbing_id', $pembimbingId)
                    ->where('status', PengajuanMagang::STATUS_DITERIMA)
                    ->where('tanggal_mulai', '<=', $now)
                    ->where('tanggal_selesai', '>=', $now)
                    ->distinct('mahasiswa_id')
                    ->count('mahasiswa_id');
                $totalBimbingan = PengajuanMagang::where('pembimbing_id', $pembimbingId)->count();

                $stats = [
                    Stat::make('Pengajuan Pending', $pendingPengajuan)
                        ->description(new HtmlString('<span style="color: #d97706; font-weight: 600;">Pengajuan yang perlu disetujui</span>'))
                        ->descriptionIcon('heroicon-m-clock')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->chart([3, 5, 2, 7, 4, 6, 8])
                        ->color('warning')
                        ->extraAttributes([
                            'style' => 'background: linear-gradient(135deg, #fef3c7 0%, #fde68a 50%, #f59e0b 100%); border-left: 6px solid #f59e0b; border-radius: 12px; box-shadow: 0 8px 25px rgba(245, 158, 11, 0.15); transition: all 0.3s ease;',
                            'onmouseover' => 'this.style.transform="translateY(-4px)"; this.style.boxShadow="0 12px 35px rgba(245, 158, 11, 0.25)";',
                            'onmouseout' => 'this.style.transform="translateY(0px)"; this.style.boxShadow="0 8px 25px rgba(245, 158, 11, 0.15)";'
                        ]),

                    Stat::make('Mahasiswa Aktif', $activeMahasiswa)
                        ->description(new HtmlString('<span style="color: #2563eb; font-weight: 600;">Mahasiswa yang sedang dibimbing</span>'))
                        ->descriptionIcon('heroicon-m-user-group')
                        ->icon('heroicon-o-users')
                        ->chart([5, 8, 6, 10, 7, 9, 11])
                        ->color('primary')
                        ->extraAttributes([
                            'style' => 'background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 50%, #3b82f6 100%); border-left: 6px solid #3b82f6; border-radius: 12px; box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15); transition: all 0.3s ease;',
                            'onmouseover' => 'this.style.transform="translateY(-4px)"; this.style.boxShadow="0 12px 35px rgba(59, 130, 246, 0.25)";',
                            'onmouseout' => 'this.style.transform="translateY(0px)"; this.style.boxShadow="0 8px 25px rgba(59, 130, 246, 0.15)";'
                        ]),

                    Stat::make('Mahasiswa Selesai', $completedMahasiswa)
                        ->description(new HtmlString('<span style="color: #059669; font-weight: 600;">Mahasiswa bimbingan yang telah selesai</span>'))
                        ->descriptionIcon('heroicon-m-check-badge')
                        ->icon('heroicon-o-academic-cap')
                        ->chart([1, 3, 6, 8, 10, 12, 15])
                        ->color('success')
                        ->extraAttributes([
                            'style' => 'background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 50%, #10b981 100%); border-left: 6px solid #10b981; border-radius: 12px; box-shadow: 0 8px 25px rgba(16, 185, 129, 0.15); transition: all 0.3s ease;',
                            'onmouseover' => 'this.style.transform="translateY(-4px)"; this.style.boxShadow="0 12px 35px rgba(16, 185, 129, 0.25)";',
                            'onmouseout' => 'this.style.transform="translateY(0px)"; this.style.boxShadow="0 8px 25px rgba(16, 185, 129, 0.15)";'
                        ]),

                    Stat::make('Total Bimbingan', $totalBimbingan)
                        ->description(new HtmlString('<span style="color: #db2777; font-weight: 600;">Seluruh mahasiswa yang pernah dibimbing</span>'))
                        ->descriptionIcon('heroicon-m-chart-bar')
                        ->icon('heroicon-o-chart-bar-square')
                        ->chart([8, 12, 16, 20, 24, 28, 32])
                        ->color('danger')
                        ->extraAttributes([
                            'style' => 'background: linear-gradient(135deg, #fce7f3 0%, #f9a8d4 50%, #ec4899 100%); border-left: 6px solid #ec4899; border-radius: 12px; box-shadow: 0 8px 25px rgba(236, 72, 153, 0.15); transition: all 0.3s ease;',
                            'onmouseover' => 'this.style.transform="translateY(-4px)"; this.style.boxShadow="0 12px 35px rgba(236, 72, 153, 0.25)";',
                            'onmouseout' => 'this.style.transform="translateY(0px)"; this.style.boxShadow="0 8px 25px rgba(236, 72, 153, 0.15)";'
                        ]),
                ];
            } else {
                $stats[] = Stat::make('No Data', '0')
                    ->description('Tidak ada data pembimbing tersedia')
                    ->descriptionIcon('heroicon-m-information-circle')
                    ->color('gray');
            }
        }

        if (empty($stats)) {
            $stats[] = Stat::make('No Data', '0')
                ->description('Tidak ada data yang tersedia saat ini')
                ->descriptionIcon('heroicon-m-information-circle')
                ->color('gray');
        }

        return $stats;
    }

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isPembimbing() ?: false;
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getHeading(): string
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->isAdmin()) {
            return '🎯 Admin Dashboard Overview';
        } elseif ($user?->isPembimbing()) {
            return '👨‍🏫 Pembimbing Dashboard Overview';
        }

        return 'Dashboard Overview';
    }
}
