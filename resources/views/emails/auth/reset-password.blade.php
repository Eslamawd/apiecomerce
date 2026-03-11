<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:Tahoma,Arial,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6fb;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td style="padding:22px 24px;background:linear-gradient(135deg,#0ea5e9 0%,#14b8a6 55%,#22c55e 100%);color:#ffffff;">
                        <div style="font-size:13px;opacity:0.9;letter-spacing:0.08em;">{{ $appName }}</div>
                        <h1 style="margin:8px 0 0;font-size:24px;line-height:1.35;">Password Reset Request</h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 10px;font-size:16px;">Hi {{ $userName }},</p>
                        <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.8;">
                            We received a request to reset your password for <strong>{{ $appName }}</strong>. Click the button below to choose a new password.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0 16px;">
                            <tr>
                                <td align="center">
                                    <a href="{{ $resetUrl }}" style="display:inline-block;background:#0ea5e9;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:10px;font-size:14px;font-weight:700;">
                                        Reset Password
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 8px;font-size:13px;color:#64748b;line-height:1.8;">
                            This reset link will expire in {{ $expiryMinutes }} minutes.
                        </p>
                        <p style="margin:0 0 8px;font-size:13px;color:#64748b;line-height:1.8;">
                            If the button does not work, copy and paste this URL into your browser:
                        </p>
                        <p style="margin:0 0 12px;font-size:12px;word-break:break-all;color:#0f172a;">{{ $resetUrl }}</p>

                        <p style="margin:0;font-size:12px;color:#6b7280;line-height:1.7;">
                            If you did not request a password reset, you can safely ignore this email.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
