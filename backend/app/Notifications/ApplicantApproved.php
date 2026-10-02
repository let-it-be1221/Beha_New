<?php

namespace App\Notifications;

use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ApplicantApproved — sent to the new user when their account is created.
 *
 * Spec §19 — includes one-time temporary password (cryptographically secure,
 * short-lived, hashed). The user must change it on first login.
 *
 * NOTE: The temp password is included in the email body for dev convenience.
 * In production, prefer sending a secure magic-link or out-of-band channel.
 */
class ApplicantApproved extends Notification
{
    use Queueable;

    public function __construct(
        public Applicant $applicant,
        public string $tempPassword,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Welcome to Beha — Your Account is Ready')
            ->greeting("Hello {$this->applicant->full_name},")
            ->line('Your application has been approved and your account has been created.')
            ->line('**Official ID:** ' . $this->applicant->generated_official_id)
            ->line('**Username:** ' . $notifiable->username)
            ->line('**Temporary Password:** `' . $this->tempPassword . '`')
            ->line('This password is valid for ' . config('beha.password_policy.temp_password_ttl_hours', 24) . ' hours.')
            ->action('Sign in', config('app.frontend_url', 'http://localhost:5173') . '/login')
            ->line('You will be required to change this password on your first login.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Your Beha account has been created',
            'application_code' => $this->applicant->application_code,
            'official_id' => $this->applicant->generated_official_id,
            'must_change_password' => true,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
