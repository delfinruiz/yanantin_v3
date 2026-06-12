<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MeetRoom;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MeetRoomPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeetRoom');
    }

    public function view(AuthUser $authUser, MeetRoom $meetRoom): bool
    {
        if ($meetRoom->canAccess($authUser)) {
            return true;
        }

        return $authUser->can('View:MeetRoom');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeetRoom');
    }

    public function update(AuthUser $authUser, MeetRoom $meetRoom): bool
    {
        if ($meetRoom->isOwner($authUser)) {
            return true;
        }

        return $authUser->can('Update:MeetRoom');
    }

    public function delete(AuthUser $authUser, MeetRoom $meetRoom): bool
    {
        if ($meetRoom->isOwner($authUser)) {
            return true;
        }

        return $authUser->can('Delete:MeetRoom');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MeetRoom');
    }

    public function restore(AuthUser $authUser, MeetRoom $meetRoom): bool
    {
        if ($meetRoom->isOwner($authUser)) {
            return true;
        }

        return $authUser->can('Restore:MeetRoom');
    }

    public function forceDelete(AuthUser $authUser, MeetRoom $meetRoom): bool
    {
        if ($meetRoom->isOwner($authUser)) {
            return true;
        }

        return $authUser->can('ForceDelete:MeetRoom');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeetRoom');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeetRoom');
    }

    public function replicate(AuthUser $authUser, MeetRoom $meetRoom): bool
    {
        if ($meetRoom->isOwner($authUser)) {
            return true;
        }

        return $authUser->can('Replicate:MeetRoom');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeetRoom');
    }
}
