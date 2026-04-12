# Paystack Dedicated Virtual Account Implementation

**Date**: April 12, 2026  
**Feature**: Automatic Wema Bank Virtual Account Creation & Webhook Integration

---

## 🎯 Overview

Implemented Paystack Dedicated Virtual Account (DVA) system that automatically:
1. Creates unique Wema Bank accounts for users on registration/login
2. Automatically credits user wallets when they transfer money to their account
3. Handles webhook notifications from Paystack

---

## ✅ What Was Implemented

### 1. Virtual Account Creation Function
**File**: `app/Http/Controllers/Controller.php`

**Function**: `paystack_account($username)`

**Features**:
- ✅ Checks multiple sources for Paystack secret key:
  - `paystack_key` table (primary - where admin saves keys)
  - `habukhan_key` table (fallback)
  - Config file (final fallback)
- ✅ Creates Paystack customer if doesn't exist
- ✅ Fetches existing customer if already created
- ✅ Creates dedicated Wema Bank virtual account
- ✅ Saves account details to `user` table (`paystack_account`, `paystack_bank`)
- ✅ Saves to `user_bank` table for display in app
- ✅ Comprehensive error logging

**API Flow**:
```
1. Create/Get Customer → POST https://api.paystack.co/customer
2. Create DVA → POST https://api.paystack.co/dedicated_account
   - preferred_bank: "wema-bank"
3. Save account number to database
```

---

### 2. Webhook Handler for Automatic Funding
**File**: `app/Http/Controllers/API/WebhookController.php`

**Function**: `paystackDedicatedAccountWebhook(Request $request)`

**Features**:
- ✅ Verifies Paystack signature for security
- ✅ Handles `charge.success` event
- ✅ Filters for `dedicated_nuban` channel only
- ✅ Finds user by account number
- ✅ Prevents duplicate transactions (idempotency)
- ✅ Applies platform charges if configured
- ✅ Credits user wallet automatically
- ✅ Records deposit in `deposit` table
- ✅ Records transaction in `message` table
- ✅ Sends push notification to user
- ✅ Comprehensive logging

**Webhook URL**: `https://your-domain.com/api/webhook/paystack/dva`

---

### 3. Route Configuration
**File**: `routes/api.php`

**Added Route**:
```php
Route::post('/webhook/paystack/dva', [WebhookController::class, 'paystackDedicatedAccountWebhook']);
```

---

## 🔧 Configuration Required

### 1. Add Paystack Keys in Admin Dashboard
Navigate to: **Admin Dashboard → Payment Keys**

Add your Paystack keys:
- **Public Key**: `pk_live_xxxxx`
- **Secret Key**: `sk_live_xxxxx`

Keys are stored in `habukhan_key` table with columns:
- `psk` - Secret key
- `plive` - Public key
- `psk_bvn` - BVN (optional)

---

### 2. Configure Paystack Webhook in Paystack Dashboard

