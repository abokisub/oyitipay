<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f8;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color: #ffffff;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 3px solid #0056b3;
            /* Primary Brand Color */
        }

        .header img {
            max-height: 50px;
        }

        .content {
            padding: 40px 30px;
            line-height: 1.6;
            color: #444;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #eeeeee;
        }

        .footer a {
            color: #0056b3;
            text-decoration: none;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0056b3;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 20px;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            font-size: 13px;
            margin-top: 20px;
            border: 1px solid #ffeeba;
        }

        .metadata {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-size: 13px;
            margin-top: 25px;
            border: 1px solid #e9ecef;
        }

        .metadata-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .metadata-label {
            font-weight: 600;
            color: #555;
        }

        h1,
        h2,
        h3 {
            color: #222;
            margin-top: 0;
        }
    </style>
</head>

<body>
    <div style="padding: 20px 0;">
        <div class="container">
            <!-- Header -->
            <div class="header">
                {{-- Ensure you have a logo at public/upload/logo.png or adapt path --}}
                <img src="{{ config('app.app_url') . '/upload/welcome.png' }}" alt="{{ config('app.name') }}"
                    style="max-width: 150px;">
            </div>

            <!-- Main Content -->
            <div class="content">
                @yield('content')
            </div>

            <!-- Footer -->
            <div class="footer">
                <p style="margin: 0 0 10px;">
                    <strong>Security Notice:</strong> {{ config('app.name') }} will never ask for your password or PIN
                    via email.
                </p>
                <p style="margin: 0 0 10px;">
                    Need help? Contact <a
                        href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
                </p>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                    <small>Automated email sent from {{ config('app.app_url') }}</small>
                </div>
            </div>
        </div>
    </div>
</body>

</html>