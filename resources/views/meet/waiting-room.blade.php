<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala de Espera - {{ $room->name }}</title>
    <link rel="icon" type="image/x-icon" href="{{ tenant()?->faviconUrl() ?? asset('favicon.ico') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'media',
            theme: {
                extend: {
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4 transition-colors duration-300">
    <div class="w-full max-w-4xl">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden transition-colors duration-300">
            <div class="p-8 md:p-12">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-full mb-4 float-animation">
                        <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        Sala de Espera
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        {{ $room->name }}
                    </p>
                </div>

                @php
                    $videoUrl = $room->waiting_room_video_url;
                    $embedUrl = $videoUrl;
                    if ($videoUrl && preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $videoUrl, $matches)) {
                        $embedUrl = 'https://www.youtube.com/embed/'.$matches[1];
                    }
                @endphp
                @if($room->waiting_room_video_url)
                    <div class="relative aspect-video rounded-xl overflow-hidden shadow-lg mb-8 group">
                        <iframe
                            id="youtube-player"
                            class="w-full h-full"
                            src="{{ $embedUrl }}?autoplay=1&mute=1&loop=1&controls=1&showinfo=0&rel=0"
                            frameborder="0"
                            allow="autoplay; encrypted-media"
                            allowfullscreen>
                        </iframe>
                        <div class="absolute bottom-4 right-4 flex gap-3">
                            <button
                                id="unmute-btn"
                                class="w-10 h-10 bg-black/60 hover:bg-black/80 rounded-full flex items-center justify-center transition-all"
                                onclick="toggleMute()"
                                title="Activar sonido"
                            >
                                <svg id="mute-icon-off" class="w-5 h-5 text-white hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                </svg>
                                <svg id="mute-icon-on" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <script>
                        let isMuted = true;

                        function toggleMute() {
                            isMuted = !isMuted;
                            const iframe = document.getElementById('youtube-player');
                            iframe.src = iframe.src.replace(/mute=[01]/, 'mute=' + (isMuted ? '1' : '0'));
                            document.getElementById('mute-icon-off').classList.toggle('hidden', isMuted);
                            document.getElementById('mute-icon-on').classList.toggle('hidden', !isMuted);
                            document.getElementById('unmute-btn').title = isMuted ? 'Activar sonido' : 'Silenciar';
                        }
                    </script>
                @else
                    <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-gray-700 dark:to-gray-600 rounded-xl p-8 mb-8 text-center">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-white dark:bg-gray-800 rounded-full shadow-lg mb-6">
                            <svg class="w-10 h-10 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                            Esperando al Moderador
                        </h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            {{ $room->waiting_room_message ?? 'La reunion comenzara pronto. Por favor, espere mientras el moderador se conecta.' }}
                        </p>
                        <div class="flex items-center justify-center space-x-2 text-amber-600 dark:text-amber-400">
                            <div class="w-2 h-2 bg-amber-600 dark:bg-amber-400 rounded-full animate-pulse"></div>
                            <div class="w-2 h-2 bg-amber-600 dark:bg-amber-400 rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
                            <div class="w-2 h-2 bg-amber-600 dark:bg-amber-400 rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Moderador</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $room->user->name }}</p>
                            </div>
                        </div>
                    </div>

                    @if($room->scheduled_date && $room->scheduled_time)
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Fecha y Hora</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $room->scheduled_date->format('d/m/Y') }} - {{ $room->scheduled_time->format('H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tipo de Sala</p>
                                <p class="font-semibold text-gray-900 dark:text-white">Recurrente - Disponible en cualquier momento</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-1">
                                Conexion automatica
                            </p>
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                Se le conectara automaticamente a la reunion cuando el moderador ingrese. Esta pagina se actualizara cada 10 segundos.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Codigo de sala: <span class="font-mono font-semibold text-gray-700 dark:text-gray-300">{{ $room->room_code }}</span>
            </p>
        </div>
    </div>

    <script>
        const roomCode = '{{ $room->room_code }}';
        const isOwner = {{ $isOwner ? 'true' : 'false' }};

        if (isOwner) {
            window.location.href = `/meet/${roomCode}/join`;
        }

        let checkInterval = setInterval(async () => {
            try {
                const response = await fetch(`/meet/api/${roomCode}/status`);
                const data = await response.json();

                if (data.acceptingParticipants && data.moderatorConnected) {
                    clearInterval(checkInterval);
                    window.location.href = `/meet/${roomCode}/join`;
                }
            } catch (error) {
                console.error('Error checking room status:', error);
            }
        }, 5000);
    </script>
</body>
</html>
