<?php

namespace App\Helpers;

class DateHelper
{
    /**
     * Add working days (skipping weekends) to a date.
     */
    public static function addWorkingDays(string $fromDate, int $days): string
    {
        $date = new \DateTime($fromDate);
        $added = 0;

        while ($added < $days) {
            $date->modify('+1 day');
            $dayOfWeek = (int) $date->format('N'); // 1=Mon, 7=Sun
            if ($dayOfWeek <= 5) {
                $added++;
            }
        }

        return $date->format('Y-m-d H:i:s');
    }
}
