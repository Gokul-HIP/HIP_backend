<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $success ? 'Email Verified' : 'Verification Failed' }} — {{ config('app.name', 'HealthInPocket') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 32px 16px;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #f0f4f8;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }
        .header {
            background: #0da2e7;
            padding: 36px 32px 28px;
            text-align: center;
            color: #fff;
        }
        .logo {
            width: 56px;
            height: 56px;
            margin: 0 auto 14px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        .header h1 {
            margin: 0 0 6px;
            font-size: 22px;
            font-weight: 700;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .body {
            padding: 36px 32px 32px;
            text-align: center;
        }
        .icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: 700;
        }
        .icon.success {
            background: #e6f9ef;
            color: #16a34a;
        }
        .icon.error {
            background: #fef2f2;
            color: #dc2626;
        }
        .title {
            margin: 0 0 12px;
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
        }
        .message {
            margin: 0 0 24px;
            font-size: 15px;
            line-height: 1.7;
            color: #4a5568;
        }
        .name {
            color: #0da2e7;
            font-weight: 600;
        }
        .notice {
            margin-top: 8px;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
            text-align: left;
        }
        .notice.success {
            background: #f0f9ff;
            border-left: 3px solid #0da2e7;
            color: #4a5568;
        }
        .notice.error {
            background: #fff7ed;
            border-left: 3px solid #f59e0b;
            color: #4a5568;
        }
        .footer {
            padding: 18px 32px;
            text-align: center;
            background: #f7fafc;
            border-top: 1px solid #e8edf2;
            font-size: 11px;
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="logo">+</div>
            <h1>{{ config('app.name', 'HealthInPocket') }}</h1>
            <p>Your health, always in reach</p>
        </div>

        <div class="body">
            @if ($success)
                <div class="icon success">&#10003;</div>
                <h2 class="title">Email Verified!</h2>
                <p class="message">
                    @if (!empty($userName))
                        Great news, <span class="name">{{ $userName }}</span>!
                    @endif
                    Your email address has been verified successfully. You can now return to the
                    {{ config('app.name', 'HealthInPocket') }} app and continue using your account.
                </p>
                <div class="notice success">
                    You may close this page and go back to the mobile app. No further action is needed here.
                </div>
            @else
                <div class="icon error">!</div>
                <h2 class="title">Verification Failed</h2>
                <p class="message">{{ $message }}</p>
                <div class="notice error">
                    Open the {{ config('app.name', 'HealthInPocket') }} app and request a new verification email if the link has expired or was already used.
                </div>
            @endif
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'HealthInPocket') }}
        </div>
    </div>
</body>
</html>
