<div>
    <style>
    .mood-overlay-card {
        --mood-card-bg: #fff;
        --mood-card-text: #111827;
        --mood-btn-text: #4b5563;
        --mood-btn-hover-bg: #f3f4f6;
        --mood-label: #6b7280;
        --mood-spinner-track: rgba(245,158,11,.2);
    }
    .dark .mood-overlay-card,
    .mood-overlay-card .dark {
        --mood-card-bg: #1f2937;
        --mood-card-text: #f9fafb;
        --mood-btn-text: #d1d5db;
        --mood-btn-hover-bg: #374151;
        --mood-label: #9ca3af;
        --mood-spinner-track: rgba(245,158,11,.15);
    }
    .mood-btn:hover {
        transform: scale(1.05);
        background: var(--mood-btn-hover-bg);
    }
    </style>

    @if ($showOverlay)
        <div
            wire:key="mood-overlay"
        >
            <div style="position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.6)">
                <div class="mood-overlay-card"
                    style="margin:0 1rem;width:100%;max-width:44rem;border-radius:1rem;background:var(--mood-card-bg);padding:2rem 2.5rem;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25)">
                    @if ($processing)
                        <div style="display:flex;flex-direction:column;align-items:center;padding:2rem 0">
                            <div style="margin-bottom:1rem;width:3rem;height:3rem;border-radius:50%;border:4px solid var(--mood-spinner-track);border-top-color:#f59e0b;animation:spin 1s linear infinite"></div>
                            <p style="font-size:1.125rem;font-weight:500;color:var(--mood-card-text)">Guardando tu estado de animo...</p>
                        </div>
                    @else
                        <div style="text-align:center">
                            <h2 style="margin-bottom:2rem;font-size:1.5rem;font-weight:700;color:var(--mood-card-text)">Como te sientes hoy?</h2>

                            <div style="display:flex;justify-content:center;gap:0.25rem">
                                @foreach ([
                                    'sad' => ['emoji' => '😢', 'label' => 'Muy triste'],
                                    'med_sad' => ['emoji' => '🙁', 'label' => 'Medianamente triste'],
                                    'neutral' => ['emoji' => '😐', 'label' => 'Neutral'],
                                    'med_happy' => ['emoji' => '🙂', 'label' => 'Medianamente feliz'],
                                    'happy' => ['emoji' => '😄', 'label' => 'Muy feliz'],
                                ] as $mood => $config)
                                    <button
                                        wire:click="selectMood('{{ $mood }}')"
                                        type="button"
                                        class="mood-btn"
                                        style="display:flex;flex-direction:column;align-items:center;gap:0.5rem;border-radius:0.75rem;padding:0.75rem 1rem;min-width:80px;background:transparent;border:none;cursor:pointer;transition:transform .15s"
                                    >
                                        <span style="font-size:2.25rem">{{ $config['emoji'] }}</span>
                                        <span style="font-size:0.875rem;font-weight:500;color:var(--mood-btn-text);white-space:nowrap">{{ $config['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
