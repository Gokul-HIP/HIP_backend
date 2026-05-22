<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your email — {{ config('app.name', 'HealthInPocket') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:Arial,Helvetica,sans-serif;color:#333333;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f0f4f8;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                   style="max-width:580px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">

                {{-- ===== HEADER ===== --}}
                <tr>
                    <td style="background:#0da2e7;padding:36px 40px 32px;text-align:center;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td align="center" style="padding-bottom:14px;">
                                    <table role="presentation" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="width:52px;height:52px;background:rgba(255,255,255,0.2);border-radius:50%;text-align:center;vertical-align:middle;">
                                                <img src="https://img.icons8.com/ios/50/ffffff/health-book.png"
                                                     width="26" height="26" alt="" style="display:inline-block;vertical-align:middle;">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p style="margin:0 0 6px;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:0.4px;">
                                        {{ config('app.name', 'HealthInPocket') }}
                                    </p>
                                    <p style="margin:0;font-size:13px;color:rgba(255,255,255,0.88);">
                                        Your health, always in reach
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- ===== BODY ===== --}}
                <tr>
                    <td style="padding:36px 40px 32px;">

                        {{-- Action badge --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;">
                            <tr>
                                <td align="center">
                                    <span style="display:inline-block;background:#e6f7fd;color:#0877b5;font-size:11px;font-weight:600;padding:4px 14px;border-radius:50px;">
                                        ● &nbsp;Action required
                                    </span>
                                </td>
                            </tr>
                        </table>

                        {{-- Greeting --}}
                        <p style="margin:0 0 12px;font-size:17px;font-weight:600;color:#1a202c;">
                            Hello, {{ $user->first_name ?? 'there' }} 👋
                        </p>

                        {{-- Body text --}}
                        <p style="margin:0 0 28px;font-size:14px;line-height:1.75;color:#4a5568;">
                            Thank you for signing up for
                            <strong style="color:#0da2e7;">{{ config('app.name', 'HealthInPocket') }}</strong>.
                            To get started, we just need to verify your email address and make sure
                            everything is set up correctly for your account.
                        </p>

                        {{-- CTA Button --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
                            <tr>
                                <td align="center">
                                    <a href="{{ $verificationUrl }}"
                                       style="display:inline-block;padding:14px 40px;background:#0da2e7;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;border-radius:50px;letter-spacing:0.3px;">
                                        ✓ &nbsp;Verify My Email Address
                                    </a>
                                </td>
                            </tr>
                        </table>

                        {{-- Divider --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                            <tr>
                                <td style="border-top:1px solid #e8edf2;font-size:0;line-height:0;">&nbsp;</td>
                            </tr>
                        </table>

                        {{-- Fallback link --}}
                        <p style="margin:0 0 8px;font-size:12px;color:#718096;">
                            Button not working? Copy and paste this link into your browser:
                        </p>
                        <p style="margin:0;font-size:11px;line-height:1.6;word-break:break-all;">
                            <a href="{{ $verificationUrl }}" style="color:#0da2e7;text-decoration:none;">
                                {{ $verificationUrl }}
                            </a>
                        </p>

                        {{-- Notice box --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;">
                            <tr>
                                <td style="background:#f0f9ff;border-left:3px solid #0da2e7;border-radius:0 8px 8px 0;padding:12px 16px;">
                                    <p style="margin:0;font-size:12px;color:#4a5568;line-height:1.6;">
                                        ⏱ This link <strong>expires in 24 hours</strong>.
                                        If you did not create an account with
                                        {{ config('app.name', 'HealthInPocket') }},
                                        you can safely ignore this email — no action is needed.
                                    </p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                {{-- ===== FOOTER ===== --}}
                <tr>
                    <td style="background:#f7fafc;padding:20px 40px;text-align:center;border-top:1px solid #e8edf2;">
                        <p style="margin:0 0 6px;font-size:11px;color:#a0aec0;line-height:1.6;">
                            © {{ date('Y') }} {{ config('app.name', 'HealthInPocket') }} &nbsp;·&nbsp;
                            <a href="#" style="color:#0da2e7;text-decoration:none;">Privacy Policy</a>
                            &nbsp;·&nbsp;
                            <a href="#" style="color:#0da2e7;text-decoration:none;">Unsubscribe</a>
                        </p>
                        <p style="margin:0;font-size:11px;color:#a0aec0;line-height:1.6;">
                            You received this email because you signed up at
                            {{ parse_url(config('app.url'), PHP_URL_HOST) }}
                        </p>
                    </td>
                </tr>

            </table>

            {{-- Address line below card --}}
            <p style="margin:16px 0 0;font-size:11px;color:#a0aec0;text-align:center;">
                {{ config('app.name', 'HealthInPocket') }} · Your City, Country
            </p>

        </td>
    </tr>
</table>

</body>
</html>