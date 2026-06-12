<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $room->name }} - Videoconferencia</title>
    <link rel="icon" type="image/x-icon" href="{{ tenant()?->faviconUrl() ?? asset('favicon.ico') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { margin: 0; padding: 0; overflow: hidden; background: #000; }
        #meet { width: 100vw; height: 100vh; }
    </style>
</head>
<body>
    <input type="hidden" id="room-code" value="{{ $room->room_code }}">
    <input type="hidden" id="user-id" value="{{ $user?->id }}">
    <input type="hidden" id="user-name" value="{{ $user?->name ?? 'Invitado' }}">
    <input type="hidden" id="user-email" value="{{ $user?->email }}">
    <input type="hidden" id="is-owner" value="{{ $isOwner ? '1' : '0' }}">
    <input type="hidden" id="jitsi-domain" value="meet.cahilt.pro">

    <div id="meet"></div>

    @unless ($isOwner)
    @php $tenant = tenant(); $logoUrl = $tenant?->logoLightUrl(); @endphp
    @if($logoUrl)
    <div style="position: fixed; top: 16px; left: 16px; z-index: 9999; background: #000; border-radius: 8px; padding: 8px 12px;">
        <img src="{{ $logoUrl }}" alt="{{ $tenant->name }}" style="height: 48px; width: auto; display: block;">
    </div>
    @endif
    @endunless

    @if ($isOwner)
    <div id="participant-toggle" style="position: fixed; top: 16px; left: 16px; z-index: 9999; display: none;">
        <button id="toggle-participants-btn"
            style="padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.3); transition: all 0.2s;">
            Cargando...
        </button>
    </div>
    @endif

    <script src="https://meet.cahilt.pro/external_api.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const roomCode = document.getElementById('room-code').value;
            const userName = document.getElementById('user-name').value;
            const userEmail = document.getElementById('user-email').value;
            const isOwner = document.getElementById('is-owner').value === '1';
            const domain = document.getElementById('jitsi-domain').value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const roomConfig = {
                start_muted_audio: {{ $room->start_muted_audio ? 'true' : 'false' }},
                start_muted_video: {{ $room->start_muted_video ? 'true' : 'false' }},
                lobby_enabled: {{ $room->lobby_enabled ? 'true' : 'false' }},
                require_password: {{ $room->require_password ? 'true' : 'false' }},
                max_participants: {{ $room->max_participants ?? 'null' }}
            };

            const toolbarButtons = ['microphone', 'camera', 'hangup', 'settings', 'chat', 'desktop', 'recording', 'whiteboard', 'closedcaptions', 'tileview', 'fullscreen', 'invite'];

            const options = {
                roomName: roomCode,
                parentNode: document.querySelector('#meet'),
                userInfo: {
                    displayName: userName,
                    email: userEmail
                },
                configOverwrite: {
                    prejoinPageEnabled: false,
                    startWithAudioMuted: roomConfig.start_muted_audio,
                    startWithVideoMuted: roomConfig.start_muted_video,
                    enableLobby: roomConfig.lobby_enabled,
                    maxFullResolutionParticipants: roomConfig.max_participants || -1,
                    noiseSuppression: true,
                    enableClosePage: false,
                    disableBeforeUnloadHandlers: true,
                    disableProfile: true,
                    disableInviteFunctions: true,
                    toolbarButtons: toolbarButtons,
                },
                interfaceConfigOverwrite: {
                    SHOW_JITSI_WATERMARK: false,
                    SHOW_BRAND_WATERMARK: false,
                    SHOW_WATERMARK_FOR_GUESTS: false,
                    SHOW_CHROME_EXTENSION_BANNER: false,
                    SHOW_PROMOTIONAL_CLOSE_PAGE: false,
                    DISABLE_VIDEO_BACKGROUND: true,
                    FILM_STRIP_MAX_HEIGHT: 80,
                },
                lang: 'es'
            };

            const api = new JitsiMeetExternalAPI(domain, options);

            let myParticipantId = null;

            // Register moderator presence immediately so waiting room detects it
            if (isOwner) {
                fetch(`/meet/api/${roomCode}/participant-joined`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        displayName: userName,
                        email: userEmail
                    })
                }).catch(err => console.error('Error al registrar presencia:', err));

                // Heartbeat cada 15s para mantener el updated_at fresco
                setInterval(() => {
                    if (window._leaving) return;
                    fetch(`/meet/api/${roomCode}/heartbeat`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    }).catch(err => console.error('Error en heartbeat:', err));
                }, 15000);

                // Boton de habilitar/deshabilitar entrada de participantes
                const toggleContainer = document.getElementById('participant-toggle');
                const toggleBtn = document.getElementById('toggle-participants-btn');

                async function updateToggleState() {
                    try {
                        const res = await fetch(`/meet/api/${roomCode}/status`);
                        const data = await res.json();
                        const isAccepting = data.acceptingParticipants;
                        toggleContainer.style.display = 'block';
                        toggleBtn.textContent = isAccepting ? 'Deshabilitar entrada' : 'Habilitar entrada';
                        toggleBtn.style.background = isAccepting ? '#ef4444' : '#22c55e';
                        toggleBtn.style.color = '#fff';
                    } catch (e) {
                        // ignore
                    }
                }

                toggleBtn.addEventListener('click', async () => {
                    const isAccepting = toggleBtn.textContent === 'Deshabilitar entrada';
                    toggleBtn.disabled = true;
                    try {
                        const res = await fetch(`/meet/api/${roomCode}/accept-participants`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ accepting: !isAccepting })
                        });
                        if (res.ok) {
                            await updateToggleState();
                        }
                    } catch (e) {
                        console.error('Error al cambiar estado:', e);
                    }
                    toggleBtn.disabled = false;
                });

                // Poll estado del boton cada 10s (por si otra pestana lo cambio)
                setInterval(updateToggleState, 10000);
                updateToggleState();
            }

            api.addEventListener('videoConferenceJoined', (event) => {
                myParticipantId = event.id;

                fetch(`/meet/api/${roomCode}/participant-joined`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        participantId: event.id,
                        displayName: userName,
                        email: userEmail
                    })
                }).catch(err => console.error('Error al registrar participante:', err));

                if (isOwner && roomConfig.lobby_enabled) {
                    api.executeCommand('toggleLobby', true);
                }

                if (isOwner && roomConfig.require_password) {
                    api.addEventListener('participantRoleChanged', (event) => {
                        if (event.role === 'moderator') {
                            api.executeCommand('password', '{{ $room->password }}');
                        }
                    });
                }
            });

            function redirectToLeave() {
                if (window._leaving) return;
                window._leaving = true;
                window.stop();
                fetch(`/meet/api/${roomCode}/participant-left`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        participantId: myParticipantId
                    })
                }).catch(err => console.error('Error al registrar salida:', err));
                setTimeout(() => {
                    window.location.replace(`/meet/${roomCode}/leave`);
                }, 50);
            }

            api.addEventListener('readyToClose', redirectToLeave);
            api.addEventListener('videoConferenceLeft', redirectToLeave);

            window.addEventListener('beforeunload', () => {
                if (!window._leaving) {
                    window.stop();
                    try {
                        navigator.sendBeacon(`/meet/api/${roomCode}/participant-left`, new Blob([
                            JSON.stringify({ participantId: myParticipantId })
                        ], { type: 'application/json' }));
                    } catch (e) {
                        // fallback, ignore
                    }
                }
            });

            // Poll room status as fallback for when moderator ends meeting for all
            setInterval(async () => {
                if (window._leaving) return;
                try {
                    const res = await fetch(`/meet/api/${roomCode}/status`);
                    const data = await res.json();
                    if (data.status === 'completed' || data.status === 'cancelled') {
                        redirectToLeave();
                    }
                } catch (e) {
                    // ignore polling errors
                }
            }, 10000);

            api.addEventListener('participantJoined', (event) => {
                console.log('Participante unido:', event);
            });

            api.addEventListener('participantLeft', (event) => {
                console.log('Participante salio:', event);
            });

            if (isOwner) {
                api.addEventListener('knockingParticipant', (event) => {
                    const approved = confirm(`Participante "${event.participant.name}" quiere unirse. Aceptar?`);
                    api.executeCommand('answerKnockingParticipant', event.participant.id, approved);
                });
            }
        });
    </script>
</body>
</html>
