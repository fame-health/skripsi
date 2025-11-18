@php
    $steps = $this->getStepsData();
    $activeStep = collect($steps)->firstWhere('active', true);
    $activeStepIndex = collect($steps)->search(fn($step) => $step['active']);
    $completedSteps = collect($steps)->where('completed', true);
    $totalSteps = count($steps);
    $progress = $totalSteps > 0 ? round(($completedSteps->count() / $totalSteps) * 100) : 0;

    // --- LOGIKA WARNA KONTEN KANAN (FIXED) ---
    // Prioritaskan warna dari data step (dari PHP)
    $currentColor = $activeStep['color'] ?? 'primary';

    // Jika warna sudah diset dari backend, gunakan itu
    // Ini memastikan warna 'danger' dari PHP tetap digunakan

    // Tema Konten Kanan
    $themes = [
        'primary' => [ 'badge_bg' => 'bg-primary-50', 'badge_text' => 'text-primary-600', 'ping_bg' => 'bg-primary-400', 'dot_bg' => 'bg-primary-500', 'alert_bg' => 'bg-primary-50', 'alert_border' => 'border-primary-200', 'alert_icon_bg' => 'bg-white', 'alert_icon_border' => 'border-primary-100', 'alert_icon_text' => 'text-primary-500', 'alert_title' => 'text-primary-700' ],
        'danger'  => [ 'badge_bg' => 'bg-red-50', 'badge_text' => 'text-red-600', 'ping_bg' => 'bg-red-400', 'dot_bg' => 'bg-red-500', 'alert_bg' => 'bg-red-50', 'alert_border' => 'border-red-200', 'alert_icon_bg' => 'bg-white', 'alert_icon_border' => 'border-red-100', 'alert_icon_text' => 'text-red-500', 'alert_title' => 'text-red-700' ],
        'success' => [ 'badge_bg' => 'bg-green-50', 'badge_text' => 'text-green-600', 'ping_bg' => 'bg-green-400', 'dot_bg' => 'bg-green-500', 'alert_bg' => 'bg-green-50', 'alert_border' => 'border-green-200', 'alert_icon_bg' => 'bg-white', 'alert_icon_border' => 'border-green-100', 'alert_icon_text' => 'text-green-500', 'alert_title' => 'text-green-700' ],
        'warning' => [ 'badge_bg' => 'bg-orange-50', 'badge_text' => 'text-orange-600', 'ping_bg' => 'bg-orange-400', 'dot_bg' => 'bg-orange-500', 'alert_bg' => 'bg-orange-50', 'alert_border' => 'border-orange-200', 'alert_icon_bg' => 'bg-white', 'alert_icon_border' => 'border-orange-100', 'alert_icon_text' => 'text-orange-500', 'alert_title' => 'text-orange-700' ],
        'info'    => [ 'badge_bg' => 'bg-sky-50', 'badge_text' => 'text-sky-600', 'ping_bg' => 'bg-sky-400', 'dot_bg' => 'bg-sky-500', 'alert_bg' => 'bg-sky-50', 'alert_border' => 'border-sky-200', 'alert_icon_bg' => 'bg-white', 'alert_icon_border' => 'border-sky-100', 'alert_icon_text' => 'text-sky-500', 'alert_title' => 'text-sky-700' ],
    ];

    $theme = $themes[$currentColor] ?? $themes['primary'];
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section class="p-0 overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10">
        <div class="flex flex-col md:flex-row min-h-[350px]">

            {{-- ================================================= --}}
            {{-- KOLOM KIRI (SIDEBAR) --}}
            {{-- ================================================= --}}
            <div class="w-full md:w-5/12 border-b md:border-b-0 md:border-r border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-gray-900/50 flex flex-col">

                {{-- Header Sidebar --}}
                <div class="p-4 border-b border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 bg-primary-50 dark:bg-primary-900/20 rounded-lg relative overflow-hidden">
                                <x-heroicon-m-clipboard-document-list class="w-4 h-4 text-primary-600 dark:text-primary-400 relative z-10" />
                            </div>
                            <h3 class="text-sm font-bold text-gray-950 dark:text-white">Progress Magang</h3>
                        </div>
                        <span class="text-xs font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-2 py-0.5 rounded-full">
                            {{ $progress }}%
                        </span>
                    </div>
                    <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full bg-primary-600 dark:bg-primary-500 transition-all duration-1000 ease-out" style="width: {{ $progress }}%"></div>
                    </div>
                </div>

                {{-- List Steps --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-1">
                    @foreach($steps as $index => $step)
                        @php
                            $isActive = $step['active'];
                            $isCompleted = $step['completed'];
                            // Gunakan warna dari data backend
                            $rawColor = $step['color'] ?? 'primary';

                            // --- DEFINISI STYLE SIDEBAR ---
                            $style = [
                                'primary' => ['container' => 'border-primary-200', 'spinner' => 'border-primary-400/50', 'bg' => 'bg-primary-100', 'number' => 'text-primary-700', 'title' => 'text-primary-700', 'status' => 'text-primary-600', 'dot' => 'bg-primary-500', 'chevron' => 'text-primary-400', 'label' => 'Aktif'],
                                'danger'  => ['container' => 'border-red-200',     'spinner' => 'border-red-400/50',     'bg' => 'bg-red-100',     'number' => 'text-red-700',     'title' => 'text-red-700',     'status' => 'text-red-600',     'dot' => 'bg-red-500',     'chevron' => 'text-red-400',     'label' => 'Ditolak'],
                                'success' => ['container' => 'border-green-200',   'spinner' => 'border-green-400/50',   'bg' => 'bg-green-100',   'number' => 'text-green-700',   'title' => 'text-green-700',   'status' => 'text-green-600',   'dot' => 'bg-green-500',   'chevron' => 'text-green-400',   'label' => 'Selesai'],
                                'warning' => ['container' => 'border-orange-200',  'spinner' => 'border-orange-400/50',  'bg' => 'bg-orange-100',  'number' => 'text-orange-700',  'title' => 'text-orange-700',  'status' => 'text-orange-600',  'dot' => 'bg-orange-500',  'chevron' => 'text-orange-400',  'label' => 'Proses'],
                                'info'    => ['container' => 'border-sky-200',     'spinner' => 'border-sky-400/50',     'bg' => 'bg-sky-100',     'number' => 'text-sky-700',     'title' => 'text-sky-700',     'status' => 'text-sky-600',     'dot' => 'bg-sky-500',     'chevron' => 'text-sky-400',     'label' => 'Tinjau'],
                                'gray'    => ['container' => 'border-gray-200',    'spinner' => 'border-gray-400/50',    'bg' => 'bg-gray-100',    'number' => 'text-gray-700',    'title' => 'text-gray-700',    'status' => 'text-gray-600',    'dot' => 'bg-gray-500',    'chevron' => 'text-gray-400',    'label' => 'Menunggu'],
                            ];

                            $s = $style[$rawColor] ?? $style['primary'];
                        @endphp

                        <div class="group flex items-center gap-3 p-3 rounded-lg transition-all duration-300 border relative overflow-hidden
                            {{ $isActive
                                ? 'bg-white dark:bg-white/5 shadow-sm translate-x-1 ' . $s['container'] . ' dark:border-white/10'
                                : 'bg-transparent border-transparent hover:bg-gray-100 dark:hover:bg-white/5 text-gray-500'
                            }}">

                            {{-- LINGKARAN NOMOR --}}
                            <div class="flex-shrink-0 relative flex items-center justify-center w-8 h-8">
                                @if($isCompleted)
                                    <div class="w-6 h-6 rounded-full bg-success-100 text-success-600 dark:bg-success-500/20 dark:text-success-400 flex items-center justify-center">
                                        <x-heroicon-m-check class="w-3.5 h-3.5" />
                                    </div>
                                @elseif($isActive)
                                    {{-- SPINNER (Dashed Border) --}}
                                    <div class="absolute inset-0 w-8 h-8 border-2 border-dashed {{ $s['spinner'] }} rounded-full animate-[spin_6s_linear_infinite]"></div>

                                    {{-- NOMOR dengan warna sesuai status --}}
                                    <div class="relative w-6 h-6 rounded-full {{ $s['bg'] }} flex items-center justify-center shadow-sm z-10">
                                        <span class="text-gray-900 dark:text-gray-100 font-bold text-xs">{{ $index + 1 }}</span>
                                    </div>
                                @else
                                    <div class="w-6 h-6 rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-500 flex items-center justify-center font-medium text-xs group-hover:border-gray-400 transition-colors">
                                        {{ $index + 1 }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1 z-10">
                                <h4 class="text-xs font-semibold {{ $isActive ? $s['title'] . ' dark:text-gray-200' : 'text-gray-700 dark:text-gray-400' }}">
                                    {{ $step['title'] }}
                                </h4>

                                @if($isActive)
                                    <p class="text-[10px] font-medium flex items-center gap-1 mt-0.5 {{ $s['status'] }} dark:text-gray-400">
                                        <span class="w-1.5 h-1.5 rounded-full animate-pulse {{ $s['dot'] }}"></span>
                                        {{ $step['status'] ?? $s['label'] }}
                                    </p>
                                @endif
                            </div>

                            @if($isActive)
                                <x-heroicon-m-chevron-right class="w-3.5 h-3.5 {{ $s['chevron'] }}" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ================================================= --}}
            {{-- KOLOM KANAN (DETAIL CONTENT) --}}
            {{-- ================================================= --}}
            <div class="w-full md:w-7/12 bg-white dark:bg-gray-900 flex flex-col justify-center relative p-6 md:p-8 overflow-hidden">
                {{-- Background Decoration --}}
                <div class="absolute -top-8 -right-8 p-4 opacity-[0.03] dark:opacity-[0.05] pointer-events-none">
                    <div class="animate-[spin_60s_linear_infinite]">
                        <x-heroicon-o-cog-6-tooth class="w-64 h-64 text-gray-900 dark:text-white" />
                    </div>
                </div>

                @if($activeStep)
                    <div class="max-w-md mx-auto w-full relative z-10">
                        {{-- Badge Status --}}
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full {{ $theme['badge_bg'] }} {{ $theme['badge_text'] }} text-[10px] font-bold mb-3 tracking-wide uppercase shadow-sm">
                            <span class="relative flex h-1.5 w-1.5">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $theme['ping_bg'] }} opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-1.5 w-1.5 {{ $theme['dot_bg'] }}"></span>
                            </span>
                            {{ $activeStep['status'] ?? 'Step ' . ($activeStepIndex + 1) }}
                        </div>

                        <h2 class="text-xl md:text-2xl font-bold text-gray-950 dark:text-white mb-2 leading-tight">
                            {{ $activeStep['title'] }}
                        </h2>

                        <div class="prose prose-sm dark:prose-invert text-gray-600 dark:text-gray-400 mb-5 leading-relaxed">
                            <p>{{ $activeStep['description'] }}</p>
                        </div>

                        @if($activeStep['keterangan'])
                            <div class="relative rounded-lg {{ $theme['alert_bg'] }} border {{ $theme['alert_border'] }} p-3 mb-5 shadow-sm overflow-hidden">
                                <div class="absolute top-0 right-0 w-12 h-12 rounded-full blur-xl -mr-2 -mt-2 pointer-events-none opacity-10 {{ str_replace('bg-', 'bg-', $theme['dot_bg']) }}"></div>
                                <div class="flex items-start gap-3 relative z-10">
                                    <div class="shrink-0 w-8 h-8 rounded-md {{ $theme['alert_icon_bg'] }} border {{ $theme['alert_icon_border'] }} flex items-center justify-center shadow-sm mt-0.5">
                                        @if($currentColor === 'danger')
                                            <x-heroicon-m-x-circle class="w-4 h-4 {{ $theme['alert_icon_text'] }}" />
                                        @elseif($currentColor === 'success')
                                            <x-heroicon-m-check-circle class="w-4 h-4 {{ $theme['alert_icon_text'] }}" />
                                        @elseif($currentColor === 'info')
                                            <x-heroicon-m-information-circle class="w-4 h-4 {{ $theme['alert_icon_text'] }}" />
                                        @else
                                            <x-heroicon-m-bell-alert class="w-4 h-4 {{ $theme['alert_icon_text'] }} animate-[wiggle_1s_ease-in-out_infinite]" />
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="text-[10px] font-bold {{ $theme['alert_title'] }} uppercase tracking-wide mb-0.5">
                                            @if($currentColor === 'danger') Perbaiki Segera
                                            @elseif($currentColor === 'success') Berhasil
                                            @else Perhatian @endif
                                        </h5>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-snug">
                                            {{ $activeStep['keterangan'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="flex flex-col gap-2">
                            <x-filament::button
                                tag="a"
                                href="{{ $activeStep['url'] }}"
                                size="lg"
                                color="{{ $currentColor }}"
                                class="w-full shadow-lg shadow-{{ $currentColor }}-500/10 hover:scale-[1.01] transition-transform duration-300 group"
                            >
                                <span class="flex items-center justify-center gap-2 text-sm">
                                    {{ $activeStep['button_text'] }}
                                    <x-heroicon-m-arrow-right class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                </span>
                            </x-filament::button>
                            <p class="text-center text-[10px] text-gray-400 mt-1">
                                Proses tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="text-center max-w-xs mx-auto relative z-10">
                        <div class="inline-flex p-4 bg-success-50 dark:bg-success-900/20 rounded-full mb-4 ring-1 ring-success-100 dark:ring-success-500/30 animate-[bounce_3s_infinite]">
                            <x-heroicon-s-check-badge class="w-10 h-10 text-success-600 dark:text-success-400" />
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Sempurna!</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Seluruh kegiatan magang selesai.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-3 border-t border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 sticky bottom-0 z-20">
            <div class="flex items-center justify-between gap-2 bg-gray-50 dark:bg-gray-800 rounded-lg p-2 border border-gray-100 dark:border-gray-700 hover:border-gray-300 transition-colors group">
                <div class="text-[10px] text-gray-500 dark:text-gray-400 pl-1 font-medium flex items-center gap-1.5">
                    <x-heroicon-o-printer class="w-3.5 h-3.5 group-hover:text-primary-500 transition-colors" />
                    Cetak Laporan
                </div>
                <div>
                    <livewire:mahasiswa-print :steps="$steps" />
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 10px; }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #374151; }
</style>
