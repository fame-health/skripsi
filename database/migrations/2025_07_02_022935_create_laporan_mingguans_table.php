<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('laporan_mingguan', function (Blueprint $table) {
            $table->engine = 'InnoDB'; // Pastikan menggunakan engine InnoDB
            $table->id();

            // Perbaikan: Tentukan nama tabel referensi secara eksplisit
            $table->foreignId('pengajuan_magang_id')
                  ->constrained('pengajuan_magang') // Tambahkan nama tabel disini
                  ->onDelete('cascade');

            $table->foreignId('mahasiswa_id')
                  ->constrained('mahasiswa')
                  ->onDelete('cascade');

            $table->integer('minggu_ke');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('kegiatan');
            $table->text('pencapaian');
            $table->text('kendala');
            $table->text('rencana_minggu_depan');
            $table->boolean('status_approve')->default(false);

            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->timestamp('approved_at')->nullable();
            $table->text('catatan_pembimbing')->nullable();
            $table->timestamps();

            // Tambahkan index untuk performa
            $table->index('pengajuan_magang_id');
            $table->index('mahasiswa_id');
            $table->index('approved_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('laporan_mingguan');
    }
};
