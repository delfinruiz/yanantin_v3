<?php

namespace App\Http\Controllers;

use App\Models\MeetRoom;
use App\Models\MeetRoomInvitation;
use App\Models\MeetRoomLog;
use App\Models\MeetRoomParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeetRoomController extends Controller
{
    public function join(string $roomCode, Request $request)
    {
        $room = MeetRoom::where('room_code', $roomCode)->firstOrFail();
        $user = Auth::user();
        $token = $request->get('token');

        $invitation = null;
        if ($token) {
            $invitation = MeetRoomInvitation::where('token', $token)
                ->where('meet_room_id', $room->id)
                ->first();
        }

        $isOwner = $user && $room->isOwner($user);
        $isInvited = $invitation || ($user && $room->isInvited($user));

        if (! $isOwner && ! $isInvited) {
            abort(403, 'No tienes permiso para acceder a esta reunion');
        }

        if ($invitation && $invitation->status === 'pending') {
            $invitation->update(['status' => 'accepted']);
        }

        if ($isOwner) {
            $room->update(['accepting_participants' => false]);

            MeetRoomParticipant::where('meet_room_id', $room->id)
                ->where('user_id', $user->id)
                ->whereNull('left_at')
                ->update(['left_at' => now()]);

            MeetRoomParticipant::withoutEvents(function () use ($room, $user, $request) {
                MeetRoomParticipant::create(
                    [
                        'meet_room_id' => $room->id,
                        'user_id' => $user->id,
                        'display_name' => $user->name,
                        'email' => $user->email,
                        'joined_at' => now(),
                        'is_moderator' => true,
                        'jitsi_connected' => false,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );
            });

            MeetRoomLog::log(
                $room,
                'join_page_viewed',
                $user,
                'Moderador accedio a la pagina de la reunion'
            );

            return view('meet.join', [
                'room' => $room,
                'user' => $user,
                'isOwner' => $isOwner,
                'invitation' => $invitation,
            ]);
        }

        if (! $room->moderatorIsConnected()) {
            return view('meet.waiting-room', [
                'room' => $room,
                'user' => $user,
                'isOwner' => $isOwner,
                'invitation' => $invitation,
            ]);
        }

        MeetRoomLog::log(
            $room,
            'join_page_viewed',
            $user,
            'Invitado accedio a la pagina de la reunion'
        );

        return view('meet.join', [
            'room' => $room,
            'user' => $user,
            'isOwner' => $isOwner,
            'invitation' => $invitation,
        ]);
    }

    public function waitingRoom(string $roomCode)
    {
        $room = MeetRoom::where('room_code', $roomCode)->firstOrFail();
        $user = Auth::user();

        return view('meet.waiting-room', [
            'room' => $room,
            'user' => $user,
            'isOwner' => $room->isOwner($user),
            'invitation' => null,
        ]);
    }

    public function leave(string $roomCode)
    {
        $room = MeetRoom::where('room_code', $roomCode)->first();
        $user = Auth::user();

        if ($room && $user) {
            $participant = MeetRoomParticipant::where('meet_room_id', $room->id)
                ->where('user_id', $user->id)
                ->whereNull('left_at')
                ->first();

            if ($participant) {
                $participant->markLeft();
            }

            MeetRoomLog::log(
                $room,
                'left',
                $user,
                'Usuario salio de la reunion'
            );

            if ($room->isOwner($user) && $room->type === 'unique') {
                $room->update(['status' => 'completed']);

                MeetRoomLog::log(
                    $room,
                    'room_completed',
                    $user,
                    'El moderador finalizo la reunion'
                );
            }
        }

        return view('meet.leave', [
            'room' => $room,
            'user' => $user,
        ]);
    }

    public function apiStatus(string $roomCode): JsonResponse
    {
        $room = MeetRoom::where('room_code', $roomCode)->first();

        if (! $room) {
            return response()->json([
                'exists' => false,
                'canJoin' => false,
            ]);
        }

        return response()->json([
            'exists' => true,
            'canJoin' => $room->can_join,
            'moderatorConnected' => $room->moderatorIsConnected(),
            'acceptingParticipants' => $room->accepting_participants,
            'status' => $room->status,
            'type' => $room->type,
            'scheduledDate' => $room->scheduled_date?->format('Y-m-d'),
            'scheduledTime' => $room->scheduled_time?->format('H:i:s'),
            'waitingRoomEnabled' => $room->waiting_room_enabled,
            'waitingRoomVideoUrl' => $room->waiting_room_video_url,
            'waitingRoomMessage' => $room->waiting_room_message,
            'roomName' => $room->name,
            'organizerName' => $room->user?->name,
        ]);
    }

    public function apiParticipantJoined(Request $request, string $roomCode): JsonResponse
    {
        $room = MeetRoom::where('room_code', $roomCode)->firstOrFail();
        $user = Auth::user();

        $participant = MeetRoomParticipant::updateOrCreate(
            [
                'meet_room_id' => $room->id,
                'user_id' => $user?->id,
            ],
            [
                'participant_id' => $request->input('participantId'),
                'display_name' => $request->input('displayName', $user?->name ?? 'Invitado'),
                'email' => $request->input('email', $user?->email),
                'joined_at' => now(),
                'is_moderator' => $room->isOwner($user),
                'jitsi_connected' => true,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        $invitation = MeetRoomInvitation::where('meet_room_id', $room->id)
            ->where('email', $user?->email)
            ->whereIn('status', ['accepted', 'pending'])
            ->first();

        if ($invitation) {
            $invitation->markAsAttended();
        }

        MeetRoomLog::log(
            $room,
            'participant_joined',
            $user,
            'Participante se unio a la reunion',
            ['participant_id' => $participant->participant_id]
        );

        return response()->json(['success' => true]);
    }

    public function apiParticipantLeft(Request $request, string $roomCode): JsonResponse
    {
        $room = MeetRoom::where('room_code', $roomCode)->firstOrFail();
        $user = Auth::user();
        $participantId = $request->input('participantId');

        $participant = MeetRoomParticipant::where('meet_room_id', $room->id)
            ->whereNull('left_at')
            ->where(function ($q) use ($participantId, $user) {
                $q->where('participant_id', $participantId)
                    ->orWhere('user_id', $user?->id);
            })
            ->first();

        if ($participant) {
            $participant->markLeft();

            if ($participant->is_moderator) {
                $room->update(['accepting_participants' => false]);
            }
        }

        MeetRoomLog::log(
            $room,
            'participant_left',
            $user,
            'Participante salio de la reunion',
            ['participant_id' => $participantId]
        );

        if ($room->isOwner($user) && $room->type === 'unique') {
            $room->update(['status' => 'completed']);
        }

        return response()->json(['success' => true]);
    }

    public function decline(string $roomCode, Request $request)
    {
        $room = MeetRoom::where('room_code', $roomCode)->firstOrFail();

        $invitation = MeetRoomInvitation::where('token', $request->token)
            ->where('meet_room_id', $room->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->markAsDeclined();

        MeetRoomLog::log(
            $room,
            'invitation_declined',
            $invitation->invitable,
            'Invitacion rechazada por '.($invitation->name ?? $invitation->email),
            ['invitation_id' => $invitation->id]
        );

        return view('meet.declined', [
            'room' => $room,
            'invitation' => $invitation,
        ]);
    }

    public function apiToggleRoomAccess(Request $request, string $roomCode): JsonResponse
    {
        $room = MeetRoom::where('room_code', $roomCode)->firstOrFail();
        $user = Auth::user();

        if (! $room->isOwner($user)) {
            return response()->json(['error' => 'No tienes permiso para modificar esta sala'], 403);
        }

        $newStatus = $room->status === 'active' ? 'pending' : 'active';
        $room->update(['status' => $newStatus]);

        MeetRoomLog::log(
            $room,
            'room_access_toggled',
            $user,
            'El moderador cambio el estado de la sala a: '.$newStatus
        );

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => $newStatus === 'active' ? 'Sala habilitada' : 'Sala deshabilitada',
        ]);
    }

    public function heartbeat(string $roomCode): JsonResponse
    {
        $room = MeetRoom::where('room_code', $roomCode)->first();
        $user = Auth::user();

        if (! $room || ! $user) {
            return response()->json(['alive' => false], 404);
        }

        $updated = MeetRoomParticipant::where('meet_room_id', $room->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->touch();

        return response()->json(['alive' => (bool) $updated]);
    }

    public function apiAcceptParticipants(Request $request, string $roomCode): JsonResponse
    {
        $room = MeetRoom::where('room_code', $roomCode)->firstOrFail();
        $user = Auth::user();

        if (! $room->isOwner($user)) {
            return response()->json(['error' => 'No tienes permiso'], 403);
        }

        $accepting = $request->boolean('accepting', true);
        $room->update(['accepting_participants' => $accepting]);

        MeetRoomLog::log(
            $room,
            'accepting_participants_toggled',
            $user,
            $accepting ? 'Habilito la entrada de participantes' : 'Deshabilito la entrada de participantes'
        );

        return response()->json([
            'success' => true,
            'accepting_participants' => $accepting,
        ]);
    }
}
