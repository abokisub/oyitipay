# Autopilot Webhook Verification Guide

**Webhook URL**: `https://app.oyitipay.com/api/autopilot/webhook/secure`

---

## 🎯 Quick Verification Steps

### Method 1: Using cPanel Terminal (Recommended)

1. **Login to cPanel**
2. **Open Terminal** (or SSH)
3. **Navigate to your app directory**:
   ```bash
   cd /home/xzmzrphi/app.oyitipay.com
   ```

4. **Run the verification script**:
   ```bash
   php check_autopilot_webhook_simple.php
   ```

This will:
- ✅ Test if webhook URL is accessible
- ✅ Check recent webhook logs
- ✅ Send a test webhook request
- ✅ Show you if it's working

---

### Method 2: Check Laravel Logs Manually

1. **Via cPanel File Manager**:
   - Navigate to: `storage/logs/laravel.log`
   - Search for: `Autopilot Webhook`
   - Look for recent entries

2. **Via SSH/Terminal**:
   ```bash
   cd /home/xzmzrphi/app.oyitipay.com
   tail -f storage/logs/laravel.log | grep "Autopilot"
   ```

3. **What to look for**:
   ```
   [2026-04-16 10:30:45] local.INFO: Autopilot Webhook received: {"status":"success","data":{"reference":"ABC123"}}
   ```

---

### Method 3: Check Autopilot Dashboard

1. **Login to Autopilot Dashboard**
2. **Go to Settings → Webhooks**
3. **Check webhook logs/history**
4. **Look for**:
   - ✅ Successful deliveries (200 OK)
   - ❌ Failed deliveries (404, 500 errors)
   - ⏳ Pending/retry attempts

---

## 🔍 What the Webhook Does

Your Autopilot webhook handles transaction status updates:

### Expected Payload Format:
```json
{
  "status": "success",  // or "fail"
  "data": {
    "reference": "TXN123456",
    "product": "data",  // or "airtime", "cable", etc.
    "amount": 100,
    "phone": "08012345678"
  }
}
```

### What Happens:
1. **Success**: Updates transaction status to `plan_status = 1`
2. **Fail**: Updates to `plan_status = 2` and refunds user

### Database Tables Checked:
- `data` - Data bundle transactions
- `airtime` - Airtime purchases
- `cable` - Cable TV subscriptions
- `cash` - Cash transactions
- `message` - Transaction history

---

## ✅ Verification Checklist

### 1. Route Configuration
Check `routes/api.php` has:
```php
Route::any('autopilot/webhook/secure', [WebhookController::class, 'AutopilotWebhook']);
```

### 2. Controller Exists
File: `app/Http/Controllers/Webhooks/AutopilotWebhook.php`
- ✅ Should exist
- ✅ Should have `Handle()` method
- ✅ Should log incoming requests

### 3. Database Schema
Tables should have `api_reference` column:
```sql
-- Check if column exists
SHOW COLUMNS FROM data LIKE 'api_reference';
```

If missing, add it:
```sql
ALTER TABLE data ADD COLUMN api_reference VARCHAR(255) NULL;
ALTER TABLE airtime ADD COLUMN api_reference VARCHAR(255) NULL;
ALTER TABLE cable ADD COLUMN api_reference VARCHAR(255) NULL;
```

### 4. Webhook URL in Autopilot
- ✅ URL: `https://app.oyitipay.com/api/autopilot/webhook/secure`
- ✅ Method: POST
- ✅ Content-Type: application/json

---

## 🧪 Testing the Webhook

### Test 1: Manual cURL Test
```bash
curl -X POST https://app.oyitipay.com/api/autopilot/webhook/secure \
  -H "Content-Type: application/json" \
  -d '{
    "status": "success",
    "data": {
      "reference": "TEST123",
      "product": "data",
      "amount": 100
    }
  }'
```

**Expected Response**:
```json
{"status":"ignored"}  // If reference not found (normal for test)
```
or
```json
{"status":"success"}  // If reference exists
```

