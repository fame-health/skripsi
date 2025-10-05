@php
    $steps = $this->getStepsData();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-academic-cap class="w-6 h-6 text-primary-600" />
                <span class="text-lg font-semibold text-gray-800">Prosedur Pelaksanaan Magang</span>
            </div>
        </x-slot>

        <div class="space-y-4 relative">
            @foreach ($steps as $index => $step)
                <div class="relative flex flex-col sm:flex-row items-start gap-4 group bg-white p-4 sm:p-5 rounded-lg shadow-sm border-l-4
                    {{ $step['completed'] ? 'border-success-500' : ($step['active'] ? 'border-primary-500' : 'border-gray-300') }}">
                    <!-- Stepper Line -->
                    @if (!$loop->last)
                        <div class="absolute left-5 top-12 w-0.5 h-[calc(100%+1rem)]
                            {{ $step['completed'] ? 'bg-success-300' : 'bg-gray-200' }}
                            group-last:h-0 z-0"></div>
                    @endif

                    <div class="relative z-10 flex-shrink-0">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold border-2
                            {{ $step['completed'] ? 'bg-success-50 border-success-400 text-success-700' :
                               ($step['active'] ? 'bg-primary-50 border-primary-400 text-primary-700' :
                               'bg-gray-50 border-gray-200 text-gray-400') }}">
                            {{ $index + 1 }}
                            @if($step['completed'])
                                <x-heroicon-s-check class="absolute -right-1 -bottom-1 w-4 h-4 p-0.5 bg-success-500 text-white rounded-full border-2 border-white" />
                            @endif
                        </div>
                    </div>

                    <div class="flex-1 pt-1 w-full">
                        <h3 class="flex items-center gap-2 text-base font-semibold
                            {{ $step['completed'] ? 'text-success-800' :
                               ($step['active'] ? 'text-primary-800' : 'text-gray-500') }}">
                            @if($step['completed'])
                                <x-heroicon-s-check class="w-4 h-4 text-success-500" />
                            @endif
                            <span class="break-words">{{ $step['title'] }}</span>
                        </h3>

                        <p class="mt-1 text-sm text-gray-600 break-words">{{ $step['description'] }}</p>

                        @if($step['status'])
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <x-filament::badge
                                    color="{{ $step['status'] === 'Diterima' || $step['status'] === 'Selesai' ? 'success' :
                                             ($step['status'] === 'Ditolak' ? 'danger' :
                                             ($step['status'] === 'Sedang Diproses' ? 'warning' : 'gray')) }}"
                                    size="xs sm:sm"
                                    icon="{{ $step['status'] === 'Diterima' || $step['status'] === 'Selesai' ? 'heroicon-o-check-circle' :
                                             ($step['status'] === 'Ditolak' ? 'heroicon-o-x-circle' :
                                             ($step['status'] === 'Sedang Diproses' ? 'heroicon-o-clock' : 'heroicon-o-minus-circle')) }}"
                                    class="inline-flex items-center"
                                >
                                    Status: {{ $step['status'] }}
                                </x-filament::badge>
                            </div>
                        @endif

                        @if($step['keterangan'])
                            <p class="mt-1 text-sm text-gray-600 italic bg-gray-100 p-2 rounded-md break-words">
                                {{ $step['keterangan'] }}
                            </p>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <x-filament::badge
                                color="{{ $step['completed'] ? 'success' : ($step['active'] ? 'warning' : 'gray') }}"
                                size="xs sm:sm"
                                icon="{{ $step['completed'] ? 'heroicon-o-check-circle' :
                                      ($step['active'] ? 'heroicon-o-arrow-path' : 'heroicon-o-clock') }}"
                                class="inline-flex items-center"
                            >
                                {{ $step['completed'] ? 'Selesai' : ($step['active'] ? 'Sedang Berjalan' : 'Menunggu') }}
                            </x-filament::badge>

                            @if($step['completed'] && $step['completed_at'])
                                <span class="text-xs text-success-500">{{ $step['completed_at'] }}</span>
                            @endif

                            <a href="{{ $step['url'] }}"
                               class="inline-flex items-center px-3 sm:px-4 py-1 sm:py-1.5 text-xs sm:text-sm font-medium rounded-md transition-colors duration-200
                                      {{ $step['completed'] ? 'bg-success-600 hover:bg-success-700 text-white' :
                                         ($step['active'] ? 'bg-primary-600 hover:bg-primary-700 text-white' :
                                         'bg-gray-300 text-gray-500 cursor-not-allowed') }}"
                               @if (!$step['completed'] && !$step['active']) disabled title="Lengkapi langkah sebelumnya terlebih dahulu" @endif>
                                {{ $step['button_text'] }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 pt-4 border-t border-gray-200">
            @php
                $completedCount = count(array_filter($steps, fn($step) => $step['completed']));
                $totalSteps = count($steps);
                $percentage = round(($completedCount / $totalSteps) * 100);
            @endphp

            <div class="mb-2 flex justify-between text-sm font-medium text-gray-700">
                <span>Progress Keseluruhan</span>
                <span>{{ $percentage }}%</span>
            </div>

            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-300"
                     style="width: {{ $percentage }}%"></div>
            </div>

            <div class="mt-1 text-xs text-gray-500 text-right">
                {{ $completedCount }} dari {{ $totalSteps }} langkah selesai
            </div>

            <!-- Print Button -->
            <div class="mt-4 flex justify-end">
                <livewire:mahasiswa-print :steps="$steps" />
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<script>
    document.querySelectorAll('.progress-steps a[disabled]').forEach(button => {
        button.addEventListener('click', (e) => e.preventDefault());
    });
</script>

<style>
    .progress-steps .group {
        transition: transform 0.2s ease;
    }
    .progress-steps .group:hover {
        transform: translateY(-2px);
    }
    @media (max-width: 640px) {
        .progress-steps .group {
            padding: 1rem;
        }
        .progress-steps .step-line {
            left: 1.25rem;
            top: 2.5rem;
            height: calc(100% + 0.5rem);
        }
    }
</style>
