@php
    $tenant = tenant();
    $directory = resource_path('views/tenants/' . $tenant->id);
@endphp

<div class="fi-widget p-6">
    <div class="rounded-xl border border-amber-300 bg-amber-50 p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <x-filament::icon
                    icon="heroicon-o-exclamation-triangle"
                    class="h-8 w-8 text-amber-500"
                />
            </div>
            <div>
                <h3 class="text-lg font-semibold text-amber-800">
                    Carpeta de landing page no creada
                </h3>
                <p class="mt-2 text-sm text-amber-700">
                    No se pudo crear la carpeta automaticamente por falta de permisos.
                    Ejecuta los siguientes comandos en el servidor:
                </p>
                <div class="mt-4 rounded-lg bg-gray-900 p-4 font-mono text-sm text-green-300">
                    <code>mkdir -p {{ $directory }}</code>
                    <br>
                    <code>cp resources/views/tenant/default-landing.blade.php {{ $directory }}/landing.blade.php</code>
                    <br>
                    <code>chown -R www-data:www-data {{ $directory }}</code>
                </div>
                <p class="mt-3 text-xs text-amber-600">
                    Luego de ejecutar los comandos, la landing page estara disponible en
                    <strong>{{ $tenant->id }}.localhost:8000</strong>
                </p>
            </div>
        </div>
    </div>
</div>
