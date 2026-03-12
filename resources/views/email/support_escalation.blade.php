<!DOCTYPE html>
<html>

<head>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .content {
            margin-bottom: 30px;
        }

        .footer {
            font-size: 12px;
            color: #888;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .highlight {
            font-weight: bold;
            color: #00A86B;
        }

        .card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>{{ $title }}</h2>
        </div>
        <div class="content">
            <p>Hello Admin,</p>
            <p>An escalation request has been triggered by a user on <strong>{{ $app_name }}</strong>.</p>

            <div class="card">
                <p><strong>User:</strong> {{ $name }} ({{ $username }})</p>
                <p><strong>Ticket ID:</strong> #{{ $transid }}</p>
                <p><strong>Date:</strong> {{ $date }}</p>
                <p><strong>Message:</strong> User has requested to speak with a human support agent.</p>
            </div>

            <p>Please log in to the admin dashboard to respond to this ticket.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $app_name }}. All rights reserved.</p>
            <p>Support Phone: {{ $app_phone }}</p>
        </div>
    </div>
</body>

</html>