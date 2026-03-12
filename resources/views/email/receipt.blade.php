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
            <p>Hello {{ $name }},</p>
            <p>Thank you for your purchase on <strong>{{ $app_name }}</strong>.</p>
            <p>Please find attached the PDF receipt for your transaction.</p>

            <div class="card">
                <p><strong>Transaction ID:</strong> #{{ $transid }}</p>
                <p><strong>Date:</strong> {{ $date }}</p>
                <p><strong>Status:</strong> Successful</p>
            </div>

            <p>If you have any questions regarding this purchase, please contact our support team.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $app_name }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>