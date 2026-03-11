<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Stone | Verify Your Email')
            ->view('emails.auth.verify-email', [
                'verificationUrl' => $verificationUrl,
                'userName' => $notifiable->name ?? 'User',
                'appName' => config('app.name', 'Stone'),
            ]);
    }
}
