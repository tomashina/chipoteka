<?php

final class OrderDurationRules
{
    /**
     * The duration cron runs approximately every 12 minutes. A one-hour window
     * gives it several retry opportunities without backfilling old orders when
     * this mechanism is first enabled.
     */
    const DELIVERY_WINDOW_HOURS = 1;


    /**
     * @param \DateTimeInterface $changedAt
     * @param \DateTimeInterface $now
     * @param int                $thresholdHours
     * @param int                $windowHours
     *
     * @return bool
     */
    public static function isDue(
        \DateTimeInterface $changedAt,
        \DateTimeInterface $now,
        int $thresholdHours,
        int $windowHours = self::DELIVERY_WINDOW_HOURS
    ): bool {
        if ($thresholdHours <= 0 || $windowHours <= 0) {
            return false;
        }

        $ageInSeconds = $now->getTimestamp() - $changedAt->getTimestamp();
        $startsAt     = $thresholdHours * 3600;
        $endsAt       = ($thresholdHours + $windowHours) * 3600;

        return $ageInSeconds >= $startsAt && $ageInSeconds < $endsAt;
    }
}