### Test 2: Real Transaction Test
1. Make a small purchase (₦50 airtime)
2. Note the transaction reference
3. Check if Autopilot calls your webhook
4. Monitor logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "Autopilot"
   ```

---

## 🐛 Troubleshooting

### Issue 1: Webhook Returns 404
**Problem**: Route not found

**Solution**:
1. Check `routes/api.php` has the route
2. Clear route cache:
   ```bash
   php artisan route:clear
   php artisan cache:clear
   ```

### Issue 2: No Logs Appearing
**Problem**: Webhook not being called

**Solution**:
1. Check Autopilot dashboard webhook configuration
2. Verify URL is correct (no typos)
3. Check if Autopilot IP is blocked by firewall
4. Verify SSL certificate is valid

### Issue 3: Webhook Called But Not Working
**Problem**: Transactions not updating

**Solution**:
1. Check if `api_reference` column exists in tables
2. Verify transaction reference matches
3. Check Laravel logs for errors:
   ```bash
   tail -n 100 storage/logs/laravel.log
   ```

### Issue 4: 500 Server Error
**Problem**: PHP error in webhook code

**Solution**:
1. Check Laravel logs for stack trace
2. Check cPanel error logs
3. Verify database connection
4. Check if all required columns exist

---

## 📊 Monitoring Webhook Activity

### Real-time Monitoring
```bash
# Watch all webhook activity
tail -f storage/logs/laravel.log | grep "Autopilot"

# Watch all API activity
tail -f storage/logs/laravel.log | grep "Webhook"
```

### Check Recent Activity
```bash
# Last 50 webhook calls
grep "Autopilot Webhook" storage/logs/laravel.log | tail -n 50

# Count webhook calls today
grep "Autopilot Webhook" storage/logs/laravel.log | grep "$(date +%Y-%m-%d)" | wc -l
```

### Database Query
```sql
-- Check transactions with api_reference
SELECT * FROM data 
WHERE api_reference IS NOT NULL 
ORDER BY id DESC 
LIMIT 10;

-- Check pending transactions
SELECT * FROM data 
WHERE plan_status = 0 
AND api_reference IS NOT NULL;
```

---

## 🔐 Security Considerations

### 1. IP Whitelisting (Optional)
Add Autopilot's IP to allowed list in `.htaccess` or firewall

### 2. Signature Verification (Recommended)
Add webhook signature verification:
```php
$signature = $request->header('X-Autopilot-Signature');
$secret = config('services.autopilot.webhook_secret');

if (!hash_equals($signature, hash_hmac('sha256', $request->getContent(), $secret))) {
    return response()->json(['error' => 'Invalid signature'], 401);
}
```

### 3. Rate Limiting
Already handled by Laravel's default rate limiting

---

## 📝 Expected Behavior

### Successful Transaction Flow:
1. User makes purchase → Transaction created with `plan_status = 0`
2. Autopilot processes → Sends webhook with `status = "success"`
3. Webhook updates → `plan_status = 1`
4. User sees success message

### Failed Transaction Flow:
1. User makes purchase → Transaction created with `plan_status = 0`
2. Autopilot fails → Sends webhook with `status = "fail"`
3. Webhook updates → `plan_status = 2`
4. User refunded → Balance restored
5. User sees failure message

---

## 🎯 Success Indicators

Your webhook is working if:
- ✅ Autopilot dashboard shows 200 OK responses
- ✅ Laravel logs show "Autopilot Webhook received"
- ✅ Transactions update from `plan_status = 0` to `1` or `2`
- ✅ Users receive success/failure notifications
- ✅ Failed transactions are refunded automatically

---

## 📞 Need Help?

If webhook still not working:

1. **Share these details**:
   - Output of `check_autopilot_webhook_simple.php`
   - Last 20 lines from `storage/logs/laravel.log`
   - Autopilot dashboard webhook logs
   - Any error messages from cPanel

2. **Check these files**:
   - `routes/api.php` (line ~410)
   - `app/Http/Controllers/Webhooks/AutopilotWebhook.php`
   - `storage/logs/laravel.log`

3. **Run diagnostics**:
   ```bash
   php test_autopilot_webhook.php
   ```

---

**Last Updated**: April 16, 2026  
**Webhook URL**: https://app.oyitipay.com/api/autopilot/webhook/secure  
**Status**: Ready for verification ✅
