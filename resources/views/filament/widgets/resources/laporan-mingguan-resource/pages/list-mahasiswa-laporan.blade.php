<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Daftar Mahasiswa dengan Logbook</x-slot>
        <p class="text-gray-600">Pilih mahasiswa untuk melihat daftar laporan mingguan (logbook) mereka.</p>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
