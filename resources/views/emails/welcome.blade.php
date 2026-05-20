<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ONEMALL</title>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #1e293b; margin: 0; padding: 0; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        .header { background: #0f172a; padding: 40px; text-align: center; }
        .logo { color: #ffffff; font-size: 24px; font-weight: 900; letter-spacing: -1px; text-transform: uppercase; text-decoration: none; }
        .content { padding: 40px; }
        .greeting { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 16px; }
        .text { font-size: 16px; color: #64748b; margin-bottom: 24px; }
        .cta-button { display: inline-block; padding: 16px 32px; background-color: #3b82f6; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3); }
        .footer { padding: 30px; text-align: center; background: #f1f5f9; font-size: 12px; color: #94a3b8; }
        .highlight { color: #3b82f6; font-weight: 700; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="#" class="logo">ONEMALL</a>
        </div>
        <div class="content">
            <h1 class="greeting">Welcome to the family, {{ $user->name }}!</h1>
            <p class="text">We're absolutely thrilled to have you with us. Your account has been successfully created, and you're now part of the <span class="highlight">ONEMALL</span> community.</p>
            
            <p class="text">Your unique Member ID is: <span class="highlight">{{ $user->user_id }}</span></p>

            <p class="text">Start exploring our premium collection of electronics and gadgets. We've handpicked the best tech just for you.</p>
            
            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ route('home') }}" class="cta-button">Start Shopping</a>
            </div>

            <p class="text" style="font-size: 14px; margin-top: 40px;">If you have any questions, simply reply to this email. Our support team is here for you 24/7.</p>
        </div>
        <div class="footer">
            <p>&copy; 2026 ONEMALL Inc. All rights reserved.</p>
            <p>123 Tech Avenue, Silicon Valley, CA 94025</p>
        </div>
    </div>
</body>
</html>
