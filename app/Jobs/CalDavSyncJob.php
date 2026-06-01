<?php

namespace App\Jobs;

use App\Models\Calendar;
use App\Models\EmailAccount;
use App\Models\Event;
use App\Services\CalDav\CalDavService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CalDavSyncJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public string $tenantId,
        public int $userId,
        public int $emailAccountId,
    ) {}

    public function handle(): void
    {
        tenancy()->initialize($this->tenantId);

        try {
            $emailAccount = EmailAccount::find($this->emailAccountId);
            if (! $emailAccount) {
                return;
            }

            $calDavService = app(CalDavService::class);

            $calendar = Calendar::where('user_id', $this->userId)
                ->where('is_personal', true)
                ->first();

            if (! $calendar) {
                return;
            }

            $remoteMeta = $calDavService->listEvents($emailAccount, $calendar);

            $localEvents = Event::where('calendar_id', $calendar->id)
                ->whereNotNull('caldav_uid')
                ->get()
                ->keyBy('caldav_uid');

            $remoteByUid = [];
            foreach ($remoteMeta as $meta) {
                $uid = pathinfo($meta['href'], PATHINFO_FILENAME);
                $remoteByUid[$uid] = $meta;
            }

            foreach ($remoteMeta as $meta) {
                $uid = pathinfo($meta['href'], PATHINFO_FILENAME);

                if (isset($localEvents[$uid])) {
                    $local = $localEvents[$uid];
                    if ($local->caldav_etag !== $meta['etag']) {
                        $remoteEvent = $calDavService->fetchEvent($emailAccount, $meta['href']);
                        if ($remoteEvent) {
                            $local->update([
                                'title' => $remoteEvent->title,
                                'description' => $remoteEvent->description,
                                'starts_at' => $remoteEvent->startsAt,
                                'ends_at' => $remoteEvent->endsAt,
                                'all_day' => $remoteEvent->allDay,
                                'caldav_etag' => $meta['etag'],
                                'caldav_last_sync_at' => now(),
                            ]);
                        }
                    }
                } else {
                    $remoteEvent = $calDavService->fetchEvent($emailAccount, $meta['href']);
                    if ($remoteEvent) {
                        Event::create([
                            'calendar_id' => $calendar->id,
                            'title' => $remoteEvent->title,
                            'description' => $remoteEvent->description,
                            'starts_at' => $remoteEvent->startsAt,
                            'ends_at' => $remoteEvent->endsAt,
                            'all_day' => $remoteEvent->allDay,
                            'caldav_uid' => $uid,
                            'caldav_etag' => $meta['etag'],
                            'caldav_last_sync_at' => now(),
                            'created_by' => $this->userId,
                        ]);
                    }
                }
            }

            foreach ($localEvents as $uid => $local) {
                if (! isset($remoteByUid[$uid])) {
                    $local->delete();
                }
            }

            $localEventsWithoutSync = Event::where('calendar_id', $calendar->id)
                ->whereNull('caldav_uid')
                ->get();

            foreach ($localEventsWithoutSync as $event) {
                try {
                    $result = $calDavService->createEvent($emailAccount, $calendar, $event);
                    $event->update([
                        'caldav_uid' => $result['uid'],
                        'caldav_etag' => $result['etag'],
                        'caldav_last_sync_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('CalDAV syncUp failed for event '.$event->id, ['error' => $e->getMessage()]);
                }
            }

        } catch (\Exception $e) {
            Log::error('CalDavSyncJob failed', [
                'tenant_id' => $this->tenantId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            tenancy()->end();
        }
    }
}
