<?php

namespace App\Commands;

use App\Models\Appointment;
use App\Models\Setting;
use App\Services\AppointmentNotificationService;
use App\Helpers\DateHelper;

class AppointmentCronHandler
{
    private Appointment $appointmentModel;
    private Setting $settingModel;
    private AppointmentNotificationService $notificationService;

    public function __construct()
    {
        $this->appointmentModel = new Appointment();
        $this->settingModel = new Setting();
        $this->notificationService = new AppointmentNotificationService();
    }

    /**
     * Check for appointments past payment deadline → send reminder + set overdue.
     */
    public function checkPaymentDeadlines(): void
    {
        $appointments = $this->appointmentModel->getPendingPaymentOverdue();
        $extraDays = (int) $this->settingModel->get('appointment_reminder_extra_days', null, '2');

        echo "Found " . count($appointments) . " overdue payment(s).\n";

        foreach ($appointments as $appointment) {
            $reminderDeadline = DateHelper::addWorkingDays(date('Y-m-d H:i:s'), $extraDays);

            // Build payment URL
            $lang = $appointment['lang'] ?? 'nl';
            $baseUrl = $_ENV['APP_URL'] ?? '';
            $paymentUrl = $lang === 'fr'
                ? "{$baseUrl}/fr/rendez-vous/betalen/{$appointment['payment_token']}"
                : "{$baseUrl}/afspraken/betalen/{$appointment['payment_token']}";

            // Send reminder
            $this->notificationService->sendPaymentReminder($appointment, $paymentUrl, $reminderDeadline);

            // Update appointment
            $this->appointmentModel->update($appointment['id'], [
                'payment_status' => 'overdue',
                'reminder_sent_at' => date('Y-m-d H:i:s'),
                'reminder_deadline' => $reminderDeadline,
            ]);

            echo "  Reminder sent for appointment #{$appointment['id']} ({$appointment['email']})\n";
        }
    }

    /**
     * Check for appointments past reminder deadline → cancel and release slot.
     */
    public function checkReminderDeadlines(): void
    {
        $appointments = $this->appointmentModel->getOverdueReminderExpired();

        echo "Found " . count($appointments) . " expired reminder(s).\n";

        foreach ($appointments as $appointment) {
            // Cancel appointment
            $this->appointmentModel->update($appointment['id'], [
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);

            // Send cancellation notice
            $this->notificationService->sendCancellationNotice($appointment);

            echo "  Cancelled appointment #{$appointment['id']} ({$appointment['email']})\n";
        }
    }

    /**
     * Send reminders for upcoming confirmed appointments.
     */
    public function sendPreAppointmentReminders(): void
    {
        $daysAhead = (int) $this->settingModel->get('appointment_pre_reminder_days', null, '3');
        $appointments = $this->appointmentModel->getUpcomingForReminder($daysAhead);

        echo "Found " . count($appointments) . " upcoming appointment(s) for reminder.\n";

        foreach ($appointments as $appointment) {
            $this->notificationService->sendPreAppointmentReminder($appointment);

            $this->appointmentModel->update($appointment['id'], [
                'pre_reminder_sent_at' => date('Y-m-d H:i:s'),
            ]);

            echo "  Pre-reminder sent for appointment #{$appointment['id']} ({$appointment['email']})\n";
        }
    }
}
