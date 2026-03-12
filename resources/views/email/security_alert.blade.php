@extends('email.layouts.master')

@section('content')
    <div style="text-align: center;">
        <img src="https://cdni.iconscout.com/illustration/premium/thumb/security-alert-3428238-2902707.png"
            alt="Security Shield" style="max-width: 100px; margin-bottom: 20px;">
        <h2 style="color: #dc3545; margin-bottom: 10px;">Security Alert</h2>
    </div>

    <p>Hello <strong>{{ ucfirst($username) }}</strong>,</p>

    <p>{{ $message_body ?? 'We noticed a recent change to your account security settings.' }}</p>

    <div class="metadata">
        <div class="metadata-item">
            <span class="metadata-label">Activity:</span>
            <span>{{ $title ?? 'Security Event' }}</span>
        </div>
        <div class="metadata-item">
            <span class="metadata-label">IP Address:</span>
            <span>{{ $ip_address ?? 'Unknown' }}</span>
        </div>
        <div class="metadata-item">
            <span class="metadata-label">Date & Time:</span>
            <span>{{ date('d M Y, h:i A') }}</span>
        </div>
        <div class="metadata-item">
            <span class="metadata-label">Device:</span>
            <span>{{ $device ?? 'Unknown Device' }}</span>
        </div>
    </div>

    <p style="margin-top: 25px;">If you initiated this action, no further action is required.</p>

    <div class="alert-warning">
        <strong>Did not authorize this?</strong><br>
        Please contact support immediately or change your password to secure your account.
    </div>

    <div style="text-align: center; margin-top: 25px;">
        <a href="{{ config('app.app_url') }}/contact" class="btn" style="background-color: #dc3545;">Contact Support</a>
    </div>
@endsection