1. Login to [Paystack Dashboard](https://dashboard.paystack.com)
2. Go to **Settings → Webhooks**
3. Add webhook URL: `https://app.oyitipay.com/api/webhook/paystack/dva`
4. Select events to listen for:
   - ✅ `charge.success` (required)
5. Save webhook

---

### 3. Optional: Configure Platform Charges

In `settings` table, you can set:
- `paystack_charge` - Flat charge in Naira (e.g., 20 for ₦20 charge)

Example:
```sql
UPDATE settings SET paystack_charge = 20 WHERE id = 1;
```

If not set, defaults to ₦0 (no charge).

---

## 🔄 Manual Requery Feature

If a webhook is delayed or fails, users can manually trigger a requery to check for pending transactions.

### API Endpoint
```
POST /api/paystack/requery/account
Authorization: Bearer {user_token}
Content-Type: application/json

{
  "date": "2026-04-12"  // Optional: specific date in YYYY-MM-DD format
}
```

### Response
```json
{
  "status": "success",
  "message": "Requery initiated. If you have pending transfers, they will be processed shortly.",
  "account_number": "9324761204",
  "bank": "Wema Bank"
}
```

### Usage
- User transfers money to their Wema Bank account
- If wallet is not credited after 5 minutes, user can click "Requery" button in app
- System checks Paystack for pending transactions
- If found, webhook will be triggered automatically

---

## 📊 Database Tables Used

### User Table
```sql
- paystack_account (VARCHAR) - Wema Bank account number
- paystack_bank (VARCHAR) - Bank name (Wema Bank)
```

### User Bank Table
```sql
- username
- account_number - Wema Bank account number
- account_name - Account name from Paystack
- bank - "WEMA BANK"
- provider - "paystack"
```

### Deposit Table
```sql
- username
- amount - Deposit amount
- oldbal - Balance before deposit
- newbal - Balance after deposit
- type - "Paystack Funding"
- credit_by - "Paystack"
- charges - Platform charge applied
- monify_ref - Paystack reference (for idempotency)
- status - 1 (success)
```

---

## 🧪 Testing

### Test Account Creation

1. **Register a new user** or **login existing user**
2. Check logs: `storage/logs/laravel.log`
3. Look for:
   ```
   Paystack: Creating dedicated virtual account for [username]
   Paystack: Customer code for [username]: CUS_xxxxx
   Paystack: Virtual account created for [username]
   ```
4. Verify in database:
   ```sql
   SELECT username, paystack_account, paystack_bank FROM user WHERE username = 'testuser';
   ```

### Test Webhook (Manual)

Use Postman or curl to simulate Paystack webhook:

```bash
curl -X POST https://app.oyitipay.com/api/webhook/paystack/dva \
  -H "Content-Type: application/json" \
  -H "x-paystack-signature: YOUR_SIGNATURE" \
  -d '{
    "event": "charge.success",
    "data": {
      "channel": "dedicated_nuban",
      "amount": 100000,
      "reference": "test_ref_123",
      "authorization": {
        "receiver_bank_account_number": "9930000737"
      },
      "customer": {
        "email": "user@example.com"
      }
    }
  }'
```

### Test Real Transfer

1. Get user's Wema Bank account number from app
2. Transfer money to that account from any bank
3. Wait 1-2 minutes for Paystack webhook
4. Check user wallet - should be credited automatically
5. Check logs for webhook processing

---

## 🔍 Troubleshooting

### Issue: "Paystack: Secret key not configured"

**Solution**: 
- Ensure Paystack keys are added in admin dashboard
- Check `paystack_key` table has valid keys
- Keys should not be placeholder values (`sk_test_placeholder`)

### Issue: "User not found for account"

**Solution**:
- Verify account number in webhook matches user's `paystack_account`
- Check if account was created successfully during registration

### Issue: Webhook not receiving events

**Solution**:
- Verify webhook URL in Paystack dashboard
- Ensure URL is publicly accessible (not localhost)
- Check Paystack dashboard webhook logs for delivery status
- Verify `charge.success` event is enabled

### Issue: Duplicate transactions

**Solution**:
- System already handles this via `monify_ref` check
- Each Paystack reference is processed only once

---

## 📝 Webhook Event Structure

Paystack sends this payload for dedicated account transfers:

```json
{
  "event": "charge.success",
  "data": {
    "id": 1234567,
    "domain": "live",
    "status": "success",
    "reference": "abc123xyz",
    "amount": 100000,
    "message": null,
    "gateway_response": "Approved",
    "paid_at": "2026-04-12T10:30:00.000Z",
    "created_at": "2026-04-12T10:30:00.000Z",
    "channel": "dedicated_nuban",
    "currency": "NGN",
    "ip_address": null,
    "metadata": {},
    "fees": 0,
    "customer": {
      "id": 7454289,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "customer_code": "CUS_xxxxx",
      "phone": "+2348012345678"
    },
    "authorization": {
      "authorization_code": "AUTH_xxxxx",
      "bin": null,
      "last4": null,
      "exp_month": null,
      "exp_year": null,
      "channel": "dedicated_nuban",
      "card_type": null,
      "bank": "Wema Bank",
      "country_code": "NG",
      "brand": null,
      "reusable": false,
      "signature": null,
      "account_name": "OYITIPAY / JOHN DOE",
      "receiver_bank_account_number": "9930000737",
      "receiver_bank": "Wema Bank"
    }
  }
}
```

---

## 🎯 Key Points

1. ✅ Account creation happens automatically on registration/login
2. ✅ Each user gets a unique Wema Bank account number
3. ✅ Webhook automatically credits wallet when user transfers money
4. ✅ System prevents duplicate transactions
5. ✅ Platform charges can be configured
6. ✅ All transactions are logged and notifications sent
7. ✅ Signature verification ensures security

---

## 🚀 Deployment Checklist

- [ ] Paystack live keys added in admin dashboard
- [ ] Webhook URL configured in Paystack dashboard
- [ ] `charge.success` event enabled in Paystack
- [ ] Test account creation with new user registration
- [ ] Test real transfer to verify webhook works
- [ ] Monitor logs for any errors
- [ ] Verify user receives notification on funding

---

**🎉 Implementation Complete! Users can now fund their wallets automatically via Wema Bank transfers.**
