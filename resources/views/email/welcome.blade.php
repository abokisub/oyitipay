@extends('email.layouts.master')

@section('content')
    <div style="text-align: center;">
        <h1 style="color: #0056b3; margin-bottom: 10px;">Welcome to {{ config('app.name') }}!</h1>
        <p style="font-size: 18px; color: #555;">We're thrilled to have you on board.</p>
    </div>

    <p>Hello <strong>{{ $username }}</strong>,</p>

    <p>Your account has been successfully created. You can now access a world of seamless financial services right at your
        fingertips.</p>

    <div style="background-color: #f8f9fa; border-left: 4px solid #0056b3; padding: 15px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #0056b3;">Your Login Credentials</h3>
        <p style="margin-bottom: 5px;"><strong>Username:</strong> {{ $username }}</p>
        <p style="margin-bottom: 0;"><strong>Transaction PIN:</strong> {{ $pin }}</p>
        <small style="display: block; margin-top: 10px; color: #dc3545;">*Please change your PIN immediately after logging
            in for security.</small>
    </div>

    <h3 style="color: #333;">What you can do with {{ config('app.name') }}:</h3>
    <ul style="line-height: 1.8; color: #444;">
        <li>⚡ <strong>Instant Transfers:</strong> Send money to any bank account instantly.</li>
        <li>💡 <strong>Pay Bills:</strong> Airtime, Data, Electricity, and TV subscriptions.</li>
        <li>🔒 <strong>Secure Wallet:</strong> Your funds are safe and insured.</li>
        <li>🎁 <strong>Earn Rewards:</strong> Refer friends and earn bonuses.</li>
    </ul>

    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.app_url') }}" class="btn">Login to Dashboard</a>
    </div>
@endsection