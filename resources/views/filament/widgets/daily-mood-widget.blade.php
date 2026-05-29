@php
    $moods = [
        ['code' => 'sad', 'label' => 'Triste', 'class' => 'bg-[#ef4444]', 'emoji' => '😢'],
        ['code' => 'med_sad', 'label' => 'Med Triste', 'class' => 'bg-[#f59e0b]', 'emoji' => '🙁'],
        ['code' => 'neutral', 'label' => 'Neutral', 'class' => 'bg-[#facc15]', 'emoji' => '😐'],
        ['code' => 'med_happy', 'label' => 'Med Feliz', 'class' => 'bg-[#84cc16]', 'emoji' => '🙂'],
        ['code' => 'happy', 'label' => 'Feliz', 'class' => 'bg-[#22c55e]', 'emoji' => '😄'],
    ];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="divide-y divide-gray-200 dark:divide-gray-800">
            <div class="flex items-center justify-between pb-4">
                <h2 class="text-sm font-semibold">Mensaje del día</h2>
            </div>

            <div class="pt-4">
                @if($isSuperAdmin)
                    <div class="text-sm text-gray-700 dark:text-gray-200 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4 leading-relaxed">
                        Como administrador, monitoreas el bienestar de tu equipo. Revisa los indicadores de felicidad organizacional para tomar decisiones informadas.
                    </div>
                @elseif($today?->message)
                    <div class="rounded-lg border border-success-300 bg-success-50 p-4 text-success-900">
                        "{{ $today->message }}"
                    </div>
                @else
                    <div class="text-xs text-gray-700 bg-gray-100 dark:bg-gray-800 dark:text-gray-200 rounded px-3 py-2">
                        Sin mensaje para hoy.
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
