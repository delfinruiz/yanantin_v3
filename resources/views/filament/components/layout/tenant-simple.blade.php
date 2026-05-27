@php
    use Illuminate\Support\Facades\File;

    $tenant = tenant();
    $logoLight = $tenant?->logoLightUrl();
    $logoDark = $tenant?->logoDarkUrl();
    $hasLogo = $logoLight || $logoDark;
    $name = $tenant?->name ?? filament()->getBrandName();
    $slug = $tenant?->slug();
    $backgroundUrl = $tenant?->loginBackgroundUrl();
    $isRegister = $livewire instanceof \Filament\Auth\Pages\Register;
    $isPasswordReset = $livewire instanceof \Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
    $isEmailVerification = $livewire instanceof \Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt;

    $tenantCssPath = public_path("tenants/{$slug}/css/styles.css");
    $hasTenantCss = $slug && File::exists($tenantCssPath);
    $tenantCssUrl = $hasTenantCss ? asset("tenants/{$slug}/css/styles.css") : null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <script>
        (function() {
            var t = localStorage.getItem('theme');
            if (t === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = '#070919';
                document.body.style.backgroundColor = '#070919';
            } else if (t === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.style.backgroundColor = '#070919';
                    document.body.style.backgroundColor = '#070919';
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        })();
    </script>
    @if($hasTenantCss)
        <link rel="stylesheet" href="{{ $tenantCssUrl }}">
    @endif

    <style>
        .tlp-bg { background: #f8fafc; background-image: radial-gradient(circle at 25% 25%,#fff 0%,#f8fafc 50%) }
        .tlp-title { color: #0f172a }
        .tlp-subtitle { color: #64748b }
        .tlp-card { background: #fff; border-color: #e2e8f0 }
        .tlp-link { color: #64748b }
        .tlp-link:hover { color: #f59e0b }
        .tlp-footer { color: #94a3b8 }
        .tlp-logo-fallback { background: #f59e0b; box-shadow: 0 2px 8px rgba(245,158,11,.3) }

        .dark .tlp-bg { background: #070919; background-image: none }
        .dark .tlp-title { color: #f1f5f9 }
        .dark .tlp-subtitle { color: #94a3b8 }
        .dark .tlp-card { background: #1e293b; border-color: #334155 }
        .dark .tlp-link { color: #94a3b8 }
        .dark .tlp-link:hover { color: #fbbf24 }
        .dark .tlp-footer { color: #64748b }
        .dark .tlp-logo-fallback { background: #f59e0b; box-shadow: 0 2px 8px rgba(245,158,11,.4) }
        .dark .tlp-logo-fallback span { color: #fff }
        .tlp-toggle-btn { background: #e5e7eb; border: 1px solid #d1d5db }
        .tlp-toggle-sun { color: #d97706 }
        .tlp-toggle-moon { color: #9ca3af }
        .dark .tlp-toggle-btn { background: #374151; border-color: #4b5563 }
        .dark .tlp-toggle-sun { color: #fbbf24 }
        .dark .tlp-toggle-moon { color: #e2e8f0 }

        @if($backgroundUrl)
            .tlp-bg { background: transparent !important; background-image: none !important }
            .tlp-title { color: #fff !important }
            .tlp-subtitle { color: rgba(255,255,255,.7) !important }
            .tlp-footer { color: rgba(255,255,255,.4) !important }
            .tlp-logo-fallback { background: rgba(255,255,255,.15); box-shadow: none }
            .tlp-logo-fallback span { color: #fff }
        @endif
    </style>

    <div class="tlp-bg" style="display:flex;min-height:100dvh;align-items:center;justify-content:center;padding:1.5rem;font-family:system-ui,-apple-system,sans-serif">
        {{-- Theme toggle --}}
        <button
            id="theme-toggle-btn"
            class="tlp-toggle-btn"
            onclick="var d=document.documentElement,b=document.body,p=this.querySelector('.tlp-toggle-dot'),isDark=d.classList.contains('dark');if(isDark){localStorage.setItem('theme','light');d.classList.remove('dark');b.style.backgroundColor='';p.style.left='5px'}else{localStorage.setItem('theme','dark');d.classList.add('dark');b.style.backgroundColor='#070919';p.style.left='35px'}"
            style="position:fixed;top:1rem;right:1rem;z-index:60;display:flex;align-items:center;padding:0;border-radius:9999px;width:64px;height:32px;cursor:pointer"
            aria-label="Cambiar tema"
        >
            <svg class="tlp-toggle-sun" style="width:16px;height:16px;position:absolute;left:8px;top:8px" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" fill="currentColor"/><g stroke="currentColor" stroke-width="2.5" stroke-linecap="round" fill="none"><path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"/></g></svg>
            <svg class="tlp-toggle-moon" style="width:16px;height:16px;position:absolute;right:8px;top:8px" fill="currentColor" viewBox="0 0 24 24"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <span class="tlp-toggle-dot" style="display:block;width:24px;height:24px;border-radius:50%;background:#fff;position:absolute;top:4px;left:5px;transition:left 0.2s;box-shadow:0 1px 3px rgba(0,0,0,.2)"></span>
        </button>
        <script>
            (function(){
                var b = document.getElementById('theme-toggle-btn');
                if(!b) return;
                var p = b.querySelector('.tlp-toggle-dot');
                if(!p) return;
                var t = localStorage.getItem('theme');
                var isDark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
                p.style.left = isDark ? '35px' : '5px';
            })();
        </script>
        @if($backgroundUrl)
            <div style="position:fixed;inset:0;background:url('{{ $backgroundUrl }}') center/cover no-repeat;z-index:0"></div>
            <div style="position:fixed;inset:0;background:rgba(15,23,42,0.6);z-index:1"></div>
        @endif

        <div style="position:relative;z-index:10;width:100%;max-width:400px">
            @if($hasLogo)
                <img
                    x-data="{
                        light: {{ Js::from($logoLight ?: $logoDark) }},
                        dark: {{ Js::from($logoDark ?: $logoLight) }},
                        src: {{ Js::from($logoLight ?: $logoDark) }},
                        update() {
                            this.src = document.documentElement.classList.contains('dark') ? this.dark : this.light;
                        }
                    }"
                    x-init="
                        update();
                        new MutationObserver(() => update()).observe(document.documentElement, {attributes: true, attributeFilter: ['class']});
                    "
                    :src="src"
                    alt="{{ $name }}"
                    style="display:block;height:10rem;max-height:10rem;max-width:600px;width:auto;margin:0 auto 1.25rem;object-fit:contain"
                >
            @else
                <div class="tlp-logo-fallback" style="display:flex;align-items:center;justify-content:center;width:3.5rem;height:3.5rem;margin:0 auto 0.75rem;border-radius:0.75rem">
                    <span style="font-size:1.375rem;font-weight:700;line-height:1">{{ strtoupper(mb_substr($name, 0, 2)) }}</span>
                </div>
            @endif

            <h1 class="tlp-title" style="display:none">{{ $name }}</h1>
            <p class="tlp-subtitle" style="text-align:center;font-size:0.875rem;margin:0 0 1.75rem;line-height:1.5">
                {{ $isRegister ? 'Crea tu cuenta para empezar' : ($isPasswordReset ? 'Ingresa tu correo para recuperar tu contraseña' : ($isEmailVerification ? 'Verifica tu direccion de correo' : 'Ingresa tus credenciales para acceder al panel')) }}
            </p>

            <div class="tlp-card" style="border-radius:0.75rem;box-shadow:0 1px 2px rgba(0,0,0,.04),0 2px 4px rgba(0,0,0,.04),0 8px 24px rgba(0,0,0,.06);border:1px solid;overflow:hidden">
                <div style="padding:1.5rem 1.75rem 1rem">
                    {{ $slot }}
                </div>

                    <div style="border-top:1px solid #f1f5f9;padding:1rem 1.75rem;text-align:center">
                        <a href="/" class="tlp-link" style="font-size:0.8125rem;font-weight:500;text-decoration:none">Inicio</a>

                        @if($isRegister || $isPasswordReset || $isEmailVerification)
                            <span class="tlp-link" style="margin:0 0.5rem;opacity:0.4">&middot;</span>
                            <a href="{{ filament()->getLoginUrl() }}" class="tlp-link" style="font-size:0.8125rem;font-weight:500;text-decoration:none">Iniciar sesion</a>
                        @else
                            @if(filament()->hasPasswordReset())
                                <span class="tlp-link" style="margin:0 0.5rem;opacity:0.4">&middot;</span>
                                <a href="{{ filament()->getRequestPasswordResetUrl() }}" class="tlp-link" style="font-size:0.8125rem;font-weight:500;text-decoration:none">¿Olvidaste tu contraseña?</a>
                            @endif

                            @if(filament()->hasRegistration())
                                <span class="tlp-link" style="margin:0 0.5rem;opacity:0.4">&middot;</span>
                                <a href="{{ filament()->getRegistrationUrl() }}" class="tlp-link" style="font-size:0.8125rem;font-weight:500;text-decoration:none">Registrarse</a>
                            @endif
                        @endif
                    </div>
            </div>

            <p class="tlp-footer" style="text-align:center;font-size:0.75rem;margin-top:1.5rem">&copy; {{ date('Y') }} {{ $name }}</p>
        </div>
    </div>
</x-filament-panels::layout.base>
