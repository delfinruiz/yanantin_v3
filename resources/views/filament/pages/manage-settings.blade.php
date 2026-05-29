<x-filament-panels::page>
    {{ $this->form }}

    <div class="flex justify-end mt-4">
        <x-filament::button wire:click="save" color="primary">
            Guardar configuracion
        </x-filament::button>
    </div>
</x-filament-panels::page>
