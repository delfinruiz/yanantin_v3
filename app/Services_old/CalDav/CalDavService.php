<?php

namespace App\Services\CalDav;

use App\Models\EmailAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CalDavService
{
    protected function endpointFor(EmailAccount $emailAccount, ?string $calendarEmail = null): string
    {
        $domain = $emailAccount->domain ?? substr(strrchr($emailAccount->email, '@'), 1);
        $email = $calendarEmail ?: $emailAccount->email;

        return "https://{$domain}:2080/calendars/{$email}/calendar/";
    }

    protected function clientFor(EmailAccount $emailAccount)
    {
        $password = $emailAccount->decrypted_password;
        if (! $password) {
            throw new \RuntimeException('No se pudo desencriptar la contraseña para CalDAV');
        }

        return Http::withBasicAuth($emailAccount->email, $password)
            ->withHeaders([
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Accept' => 'application/json, text/calendar',
            ])
            ->timeout(15);
    }

    public function discover(EmailAccount $emailAccount): array
    {
        $endpoint = $this->endpointFor($emailAccount);

        $response = $this->clientFor($emailAccount)
            ->withBody('<?xml version="1.0" encoding="utf-8"?>
<d:propfind xmlns:d="DAV:" xmlns:cs="http://calendarserver.org/ns/">
    <d:prop>
        <d:displayname/>
        <cs:getctag/>
    </d:prop>
</d:propfind>', 'application/xml')
            ->send('PROPFIND', $endpoint);

        if ($response->failed()) {
            throw new \RuntimeException('CalDAV PROPFIND failed: '.$response->body());
        }

        return $response->json() ?: [];
    }

    public function createEvent(EmailAccount $emailAccount, object $calendar, object $event): array
    {
        $endpoint = $this->endpointFor($emailAccount);
        $uid = $event->caldav_uid ?? (string) Str::uuid();
        $dtStart = $event->starts_at;
        $dtEnd = $event->ends_at ?? $event->starts_at;

        $ics = $this->buildIcs($uid, $event->title, $event->description ?? '', $dtStart, $dtEnd, $event->all_day ?? false, $event->color ?? null);

        $response = $this->clientFor($emailAccount)
            ->withBody($ics, 'text/calendar; charset=utf-8')
            ->put($endpoint.$uid.'.ics');

        if ($response->failed()) {
            throw new \RuntimeException('CalDAV PUT failed: '.$response->body());
        }

        $etag = $response->header('ETag');

        return [
            'uid' => $uid,
            'etag' => $etag,
        ];
    }

    public function updateEvent(EmailAccount $emailAccount, object $calendar, object $event): ?string
    {
        if (! $event->caldav_uid) {
            return null;
        }

        $endpoint = $this->endpointFor($emailAccount);
        $uid = $event->caldav_uid;
        $dtStart = $event->starts_at;
        $dtEnd = $event->ends_at ?? $event->starts_at;

        $ics = $this->buildIcs($uid, $event->title, $event->description ?? '', $dtStart, $dtEnd, $event->all_day ?? false, $event->color ?? null);

        $headers = [];
        if ($event->caldav_etag) {
            $headers['If-Match'] = $event->caldav_etag;
        }

        $response = $this->clientFor($emailAccount)
            ->withHeaders($headers)
            ->withBody($ics, 'text/calendar; charset=utf-8')
            ->put($endpoint.$uid.'.ics');

        if ($response->status() === 412) {
            Log::warning('CalDAV ETag mismatch, retrying without If-Match');
            $response2 = $this->clientFor($emailAccount)
                ->withBody($ics, 'text/calendar; charset=utf-8')
                ->put($endpoint.$uid.'.ics');

            if ($response2->failed() && $response2->status() !== 404) {
                throw new \RuntimeException('CalDAV PUT (retry) failed: '.$response2->body());
            }

            return $response2->header('ETag');
        }

        if ($response->failed() && $response->status() !== 404) {
            throw new \RuntimeException('CalDAV PUT failed: '.$response->body());
        }

        return $response->header('ETag');
    }

    public function deleteEvent(EmailAccount $emailAccount, object $event): void
    {
        if (! $event->caldav_uid) {
            return;
        }

        $endpoint = $this->endpointFor($emailAccount);
        $uid = $event->caldav_uid;

        $response = $this->clientFor($emailAccount)->delete($endpoint.$uid.'.ics');

        if ($response->failed() && $response->status() !== 404) {
            throw new \RuntimeException('CalDAV DELETE failed: '.$response->body());
        }
    }

    public function listEvents(EmailAccount $emailAccount, object $calendar): array
    {
        $endpoint = $this->endpointFor($emailAccount);

        $response = $this->clientFor($emailAccount)
            ->withBody('<?xml version="1.0" encoding="utf-8"?>
<d:propfind xmlns:d="DAV:" xmlns:cs="http://calendarserver.org/ns/">
    <d:prop>
        <d:getetag/>
        <d:resourcetype/>
        <d:displayname/>
    </d:prop>
</d:propfind>', 'application/xml')
            ->send('PROPFIND', $endpoint);

        if ($response->failed()) {
            throw new \RuntimeException('CalDAV PROPFIND failed: '.$response->body());
        }

        $events = [];
        $body = $response->body();
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'DAV:');

        if ($xml) {
            $responses = $xml->xpath('//d:response');
            foreach ($responses as $resp) {
                $href = (string) $resp->href;
                $etag = (string) $resp->propstat->prop->getetag;

                if (str_ends_with($href, '.ics')) {
                    $events[] = [
                        'href' => $href,
                        'etag' => $etag,
                    ];
                }
            }
        }

        return $events;
    }

    public function fetchEvent(EmailAccount $emailAccount, string $href): ?CalDavEventDto
    {
        $endpoint = $this->endpointFor($emailAccount);
        $url = rtrim($endpoint, '/').'/'.ltrim($href, '/');

        $response = $this->clientFor($emailAccount)->get($url);

        if ($response->failed()) {
            return null;
        }

        $parser = new CalDavParser;
        $events = $parser->parse($response->body());

        return $events[0] ?? null;
    }

    public function syncDown(EmailAccount $emailAccount, object $calendar): array
    {
        $remoteEvents = $this->listEvents($emailAccount, $calendar);
        $results = [];

        foreach ($remoteEvents as $remote) {
            try {
                $event = $this->fetchEvent($emailAccount, $remote['href']);
                if ($event) {
                    $event->etag = $remote['etag'];
                    $results[] = $event;
                }
            } catch (\Exception $e) {
                Log::warning('CalDAV fetch failed', ['href' => $remote['href'], 'error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    protected function buildIcs(string $uid, string $title, string $description, $dtStart, $dtEnd, bool $allDay, ?string $color): string
    {
        $dateFormat = $allDay ? 'Ymd' : 'Ymd\THis\Z';
        $startStr = $dtStart instanceof \DateTimeInterface ? $dtStart->format($dateFormat) : $dtStart;
        $endStr = $dtEnd instanceof \DateTimeInterface ? $dtEnd->format($dateFormat) : ($dtEnd ?: $dtStart);

        $ics = [];
        $ics[] = 'BEGIN:VCALENDAR';
        $ics[] = 'VERSION:2.0';
        $ics[] = 'PRODID:-//Cahilt//CalDAV//ES';
        $ics[] = 'BEGIN:VEVENT';
        $ics[] = 'UID:'.$uid;
        $ics[] = 'DTSTART'.($allDay ? ';VALUE=DATE' : '').':'.$startStr;
        $ics[] = 'DTEND'.($allDay ? ';VALUE=DATE' : '').':'.$endStr;
        $ics[] = 'SUMMARY:'.$title;
        if ($description) {
            $ics[] = 'DESCRIPTION:'.str_replace(["\r", "\n"], ['', '\\n'], $description);
        }
        if ($color) {
            $ics[] = 'X-APPLE-CALENDAR-COLOR:'.$color;
            $ics[] = 'X-OUTLOOK-COLOR:'.$color;
        }
        $ics[] = 'END:VEVENT';
        $ics[] = 'END:VCALENDAR';

        return implode("\r\n", $ics)."\r\n";
    }
}
