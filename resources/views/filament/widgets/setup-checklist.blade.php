@php
    $progressColor = match (true) {
        $progressPercent >= 100 => 'success',
        $progressPercent >= 50 => 'warning',
        default => 'danger',
    };

    $badgeClasses = match ($progressColor) {
        'success' => 'bg-success-50 text-success-700 dark:bg-success-500/20 dark:text-success-400',
        'warning' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400',
        default => 'bg-danger-50 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400',
    };

    $barClasses = match ($progressColor) {
        'success' => 'bg-success-500 dark:bg-success-400',
        'warning' => 'bg-warning-500 dark:bg-warning-400',
        default => 'bg-danger-500 dark:bg-danger-400',
    };
@endphp

<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 shadow-sm ring-1 ring-gray-200 dark:from-gray-900 dark:to-gray-950 dark:ring-gray-700">
        <div class="absolute top-0 right-0 h-24 w-24 -translate-y-6 translate-x-6 rounded-full bg-primary-500/10 dark:bg-primary-400/5"></div>
        <div class="absolute bottom-0 left-0 h-16 w-16 -translate-x-4 translate-y-4 rounded-full bg-amber-500/10 dark:bg-amber-400/5"></div>

        <div class="relative p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">
                        Configuración Inicial
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Completa estos pasos para activar todas las funciones.
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClasses }}">
                    {{ $mandatoryDone }}/{{ $mandatoryTotal }}
                </span>
            </div>

            <div class="mb-5 w-full h-2 rounded-full bg-gray-200 dark:bg-gray-600 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700 ease-in-out {{ $barClasses }}"
                    style="width: {{ $progressPercent }}%">
                </div>
            </div>

            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">
                    Obligatorio
                </p>

                @foreach ($steps as $step)
                    @if ($step['mandatory'])
                        <div class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition-colors hover:bg-white/60 dark:hover:bg-gray-800/50">
                            @if ($step['done'])
                                <x-filament::icon
                                    icon="heroicon-s-check-circle"
                                    class="h-5 w-5 flex-shrink-0 text-success-500" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-success-700 dark:text-success-400 line-through decoration-success-300">
                                        {{ $step['title'] }}
                                    </p>
                                </div>
                                <x-filament::badge color="success" size="sm">
                                    Hecho
                                </x-filament::badge>
                            @else
                                <x-filament::icon
                                    icon="heroicon-o-x-circle"
                                    class="h-5 w-5 flex-shrink-0 text-danger-400" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                        {{ $step['title'] }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                                        {{ $step['description'] }}
                                    </p>
                                </div>
                                <x-filament::badge color="danger" size="sm">
                                    Pendiente
                                </x-filament::badge>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>

            @if ($optionalSteps->isNotEmpty())
                <div class="mt-4 space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">
                        Opcional
                    </p>

                    @foreach ($optionalSteps as $step)
                        <div class="flex items-center gap-3 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 px-3 py-2.5 transition-colors hover:bg-white/60 dark:hover:bg-gray-800/50">
                            @if ($step['done'])
                                <x-filament::icon
                                    icon="heroicon-s-check-circle"
                                    class="h-5 w-5 flex-shrink-0 text-success-500" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-success-700 dark:text-success-400 line-through decoration-success-300">
                                        {{ $step['title'] }}
                                    </p>
                                </div>
                                <x-filament::badge color="success" size="sm">
                                    Hecho
                                </x-filament::badge>
                            @else
                                <x-filament::icon
                                    icon="heroicon-o-minus-circle"
                                    class="h-5 w-5 flex-shrink-0 text-gray-400" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $step['title'] }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                                        {{ $step['description'] }}
                                    </p>
                                </div>
                                <x-filament::badge color="gray" size="sm">
                                    Pendiente
                                </x-filament::badge>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-5 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ \App\Filament\Pages\ManageSettings::getUrl() }}"
                    class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors">
                    <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-4 w-4" />
                    Ir a Configuración
                    <x-filament::icon icon="heroicon-o-arrow-right" class="h-3 w-3" />
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
