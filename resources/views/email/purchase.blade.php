@extends('email.layouts.master')

@section('content')
  <div style="text-align: center;">
    <h2 style="color: #28a745; margin-bottom: 5px;">Transaction Successful</h2>
    <p style="font-size: 24px; font-weight: bold; color: #333; margin-top: 0;">
      ₦{{ number_format($amount ?? 0, 2) }}
    </p>
    <div style="margin-bottom: 30px;">
      <span
        style="background-color: #e6fffa; color: #006644; padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold;">SUCCESSFUL</span>
    </div>
  </div>

  <p>Hello <strong>{{ ucfirst($username) }}</strong>,</p>
  <p>Here is the receipt for your recent transaction.</p>

  <table width="100%" cellpadding="10" cellspacing="0" style="border: 1px solid #eee; margin-top: 20px; font-size: 14px;">
    <tr style="background-color: #f9f9f9;">
      <td style="color: #666;">Transaction Type</td>
      <td style="text-align: right; font-weight: bold;">{{ $title ?? 'Debit' }}</td>
    </tr>
    <tr>
      <td style="color: #666;">Description</td>
      <td style="text-align: right;">{{ $mes ?? 'Transaction' }}</td>
    </tr>
    <tr style="background-color: #f9f9f9;">
      <td style="color: #666;">Amount</td>
      <td style="text-align: right;">₦{{ number_format($amount ?? 0, 2) }}</td>
    </tr>
    @if(isset($charges) && $charges > 0)
      <tr>
        <td style="color: #666;">Charges</td>
        <td style="text-align: right; color: #dc3545;">- ₦{{ number_format($charges, 2) }}</td>
      </tr>
    @endif
    <tr style="background-color: #f9f9f9;">
      <td style="color: #666;">Reference ID</td>
      <td style="text-align: right; font-family: monospace;">{{ $transid ?? 'N/A' }}</td>
    </tr>
    <tr>
      <td style="color: #666;">Date & Time</td>
      <td style="text-align: right;">{{ $date ?? date('d M Y, h:i A') }}</td>
    </tr>
  </table>

  @if(isset($newbal))
    <div style="margin-top: 20px; text-align: center; border-top: 1px dashed #ddd; padding-top: 15px;">
      <p style="color: #666; font-size: 12px; margin-bottom: 5px;">New Wallet Balance</p>
      <strong style="font-size: 16px;">₦{{ number_format($newbal, 2) }}</strong>
    </div>
  @endif

  <div style="text-align: center; margin-top: 30px;">
    <a href="{{ config('app.app_url') }}/dashboard" style="color: #0056b3; font-size: 13px;">View Receipt in App</a>
  </div>
@endsection