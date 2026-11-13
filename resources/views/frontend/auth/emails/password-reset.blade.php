<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f8fafc;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid #f1f5f9;
        }
        .header {
            background-color: #0f172a;
            padding: 40px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.025em;
            text-transform: uppercase;
        }
        .content {
            padding: 40px;
        }
        .content h2 {
            margin-top: 0;
            color: #0f172a;
            font-size: 20px;
            font-weight: 700;
        }
        .btn {
            display: inline-block;
            background-color: #3b82f6;
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.1em;
            margin: 30px 0;
            text-align: center;
        }
        .footer {
            padding: 24px 40px;
            background-color: #f8fafc;
            border-top: 1px solid #f1f5f9;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .footer p {
            margin: 5px 0;
        }
        .divider {
            margin: 30px 0;
            border-top: 1px solid #f1f5f9;
        }
        .raw-link {
            word-break: break-all;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ONEMALL</h1>
        </div>
        <div class="content">
            <h2>Hello!</h2>
            @if(!empty($newAccount))
                <p>An account was created for you while placing your order. Use the button below to choose your password.</p>
            @else
                <p>You are receiving this email because we received a password reset request for your account.</p>
            @endif
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="btn">{{ !empty($newAccount) ? 'Set Password' : 'Reset Password' }}</a>
            </div>
            
            <p>This link will expire in {{ $expireMinutes }} minutes.</p>
            @if(empty($newAccount))
                <p>If you did not request a password reset, no further action is required.</p>
            @endif
            
            <div class="divider"></div>
            
            <p style="font-size: 12px; color: #64748b;">If you're having trouble clicking the button, copy and paste the URL below into your web browser:</p>
            <p class="raw-link">{{ $resetUrl }}</p>
        </div>
        <div class="footer">
            <p>&copy; 2026 ONEMALL. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
