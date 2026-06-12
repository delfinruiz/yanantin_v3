<x-mail::message>
# Invitacion a Reunion de Videoconferencia

Hola {{ $guestName ?? 'estimado invitado' }},

Has sido invitado a participar en una reunion de videoconferencia.

<x-mail::panel>
** reunion:** {{ $roomName }}

@if($roomDescription)
**Descripcion:** {{ $roomDescription }}
@endif

**Fecha:** {{ $scheduledDate }}

**Hora:** {{ $scheduledTime }}

**Organizador:** {{ $organizerName }}

**Codigo de sala:** `{{ $roomCode }}`
</x-mail::panel>

<x-mail::button :url="$joinUrl" color="success">
Unirse a la Reunion
</x-mail::button>

Si el boton no funciona, copia y pega este enlace en tu navegador:
{{ $joinUrl }}

---

**Importante:**
- Asegurate de tener una conexion estable a internet
- Prueba tu camara y microfono antes de la reunion
- Llegue 5 minutos antes de la hora programada

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
