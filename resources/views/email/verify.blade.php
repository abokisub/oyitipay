@extends('email.layouts.master')

@section('content')
    <div style="text-align: center;">
        <h2 style="color: #333; margin-bottom: 20px;">{{ $title ?? 'Verification Code' }}</h2>

        <p style="font-size: 16px; margin-bottom: 30px;">
            Hello <strong>{{ ucfirst($username) }}</strong>,<br>
            To complete your request, please use the One-Time Password (OTP) below.
        </p>

        <div
            style="background-color: #f0f4f8; padding: 20px; border-radius: 8px; display: inline-block; margin-bottom: 30px; letter-spacing: 5px; font-weight: bold; font-size: 32px; color: #0056b3;">
            {{ $otp }}
        </div>

        <div class="alert-warning" style="text-align: left;">
            <strong>Warning:</strong> If you did not initiate this request, please ignore this email or contact support
            immediately. Do not share this code with anyone, including our support staff.
        </div>

        <div class="metadata" style="text-align: left;">
            <div class="metadata-item">
                <span class="metadata-label">IP Address:</span>
                <span>{{ $ip_address ?? 'Unknown' }}</span>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Device:</span>
                <span>{{ $device ?? 'Unknown Device' }}</span>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Location:</span>
                <span>{{ $location ?? 'Unknown Location' }}</span>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Time:</span>
                <span>{{ date('d M Y, h:i A') }}</span>
            </div>
        </div>
    </div>
@endsection