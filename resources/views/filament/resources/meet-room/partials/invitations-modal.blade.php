<div class="space-y-3">
    @forelse($invitations as $invitation)
        @php $user = $invitation->invitable; @endphp
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                    @if($user && $user->avatar_url)
                        <img src="{{ $user->getFilamentAvatarUrl() }}" alt="" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $invitation->name ?? $invitation->email }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $invitation->email }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($invitation->invitation_type === 'internal')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        Interno
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                        Externo
                    </span>
                @endif
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium @switch($invitation->status)
                    @case('pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @break
                    @case('accepted') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @break
                    @case('declined') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @break
                    @case('attended') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @break
                    @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                @endswitch">
                    @switch($invitation->status)
                        @case('pending') Pendiente @break
                        @case('accepted') Aceptado @break
                        @case('declined') Rechazado @break
                        @case('attended') Asistio @break
                        @default {{ $invitation->status }}
                    @endswitch
                </span>
            </div>
        </div>
    @empty
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <p class="text-sm">No hay invitados</p>
        </div>
    @endforelse
</div>
