<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Customer;
use App\Models\Setting;

class AppointmentService
{
    private Appointment $appointmentModel;
    private AppointmentSlot $slotModel;
    private Customer $customerModel;
    private Setting $settingModel;

    public function __construct()
    {
        $this->appointmentModel = new Appointment();
        $this->slotModel = new AppointmentSlot();
        $this->customerModel = new Customer();
        $this->settingModel = new Setting();
    }

    public function getAvailableSaturdays(int $weeksAhead = 12): array
    {
        $blockedDates = json_decode($this->settingModel->get('blocked_dates', null, '[]'), true);
        $dates = [];

        for ($i = 0; $i < $weeksAhead; $i++) {
            $date = date('Y-m-d', strtotime("next saturday +{$i} weeks"));
            if (!in_array($date, $blockedDates) && strtotime($date) > time()) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    public function getAvailableSundays(int $weeksAhead = 12): array
    {
        $blockedDates = json_decode($this->settingModel->get('blocked_dates', null, '[]'), true);
        $dates = [];

        for ($i = 0; $i < $weeksAhead; $i++) {
            $date = date('Y-m-d', strtotime("next sunday +{$i} weeks"));
            if (!in_array($date, $blockedDates) && strtotime($date) > time()) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    public function getDateStatus(string $date): string
    {
        $appointments = $this->appointmentModel->getByDate($date);
        $hasPending = false;
        $hasConfirmed = false;

        foreach ($appointments as $appt) {
            if ($appt['status'] === 'pending') $hasPending = true;
            if ($appt['status'] === 'confirmed') $hasConfirmed = true;
        }

        if ($hasPending) return 'pending';   // orange
        if ($hasConfirmed) return 'confirmed'; // green
        return 'available'; // open
    }
}
