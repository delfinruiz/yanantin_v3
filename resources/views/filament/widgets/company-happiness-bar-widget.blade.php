<x-filament-widgets::widget>
    <div wire:poll.5s="refreshData">
        <x-filament::section>
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                <div class="flex items-center justify-between pb-4">
                    <h2 class="text-sm font-semibold">Estado de felicidad de la organización (hoy)</h2>
                </div>
                <div class="pt-4">
                    <div class="flex items-center justify-end gap-2 mb-3">
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-700/50 text-slate-200">Nivel {{ $level }}/5</span>
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-700/50 text-slate-300">Respuestas {{ $responses }}</span>
                    </div>
                    <div class="relative w-full rounded-xl h-12 overflow-hidden border border-slate-600/40 shadow-[inset_0_2px_6px_rgba(0,0,0,0.35),0_6px_14px_rgba(0,0,0,0.12)]">
                        <div class="absolute inset-y-0 left-0 w-1/5 flex flex-col items-center justify-center text-[11px] text-white gap-0.5 rounded-l-xl" style="background:#ef4444">
                            <span style="font-size:1.05rem;line-height:1">😭</span>
                            <span>{{ number_format($distribution['sad'] ?? 0, 0) }}%</span>
                        </div>
                        <div class="absolute inset-y-0 left-[20%] w-1/5 flex flex-col items-center justify-center text-[11px] text-white gap-0.5" style="background:#f59e0b">
                            <span style="font-size:1.05rem;line-height:1">🙁</span>
                            <span>{{ number_format($distribution['med_sad'] ?? 0, 0) }}%</span>
                        </div>
                        <div class="absolute inset-y-0 left-[40%] w-1/5 flex flex-col items-center justify-center text-[11px] text-black gap-0.5" style="background:#facc15">
                            <span style="font-size:1.05rem;line-height:1">😐</span>
                            <span>{{ number_format($distribution['neutral'] ?? 0, 0) }}%</span>
                        </div>
                        <div class="absolute inset-y-0 left-[60%] w-1/5 flex flex-col items-center justify-center text-[11px] text-black gap-0.5" style="background:#84cc16">
                            <span style="font-size:1.05rem;line-height:1">🙂</span>
                            <span>{{ number_format($distribution['med_happy'] ?? 0, 0) }}%</span>
                        </div>
                        <div class="absolute inset-y-0 left-[80%] w-1/5 flex flex-col items-center justify-center text-[11px] text-white gap-0.5 rounded-r-xl" style="background:#22c55e">
                            <span style="font-size:1.05rem;line-height:1">😄</span>
                            <span>{{ number_format($distribution['happy'] ?? 0, 0) }}%</span>
                        </div>
                        <div class="pointer-events-none absolute inset-0 rounded-xl" style="box-shadow: inset 0 1px 0 rgba(255,255,255,0.25)"></div>
                        <div class="absolute -top-2 h-3 w-3 rounded-full bg-white shadow transform -translate-x-1/2"
                             x-data="{ left: {{ $triangleLeft }} }"
                             x-bind:style="`left: ${left}%`">
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>

