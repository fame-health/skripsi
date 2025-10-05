<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $pengajuan_magang_id
 * @property int $mahasiswa_id
 * @property int $minggu_ke
 * @property string $tanggal_mulai
 * @property string $tanggal_selesai
 * @property string $kegiatan
 * @property string $pencapaian
 * @property string $kendala
 * @property string $rencana_minggu_depan
 * @property int $status_approve
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property string|null $catatan_pembimbing
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Mahasiswa $mahasiswa
 * @property-read \App\Models\Pembimbing|null $pembimbingApprover
 * @property-read \App\Models\PengajuanMagang $pengajuanMagang
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereCatatanPembimbing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereKegiatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereKendala($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereMahasiswaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereMingguKe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan wherePencapaian($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan wherePengajuanMagangId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereRencanaMingguDepan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereStatusApprove($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereTanggalMulai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereTanggalSelesai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LaporanMingguan whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LaporanMingguan extends Model
{
    use HasFactory;

    protected $table = 'laporan_mingguan';

    protected $fillable = [
        'pengajuan_magang_id',
        'mahasiswa_id',
        'minggu_ke',
        'tanggal_mulai',
        'tanggal_selesai',
        'kegiatan',
        'pencapaian',
        'kendala',
        'rencana_minggu_depan',
        'status_approve',
        'approved_by',
        'approved_at',
        'catatan_pembimbing',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    public function pengajuanMagang()
    {
        return $this->belongsTo(PengajuanMagang::class, 'pengajuan_magang_id');
    }

    public function pembimbingApprover()
    {
        return $this->belongsTo(Pembimbing::class, 'approved_by', 'user_id');
    }
}
