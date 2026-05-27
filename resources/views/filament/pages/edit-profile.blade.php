<x-filament-panels::page>
    {{ $this->form }}

    <div class="mt-6" style="display:flex;justify-content:flex-end">
        <x-filament::button wire:click="save" color="primary">
            Guardar
        </x-filament::button>
    </div>

    @if(count($this->sessions) > 0)
        <x-filament::section aside class="mt-8">
            <x-slot name="heading">Sesiones del navegador</x-slot>
            <x-slot name="description">Gestiona y cierra tus sesiones activas en otros navegadores y dispositivos.</x-slot>

            <style>
                .tp-session-card { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-bottom: 0.5rem }
                .tp-session-card:last-child { margin-bottom: 0 }
                .tp-session-icon { display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: #f3f4f6; flex-shrink: 0 }
                .tp-session-device { font-size: 0.875rem; font-weight: 600; color: #111827 }
                .tp-session-badge { font-size: 0.75rem; color: #059669; background: #ecfdf5; padding: 0.125rem 0.5rem; border-radius: 9999px; font-weight: 500 }
                .tp-session-meta { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem }
                .tp-warning-card { margin-top: 1.5rem; border: 1px solid #fde68a; background: #fffbeb; border-radius: 0.75rem; padding: 1.25rem }
                .tp-warning-title { font-size: 0.875rem; font-weight: 500; color: #92400e; margin: 0 0 0.25rem }
                .tp-warning-text { font-size: 0.75rem; color: #a16207; margin: 0 0 1rem }
                .tp-session-input { flex: 1; max-width: 16rem; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; background: #fff; color: #111827 }
                .tp-session-input::placeholder { color: #9ca3af }

                .dark .tp-session-card { border-color: #374151 }
                .dark .tp-session-icon { background: #1f2937 }
                .dark .tp-session-device { color: #f3f4f6 }
                .dark .tp-session-badge { color: #34d399; background: #064e3b }
                .dark .tp-session-meta { color: #9ca3af }
                .dark .tp-warning-card { border-color: #78350f; background: #451a03 }
                .dark .tp-warning-title { color: #fde68a }
                .dark .tp-warning-text { color: #fbbf24 }
                .dark .tp-session-input { border-color: #4b5563; background: #1f2937; color: #f3f4f6 }
                .dark .tp-session-input::placeholder { color: #6b7280 }
            </style>

            <div style="max-width:42rem">
                @foreach($this->sessions as $session)
                    <div class="tp-session-card">
                        <div class="tp-session-icon">
                            <x-filament::icon
                                icon="{{ $session['device'] === 'Android' || $session['device'] === 'iOS' ? 'heroicon-o-device-phone-mobile' : 'heroicon-o-computer-desktop' }}"
                                style="width:1.25rem;height:1.25rem;color:#4b5563"
                            />
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="display:flex;align-items:center;gap:0.5rem">
                                <span class="tp-session-device">
                                    {{ $session['device'] }} — {{ $session['browser'] }}
                                </span>
                                @if($session['is_current'])
                                    <span class="tp-session-badge">Activo</span>
                                @endif
                            </div>
                            <div class="tp-session-meta">
                                {{ $session['ip'] }} &middot; Última actividad {{ $session['last_active'] }}
                            </div>
                        </div>
                    </div>
                @endforeach

                @if(count($this->sessions) > 1)
                    <div class="tp-warning-card">
                        <div style="display:flex;align-items:flex-start;gap:0.75rem">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" style="width:1.25rem;height:1.25rem;color:#f59e0b;flex-shrink:0;margin-top:0.125rem"/>
                            <div style="flex:1">
                                <p class="tp-warning-title">Cerrar otras sesiones del navegador</p>
                                <p class="tp-warning-text">Ingresa tu contraseña para cerrar todas las sesiones excepto la actual.</p>
                                <div style="display:flex;align-items:flex-end;gap:0.75rem">
                                    <input
                                        type="password"
                                        wire:model="sessionPassword"
                                        placeholder="Contraseña actual"
                                        class="tp-session-input"
                                    >
                                    <x-filament::button wire:click="logoutOtherBrowserSessions" color="danger">
                                        Cerrar otras sesiones
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
