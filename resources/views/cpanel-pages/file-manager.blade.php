<x-filament-panels::page>
    <div class="space-y-6" wire:loading.class.delay.longest="opacity-50">

        @if (! $this->getTableSearch())
        <div class="flex items-center space-x-2 text-sm bg-gray-100 p-2 rounded-lg dark:bg-gray-800">
            <button wire:click="goToRoot" class="hover:text-primary-600 transition">
                <x-heroicon-o-home class="w-5 h-5" />
            </button>

            @php
            $parts = $currentPath === '/' ? [] : array_filter(explode('/', trim($currentPath, '/')));
            $cumulative = '';
            @endphp

            @foreach ($parts as $part)
            @php $cumulative .= '/' . $part; @endphp
            <span class="text-gray-400">|</span>
            <button
                wire:click="navigateTo('{{ $cumulative }}')"
                class="hover:text-primary-600 font-medium">
                {{ $part }}
            </button>
            @endforeach
        </div>
        @endif

        {{ $this->table }}
    </div>

    <div
        x-data="{
            file: {
                path: null,
                type: null,
                name: null,
                downloadUrl: null,
                docKey: null,
            },
            textContent: '',
            loadingText: false,
            savingText: false,
            resetMedia() {
                const video = document.querySelector('#preview-modal video');
                if (video) { video.pause(); video.currentTime = 0; }
                const audio = document.querySelector('#preview-modal audio');
                if (audio) { audio.pause(); audio.currentTime = 0; }
                const iframe = document.querySelector('#preview-modal iframe');
                if (iframe) { iframe.src = ''; }
                this.textContent = '';
                this.loadingText = false;
                this.savingText = false;
            },
            async loadTextContent() {
                if (this.file.type === 'txt' && this.file.path) {
                    this.loadingText = true;
                    try {
                        const response = await fetch(this.file.path);
                        if (!response.ok) throw new Error('Error cargando el archivo');
                        this.textContent = await response.text();
                    } catch (error) {
                        console.error('Error fetching text:', error);
                        this.textContent = 'No se pudo cargar el contenido del archivo.';
                    } finally {
                        this.loadingText = false;
                    }
                }
            },
            async saveTxt() {
                this.savingText = true;
                try {
                    await $wire.saveTxt(this.file.docKey, this.textContent);
                } finally {
                    this.savingText = false;
                }
            }
        }"
        x-on:open-preview.window="
            file = $event.detail;
            $dispatch('open-modal', { id: 'preview-modal' });
            if (file.type === 'txt') {
                loadTextContent();
            }
        "
        x-on:close-modal.window="
            resetMedia();
        "
        x-cloak>
        <x-filament::modal
            id="preview-modal"
            width="4xl"
            icon="heroicon-o-eye"
            :close-by-clicking-away="false"
            :close-by-escaping="false">
            <x-slot name="heading">
                <span x-text="file.name || 'Vista previa'"></span>
            </x-slot>

            <div class="space-y-4">
                <template x-if="file.type === 'txt'">
                    <div>
                        <div x-show="loadingText" class="flex items-center justify-center h-96 text-gray-400">
                            <x-filament::loading-indicator class="w-8 h-8" />
                        </div>
                        <div x-show="!loadingText" class="space-y-3">
                            <textarea x-model="textContent"
                                class="w-full h-96 border rounded-lg bg-gray-50 dark:bg-gray-900 p-4 font-mono text-sm resize-y focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                spellcheck="false"></textarea>
                            <div class="flex justify-end items-center gap-2 min-h-[36px]">
                                <span x-show="savingText" x-cloak class="flex items-center gap-2 text-sm text-gray-500 mr-auto">
                                    <x-filament::loading-indicator class="w-4 h-4" />
                                    Guardando...
                                </span>
                                <x-filament::button
                                        color="primary"
                                        size="sm"
                                        x-on:click="saveTxt()"
                                        x-bind:disabled="savingText">
                                        <x-heroicon-o-check class="w-4 h-4" />
                                        Guardar
                                    </x-filament::button>
                                    <x-filament::button
                                        tag="a"
                                        color="gray"
                                        size="sm"
                                        x-bind:href="file.downloadUrl"
                                        target="_blank">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                        Descargar
                                    </x-filament::button>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="['jpg','jpeg','png','gif','svg','webp'].includes(file.type)">
                    <div class="space-y-3">
                        <img :src="file.path"
                            class="max-w-full max-h-[80vh] mx-auto rounded-lg shadow-lg object-contain"
                            alt="Vista previa">
                        <div class="flex justify-end">
                            <x-filament::button
                                tag="a"
                                color="gray"
                                size="sm"
                                x-bind:href="file.downloadUrl"
                                target="_blank">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                Descargar
                            </x-filament::button>
                        </div>
                    </div>
                </template>

                <template x-if="['mp3','wav','aac','m4a'].includes(file.type)">
                    <div class="space-y-3">
                        <audio controls class="w-full rounded-lg shadow-md" preload="metadata">
                            <source :src="file.path">
                            Tu navegador no soporta audio.
                        </audio>
                        <div class="flex justify-end">
                            <x-filament::button
                                tag="a"
                                color="gray"
                                size="sm"
                                x-bind:href="file.downloadUrl"
                                target="_blank">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                Descargar
                            </x-filament::button>
                        </div>
                    </div>
                </template>

                <template x-if="['mp4','avi','mov'].includes(file.type)">
                    <div class="space-y-3">
                        <video controls class="w-full max-h-[80vh] rounded-lg shadow-lg" preload="metadata">
                            <source :src="file.path">
                            Tu navegador no soporta video.
                        </video>
                        <div class="flex justify-end">
                            <x-filament::button
                                tag="a"
                                color="gray"
                                size="sm"
                                x-bind:href="file.downloadUrl"
                                target="_blank">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                Descargar
                            </x-filament::button>
                        </div>
                    </div>
                </template>
            </div>
        </x-filament::modal>
    </div>

    <div
        x-data="{ shareId: null, code: '' }"
        x-on:open-ack-confirm.window="
            shareId = $event.detail.shareId;
            code = '';
            $dispatch('open-modal', { id: 'ack-confirm' });
        "
        x-cloak>
        <x-filament::modal id="ack-confirm" width="lg" icon="heroicon-o-check-badge" :close-by-clicking-away="false" :close-by-escaping="false">
            <x-slot name="heading">
                Confirmación requerida
            </x-slot>
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    El propietario requiere que confirmes la recepción de este archivo. Ingresa el código enviado por correo.
                </p>
                <x-filament::input.wrapper>
                    <x-filament::input
                        x-model="code"
                        type="text"
                        maxlength="12"
                        autocomplete="one-time-code"
                        placeholder="Código de 6 dígitos" />
                </x-filament::input.wrapper>
                <div class="flex justify-end gap-3 md:gap-4">
                    <x-filament::button color="gray" x-on:click="$wire.resendAckCode()">
                        Reenviar código
                    </x-filament::button>
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'ack-confirm' })">
                        Cancelar
                    </x-filament::button>
                    <x-filament::button color="primary" x-on:click="$wire.confirmAck(shareId, code)">
                        Confirmar
                    </x-filament::button>
                </div>
            </div>
        </x-filament::modal>
    </div>

    <x-filament::modal id="share-info" width="md">
        <x-slot name="heading">
            Compartido con
        </x-slot>

        <div
            x-data="{ shares: [] }"
            x-on:open-share-info.window="
            shares = $event.detail.shares;
            $dispatch('open-modal', { id: 'share-info' });
        "
            class="space-y-3">
            <template x-if="shares.length === 0">
                <p class="text-sm text-gray-500 text-center py-6">
                    Este archivo no está compartido
                </p>
            </template>

            <template x-for="userItem in shares" :key="userItem.name">
                <div class="flex items-center justify-between bg-gray-100 dark:bg-gray-800 rounded-lg px-4 py-3">
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-900 dark:text-gray-100 mb-1"
                            x-text="userItem.name"></span>

                        <div class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-2 items-center">
                            <span class="text-xs text-gray-500 text-right">Permiso</span>
                            <div class="flex">
                                <x-filament::badge
                                    x-show="userItem.permission === 'view'"
                                    color="info">
                                    Solo ver
                                </x-filament::badge>
                                <x-filament::badge
                                    x-show="userItem.permission === 'edit'"
                                    color="warning">
                                    Editar
                                </x-filament::badge>
                            </div>

                            <span class="text-xs text-gray-500 text-right">Confirmación</span>
                            <div class="flex">
                                <x-filament::badge
                                    x-show="userItem.ack_required && !userItem.ack_completed"
                                    color="danger">
                                    Pendiente
                                </x-filament::badge>
                                <x-filament::badge
                                    x-show="userItem.ack_required && userItem.ack_completed"
                                    color="success">
                                    Completada
                                </x-filament::badge>
                                <x-filament::badge
                                    x-show="!userItem.ack_required"
                                    color="gray">
                                    No requerida
                                </x-filament::badge>
                            </div>

                            <span class="text-xs text-gray-500 text-right" x-show="userItem.ack_required && userItem.ack_completed && userItem.ack_completed_at">
                                Fecha
                            </span>
                            <div class="flex" x-show="userItem.ack_required && userItem.ack_completed && userItem.ack_completed_at">
                                <x-filament::badge color="success">
                                    <span x-text="userItem.ack_completed_at"></span>
                                </x-filament::badge>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            x-on:click="$wire.removeShare(userItem.id)"
                            class="text-danger-600 hover:text-danger-700 transition"
                            title="Eliminar acceso">
                            <x-heroicon-o-trash class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </x-filament::modal>

    <script>
    window.addEventListener('open-onlyoffice', event => {
        window.open(event.detail.url, '_blank');
    });

    window.addEventListener('open-download', event => {
        window.open(event.detail.url, '_blank');
    });
    </script>
    <div wire:poll.30s="$refresh"></div>
</x-filament-panels::page>
