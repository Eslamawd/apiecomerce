<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Stone | Reset Your Password')
            ->view('emails.auth.reset-password', [
                'resetUrl' => $resetUrl,
                'userName' => $notifiable->name ?? 'User',
                'appName' => config('app.name', 'Stone'),
                'expiryMinutes' => (int) config('auth.passwords.users.expire', 60),
            ]);
    }
}
