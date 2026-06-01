<?php

namespace App\Services\CalDav;

class CalDavParser
{
    public function parse(string $icsContent): array
    {
        $events = [];
        $lines = explode("\n", str_replace("\r\n", "\n", $icsContent));
        $current = null;

        foreach ($lines as $line) {
            $line = rtrim($line);
            if ($line === 'BEGIN:VEVENT') {
                $current = [];
            } elseif ($line === 'END:VEVENT') {
                if ($current) {
                    $events[] = $this->buildEvent($current);
                }
                $current = null;
            } elseif ($current !== null) {
                $this->parseLine($current, $line);
            }
        }

        return $events;
    }

    protected function parseLine(array &$event, string $line): void
    {
        if (str_contains($line, ':')) {
            $colonPos = strpos($line, ':');
            $key = substr($line, 0, $colonPos);
            $value = substr($line, $colonPos + 1);

            if (str_contains($key, ';')) {
                $parts = explode(';', $key);
                $key = $parts[0];
            }

            $event[$key] = $value;
        }
    }

    protected function buildEvent(array $raw): CalDavEventDto
    {
        $dtStart = $raw['DTSTART'] ?? null;
        $dtEnd = $raw['DTEND'] ?? null;
        $allDay = ! str_contains($dtStart ?? '', 'T');

        return new CalDavEventDto(
            title: $raw['SUMMARY'] ?? 'Sin título',
            description: $raw['DESCRIPTION'] ?? null,
            startsAt: $dtStart ? new \DateTimeImmutable($dtStart) : new \DateTimeImmutable,
            endsAt: $dtEnd ? new \DateTimeImmutable($dtEnd) : null,
            allDay: $allDay,
            uid: $raw['UID'] ?? null,
        );
    }
}
