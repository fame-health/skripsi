@php
    $steps = $this->getStepsData();
    $activeStep = collect($steps)->firstWhere('active', true);
    $activeStepIndex = collect($steps)->search(fn($step) => $step['active']);
    $completedSteps = collect($steps)->where('completed', true);
    $totalSteps = count($steps);
    $progress = $totalSteps > 0 ? round(($completedSteps->count() / $totalSteps) * 100) : 0;
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section class="p-0 overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10">

        <div class="flex flex-col md:flex-row min-h-[350px]">

            {{-- Sidebar --}}
            <div class="w-full md:w-5/12 border-b md:border-b-0 md:border-r border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-gray-900/50 flex flex-col">
                <div class="p-4 border-b border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 bg-primary-50 dark:bg-primary-900/20 rounded-lg relative overflow-hidden group">
                                <x-heroicon-m-clipboard-document-list class="w-4 h-4 text-primary-600 dark:text-primary-400 relative z-10" />
                                <div class="absolute inset-0 bg-primary-200/50 dark:bg-primary-500/30 opacity-0 group-hover:opacity-100 transition-opacity animate-pulse"></div>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-950 dark:text-white">Progress Magang</h3>
                            </div>
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
                            $isDitolak = isset($step['status']) && $step['status'] === 'Ditolak';

                            $statusClasses = match($step['status_color']) {
                                'success' => 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400 border border-green-200 dark:border-green-500/30',
                                'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-500/30',
                                'danger' => 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400 border border-red-300 dark:border-red-500/40 font-extrabold',
                                default => 'bg-gray-100 text-gray-500 dark:bg-gray-700/20 dark:text-gray-300',
                            };

                            // Border color untuk step yang ditolak
                            $borderClass = $isDitolak
                                ? 'border-red-200 dark:border-red-500/30'
                                : ($isActive ? 'border-primary-200 dark:border-primary-500/30' : 'border-transparent');
                        @endphp

                        <div class="group flex items-center gap-3 p-3 rounded-lg transition-all duration-300 border relative overflow-hidden
                            {{ $isActive
                                ? 'bg-white dark:bg-white/5 shadow-sm translate-x-1 ' . $borderClass
                                : ($isDitolak
                                    ? 'bg-red-50 dark:bg-red-900/10 ' . $borderClass . ' shadow-sm'
                                    : 'bg-transparent border-transparent hover:bg-gray-100 dark:hover:bg-white/5 text-gray-500')
                            }}">

                            <div class="flex-shrink-0 relative flex items-center justify-center w-8 h-8">
                                @if($isCompleted)
                                    <div class="w-6 h-6 rounded-full bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400 flex items-center justify-center">
                                        <x-heroicon-m-check class="w-3.5 h-3.5" />
                                    </div>
                                @elseif($isDitolak)
                                    <div class="w-6 h-6 rounded-full bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400 flex items-center justify-center">
                                        <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                                    </div>
                                @elseif($isActive)
                                    <div class="absolute inset-0 w-8 h-8 border-2 border-dashed border-primary-400/50 rounded-full animate-[spin_6s_linear_infinite]"></div>
                                    <div class="relative w-6 h-6 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold text-xs shadow-md z-10">
                                        {{ $index + 1 }}
                                    </div>
                                @else
                                    <div class="w-6 h-6 rounded-full border border-gray-200 dark:border-gray-700 text-gray-400 flex items-center justify-center font-medium text-xs group-hover:border-gray-400 transition-colors">
                                        {{ $index + 1 }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1 z-10">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-semibold {{ $isActive ? 'text-primary-700 dark:text-primary-400' : ($isDitolak ? 'text-red-700 dark:text-red-400' : 'text-gray-700 dark:text-gray-300') }}">
                                        {{ $step['title'] }}
                                    </h4>

                                    @if($step['status'])
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $statusClasses }}">
                                            {{ $step['status'] }}
                                        </span>
                                    @endif
                                </div>

                                @if($isActive)
                                    <p class="text-[10px] text-primary-500 font-medium flex items-center gap-1 mt-0.5">
                                        <span class="w-1 h-1 rounded-full bg-primary-500 animate-pulse"></span>
                                        Aktif
                                    </p>
                                @elseif($isDitolak)
                                    <p class="text-[10px] text-red-500 dark:text-red-400 font-medium flex items-center gap-1 mt-0.5">
                                        <span class="w-1 h-1 rounded-full bg-red-500"></span>
                                        Perlu Diperbaiki
                                    </p>
                                @endif
                            </div>

                            @if($isActive)
                                <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-primary-400" />
                            @elseif($isDitolak)
                                <x-heroicon-m-exclamation-triangle class="w-3.5 h-3.5 text-red-400" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Detail Content --}}
            <div class="w-full md:w-7/12 bg-white dark:bg-gray-900 flex flex-col justify-center relative p-6 md:p-8 overflow-hidden">
                <div class="absolute -top-8 -right-8 p-4 opacity-[0.03] dark:opacity-[0.05] pointer-events-none">
                    <div class="animate-[spin_60s_linear_infinite]">
                        <x-heroicon-o-cog-6-tooth class="w-64 h-64 text-gray-900 dark:text-white" />
                    </div>
                </div>

                @if($activeStep)
                    <div class="max-w-md mx-auto w-full relative z-10">

                        {{-- Status Badge --}}
                        @php
                            $isDitolakActive = isset($activeStep['status']) && $activeStep['status'] === 'Ditolak';
                            $badgeColor = $isDitolakActive
                                ? 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/40'
                                : 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400';
                            $dotColor = $isDitolakActive ? 'bg-red-400' : 'bg-blue-400';
                            $dotBgColor = $isDitolakActive ? 'bg-red-500' : 'bg-blue-500';
                        @endphp

                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full {{ $badgeColor }} text-[10px] font-bold mb-3 tracking-wide uppercase shadow-sm">
                            <span class="relative flex h-1.5 w-1.5">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $dotColor }} opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-1.5 w-1.5 {{ $dotBgColor }}"></span>
                            </span>
                            Step {{ $activeStepIndex + 1 }}
                        </div>

                        <h2 class="text-xl md:text-2xl font-bold text-gray-950 dark:text-white mb-2 leading-tight">
                            {{ $activeStep['title'] }}
                        </h2>

                        <div class="prose prose-sm dark:prose-invert text-gray-600 dark:text-gray-400 mb-5 leading-relaxed">
                            <p>{{ $activeStep['description'] }}</p>
                        </div>

                        @if($activeStep['keterangan'])
                            @php
                                $alertColor = $isDitolakActive
                                    ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-500/20'
                                    : 'bg-orange-50 dark:bg-orange-900/10 border-orange-200 dark:border-orange-500/20';
                                $alertBgBlur = $isDitolakActive ? 'bg-red-500/5' : 'bg-orange-500/5';
                                $alertIconBg = $isDitolakActive
                                    ? 'bg-white dark:bg-red-900/30 border-red-100 dark:border-red-500/30'
                                    : 'bg-white dark:bg-orange-900/30 border-orange-100 dark:border-orange-500/30';
                                $alertIconColor = $isDitolakActive ? 'text-red-500 dark:text-red-400' : 'text-orange-500 dark:text-orange-400';
                                $alertTitleColor = $isDitolakActive ? 'text-red-700 dark:text-red-400' : 'text-orange-700 dark:text-orange-400';
                            @endphp

                            <div class="relative rounded-lg {{ $alertColor }} border p-3 mb-5 shadow-sm overflow-hidden">
                                <div class="absolute top-0 right-0 w-12 h-12 {{ $alertBgBlur }} rounded-full blur-xl -mr-2 -mt-2 pointer-events-none"></div>
                                <div class="flex items-start gap-3 relative z-10">
                                    <div class="shrink-0 w-8 h-8 rounded-md {{ $alertIconBg }} border flex items-center justify-center shadow-sm mt-0.5">
                                        @if($isDitolakActive)
                                            <x-heroicon-m-exclamation-circle class="w-4 h-4 {{ $alertIconColor }}" />
                                        @else
                                            <x-heroicon-m-bell-alert class="w-4 h-4 {{ $alertIconColor }} animate-[wiggle_1s_ease-in-out_infinite]" />
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="text-[10px] font-bold {{ $alertTitleColor }} uppercase tracking-wide mb-0.5">
                                            {{ $isDitolakActive ? 'Alasan Penolakan' : 'Perhatian' }}
                                        </h5>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-snug">
                                            {{ $activeStep['keterangan'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="flex flex-col gap-2">
                            {{-- Tombol dengan warna sesuai status --}}
                            @php
                                $buttonColor = (isset($activeStep['status']) && $activeStep['status'] === 'Ditolak') ? 'danger' : 'primary';
                            @endphp

                            <x-filament::button
                                tag="a"
                                href="{{ $activeStep['url'] }}"
                                size="lg"
                                color="{{ $buttonColor }}"
                                class="w-full shadow-lg hover:scale-[1.01] transition-transform duration-300 group"
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
                        <div class="inline-flex p-4 bg-green-50 dark:bg-green-900/20 rounded-full mb-4 ring-1 ring-green-100 dark:ring-green-500/30 animate-[bounce_3s_infinite]">
                            <x-heroicon-s-check-badge class="w-10 h-10 text-green-600 dark:text-green-400" />
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Sempurna!</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Seluruh kegiatan magang selesai.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 10px; }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #374151; }
    @keyframes wiggle { 0%, 100% { transform: rotate(-3deg); } 50% { transform: rotate(3deg); } }
</style>
