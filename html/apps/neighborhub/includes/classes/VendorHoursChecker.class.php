<?php

if (!defined('MB_RUNNING')) {
    exit;
}

/**
 * Evaluates a merchant's JSON weekly schedule and date overrides.
 *
 * Expected shape:
 * {
 *   "weekly": {
 *     "monday": [{"open": "09:00", "close": "14:00"}, {"open": "16:00", "close": "21:00"}]
 *   },
 *   "holidays": {"2026-12-25": []}
 * }
 */
class VendorHoursChecker
{
    private const DAYS = [
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
    ];

    public static function isOpen($hours, ?DateTimeImmutable $now = null): bool
    {
        return self::getCurrentInterval($hours, $now) !== null;
    }

    public static function getMessage($hours, ?DateTimeImmutable $now = null): string
    {
        $now = $now ?: new DateTimeImmutable();
        $interval = self::getCurrentInterval($hours, $now);

        if ($interval !== null) {
            $close = $interval['close'];
            if ($close <= $interval['open']) {
                $close = $close->modify('+1 day');
            }
            $minutes = max(1, (int)ceil(($close->getTimestamp() - $now->getTimestamp()) / 60));
            return 'Closes in ' . self::formatDuration($minutes);
        }

        $nextOpen = self::getNextOpenDateTime($hours, $now);
        return $nextOpen ? 'Opens at ' . self::formatDateTime($nextOpen, $now) : 'Closed - Hours not listed';
    }

    public static function getNextOpenTime($hours, ?DateTimeImmutable $now = null): string
    {
        $now = $now ?: new DateTimeImmutable();
        $nextOpen = self::getNextOpenDateTime($hours, $now);
        return $nextOpen ? self::formatDateTime($nextOpen, $now) : 'a later date';
    }

    public static function getOperationalDetails($hours, ?DateTimeImmutable $now = null): array
    {
        $now = $now ?: new DateTimeImmutable();
        $interval = self::getCurrentInterval($hours, $now);
        if ($interval !== null) {
            $close = $interval['close'];
            if ($close <= $interval['open']) {
                $close = $close->modify('+1 day');
            }
            $minutes = max(1, (int)ceil(($close->getTimestamp() - $now->getTimestamp()) / 60));
            return [
                'status' => 'open',
                'label' => $minutes <= 30 ? 'Closing soon' : 'Open now',
                'urgency' => $minutes <= 30 ? 'closing-soon' : '',
                'message' => 'Closes in ' . self::formatDuration($minutes),
            ];
        }

        $next = self::getNextOpenDateTime($hours, $now);
        if ($next === null) {
            return ['status' => 'closed', 'label' => 'Closed', 'urgency' => '', 'message' => 'Hours not listed'];
        }
        $minutes = max(1, (int)ceil(($next->getTimestamp() - $now->getTimestamp()) / 60));
        return [
            'status' => 'closed',
            'label' => $minutes <= 30 ? 'Opening soon' : 'Closed',
            'urgency' => $minutes <= 30 ? 'opening-soon' : '',
            'message' => $minutes <= 30
                ? 'Opens in ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's')
                : 'Opens at ' . self::formatDateTime($next, $now),
        ];
    }

    private static function getCurrentInterval($hours, ?DateTimeImmutable $now): ?array
    {
        $now = $now ?: new DateTimeImmutable();
        $schedule = self::decode($hours);
        if (!$schedule) {
            return null;
        }

        $today = $now->format('Y-m-d');
        $todayIntervals = self::intervalsForDate($schedule, $now);
        foreach ($todayIntervals as $interval) {
            if (self::contains($interval, $now)) {
                return $interval;
            }
        }

        $yesterday = $now->modify('-1 day');
        foreach (self::intervalsForDate($schedule, $yesterday) as $interval) {
            if ($interval['close'] <= $interval['open'] && self::contains($interval, $now)) {
                return $interval;
            }
        }

        return null;
    }

    private static function getNextOpenDateTime($hours, DateTimeImmutable $now): ?DateTimeImmutable
    {
        $schedule = self::decode($hours);
        if (!$schedule) {
            return null;
        }

        for ($offset = 0; $offset <= 7; $offset++) {
            $date = $now->setTime(0, 0)->modify('+' . $offset . ' day');
            $earliest = null;
            foreach (self::intervalsForDate($schedule, $date) as $interval) {
                if ($offset === 0 && $interval['open'] <= $now) {
                    continue;
                }
                if ($earliest === null || $interval['open'] < $earliest) {
                    $earliest = $interval['open'];
                }
            }
            if ($earliest !== null) {
                return $earliest;
            }
        }

        return null;
    }

    private static function intervalsForDate(array $schedule, DateTimeImmutable $date): array
    {
        $dateKey = $date->format('Y-m-d');
        $overrides = isset($schedule['holidays']) && is_array($schedule['holidays'])
            ? $schedule['holidays']
            : [];
        $rawIntervals = array_key_exists($dateKey, $overrides)
            ? $overrides[$dateKey]
            : (($schedule['weekly'][strtolower($date->format('l'))] ?? []));

        if (!is_array($rawIntervals)) {
            return [];
        }

        $intervals = [];
        foreach ($rawIntervals as $rawInterval) {
            if (!is_array($rawInterval) || !isset($rawInterval['open'], $rawInterval['close'])) {
                continue;
            }
            $open = self::time($date, $rawInterval['open']);
            $close = self::time($date, $rawInterval['close']);
            if ($open && $close) {
                $intervals[] = ['open' => $open, 'close' => $close];
            }
        }
        return $intervals;
    }

    private static function contains(array $interval, DateTimeImmutable $now): bool
    {
        $close = $interval['close'];
        if ($close <= $interval['open']) {
            $close = $close->modify('+1 day');
        }
        return $now >= $interval['open'] && $now < $close;
    }

    private static function time(DateTimeImmutable $date, $value): ?DateTimeImmutable
    {
        if (!is_string($value) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return null;
        }
        [$hour, $minute] = array_map('intval', explode(':', $value));
        return $date->setTime($hour, $minute);
    }

    private static function decode($hours): ?array
    {
        if (is_string($hours)) {
            $hours = json_decode($hours, true);
        }
        if (!is_array($hours) || !isset($hours['weekly']) || !is_array($hours['weekly'])) {
            return null;
        }
        return $hours;
    }

    private static function formatDateTime(DateTimeImmutable $dateTime, DateTimeImmutable $now): string
    {
        return $dateTime->format('Y-m-d') === $now->format('Y-m-d')
            ? $dateTime->format('g:i A')
            : $dateTime->format('l \a\t g:i A');
    }

    private static function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;
        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' hour' . ($hours === 1 ? '' : 's');
        }
        if ($remainingMinutes > 0) {
            $parts[] = $remainingMinutes . ' minute' . ($remainingMinutes === 1 ? '' : 's');
        }
        return implode(' ', $parts);
    }
}
