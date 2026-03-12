<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Inter', Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 650px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color: #00A86B;
            padding: 30px;
            color: white;
            text-align: center;
        }

        .content {
            padding: 30px;
        }

        .footer {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .info-box {
            background-color: #f0fdf4;
            border: 1px solid #dcfce7;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-label {
            color: #666;
        }

        .info-value {
            font-weight: 600;
            color: #111;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 13px;
        }

        th {
            text-align: left;
            padding: 12px 8px;
            border-bottom: 2px solid #eee;
            color: #666;
            font-weight: 600;
        }

        td {
            padding: 12px 8px;
            border-bottom: 1px solid #f5f5f5;
            vertical-align: top;
        }

        .amount {
            text-align: right;
            font-weight: 600;
        }

        .debit {
            color: #e53e3e;
        }

        .credit {
            color: #38a169;
        }

        .date {
            color: #888;
            white-space: nowrap;
        }

        .desc {
            max-width: 250px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="title">Account Statement</div>
            <p style="margin: 0; opacity: 0.9;">{{ $app_name }}</p>
        </div>
        <div class="content">
            <p>Hello <strong>{{ $name }}</strong>,</p>
            <p>Please find below the account statement for your account ({{ $username }}) from
                <strong>{{ $start_date }}</strong> to <strong>{{ $end_date }}</strong>.
            </p>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Account Name:</span>
                    <span class="info-value">{{ $name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Period:</span>
                    <span class="info-value">{{ $start_date }} - {{ $end_date }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Current Balance:</span>
                    <span class="info-value">₦{{ number_format($closing_balance, 2) }}</span>
                </div>
            </div>

            <div
                style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 20px; text-align: center; border-radius: 8px; margin-top: 20px;">
                <p style="margin: 0; font-weight: 600; color: #475569;">Detailed Statement Attached</p>
                <p style="margin: 5px 0 0; font-size: 13px; color: #64748b;">A comprehensive PDF containing all {{
                    count($transactions) }} transactions for this period is attached to this email.</p>
            </div>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="font-size: 14px; line-height: 1.6;">
                    If you have any questions regarding this statement, please contact our support team or chat with us
                    on WhatsApp.
                </p>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ $app_name }}. All rights reserved.<br>
            This is an automated message, please do not reply.
        </div>
    </div>
</body>

</html>