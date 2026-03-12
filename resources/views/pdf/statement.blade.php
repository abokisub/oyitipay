<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Account Statement</title>
    <style>
        @page {
            margin: 100px 25px;
        }

        header {
            position: fixed;
            top: -60px;
            left: 0px;
            right: 0px;
            height: 50px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        footer {
            position: fixed;
            bottom: -60px;
            left: 0px;
            right: 0px;
            height: 50px;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            margin-top: 20px;
        }

        .watermark {
            position: fixed;
            top: 25%;
            left: 10%;
            width: 80%;
            text-align: center;
            opacity: 0.05;
            z-index: -1000;
            transform: rotate(-45deg);
            font-size: 80px;
            font-weight: bold;
            color: #000;
        }

        .header-content {
            margin-bottom: 30px;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #00A86B;
            margin-bottom: 5px;
        }

        .app-name {
            font-size: 14px;
            color: #666;
        }

        .statement-title {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
            margin-top: -50px;
            color: #444;
        }

        .user-info {
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #00A86B;
            padding-bottom: 15px;
        }

        .info-table {
            width: 100%;
        }

        .info-label {
            color: #777;
            font-size: 11px;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 14px;
            font-weight: bold;
        }

        .summary-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .summary-title {
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        table.transactions {
            width: 100%;
            border-collapse: collapse;
        }

        table.transactions th {
            background-color: #f2f2f2;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }

        table.transactions td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .amount {
            text-align: right;
            font-weight: bold;
        }

        .debit {
            color: #d9534f;
        }

        .credit {
            color: #5cb85c;
        }

        .status-success {
            color: #5cb85c;
        }

        .status-fail {
            color: #d9534f;
        }
    </style>
</head>

<body>
    <div class="watermark">{{ strtoupper($app_name) }}</div>

    <header>
        <span style="color: #999;">Account Statement - {{ $app_name }}</span>
    </header>

    <footer>
        &copy; {{ date('Y') }} {{ $app_name }}. All rights reserved. This is a computer-generated document.
    </footer>

    <div class="header-content">
        <div class="logo">{{ $app_name }}</div>
        <div class="app-name">Financial Services</div>
        <div class="statement-title">ACCOUNT STATEMENT</div>
    </div>

    <div class="user-info">
        <table class="info-table">
            <tr>
                <td>
                    <div class="info-label">ACCOUNT HOLDER</div>
                    <div class="info-value">{{ strtoupper($name) }}</div>
                    <div style="margin-top: 5px;">{{ $email }}</div>
                    <div>{{ $phone }}</div>
                </td>
                <td style="text-align: right;">
                    <div class="info-label">STATEMENT PERIOD</div>
                    <div class="info-value">{{ $start_date }} - {{ $end_date }}</div>
                    <div style="margin-top: 5px;">Generated on: {{ date('d M Y, H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <div class="summary-title">ACCOUNT SUMMARY</div>
        <table class="info-table">
            <tr>
                <td>
                    <div class="info-label">OPENING BALANCE</div>
                    <div class="info-value">&#8358;{{ number_format($opening_balance, 2) }}</div>
                </td>
                <td>
                    <div class="info-label">TOTAL DEBITS</div>
                    <div class="info-value" style="color: #d9534f;">&#8358;{{ number_format($total_debit, 2) }}</div>
                </td>
                <td style="text-align: right;">
                    <div class="info-label">CLOSING BALANCE</div>
                    <div class="info-value">&#8358;{{ number_format($closing_balance, 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="transactions">
        <thead>
            <tr>
                <th>Date</th>
                <th>Transaction Details</th>
                <th>Ref ID</th>
                <th style="text-align: right;">Amount (&#8358;)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
            @php
            $txDate = null;
            try {
            if (isset($tx->habukhan_date) && !empty($tx->habukhan_date)) {
            $txDate = \Carbon\Carbon::parse($tx->habukhan_date);
            }
            } catch (\Exception $e) {
            $txDate = null;
            }
            @endphp
            <tr>
                <td style="white-space: nowrap;">
                    @if($txDate)
                    {{ $txDate->format('d M Y') }}<br><small>{{ $txDate->format('H:i') }}</small>
                    @else
                    {{ $tx->habukhan_date ?? 'N/A' }}
                    @endif
                </td>
                <td>
                    {{ $tx->message }}
                    <br><small style="color: #777;">Type: {{ strtoupper($tx->role) }} | Ref: {{ $tx->transid }}</small>
                </td>
                <td><small>{{ $tx->transid }}</small></td>
                <td class="amount {{ $tx->amount > 0 ? 'debit' : 'credit' }}">
                    &#8358;{{ number_format(abs($tx->amount), 2) }}
                </td>
            </tr>
            @endforeach
            @if($transactions->isEmpty())
            <tr>
                <td colspan="4" style="text-align: center; padding: 50px; color: #999;">
                    No transactions found for the specified period.
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <div style="margin-top: 50px; font-size: 11px; color: #777; border-top: 1px solid #eee; padding-top: 10px;">
        <p><strong>Note:</strong> All transactions are in Nigerian Naira (&#8358;). If you notice any discrepancy,
            please
            contact our support team immediately.</p>
        <p>Thank you for choosing {{ $app_name }}.</p>
    </div>
</body>

</html>