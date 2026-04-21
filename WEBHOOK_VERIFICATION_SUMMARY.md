# Autopilot Webhook Verification - Quick Start

**Your Webhook URL**: `https://app.oyitipay.com/api/autopilot/webhook/secure`

---

## ✅ 3 Ways to Verify Your Webhook

### Option 1: Quick Shell Script (Fastest)
```bash
cd /home/xzmzrphi/app.oyitipay.com
chmod +x quick_webhook_test.sh
./quick_webhook_test.sh
```

### Option 2: PHP Script (Most Detailed)
```bash
cd /home/xzmzrphi/app.oyitipay.com
php check_autopilot_webhook_simple.php
```

### Option 3: Manual cURL Test
```bash
curl -X POST https://app.oyitipay.com/api/autopilot/webhook/secure \
  -H "Content-Type: application/json" \
  -d '{"status":"success","data":{"reference":"TEST123","product":"data"}}'
```

**Expected Response**: `{"status":"ignored"}` or `{"status":"success"}`

---

## 🔍 What to Check in Autopilot Dashboard

1. **Login to Autopilot Dashboard**
2. **Navigate to**: Settings → Webhooks (or API Settings)
3. **Verify**:
   - ✅ Webhook URL: `https://app.oyitipay.com/api/autopilot/webhook/secure`
   - ✅ Method: POST
   - ✅ Status: Active/Enabled
4. **Check Logs**:
   - Look for recent webhook deliveries
   - Check for 200 OK responses (success)
   - Check for 404/500 errors (problems)

---

## 📊 How to Monitor Webhook Activity

### Real-time Monitoring (via SSH/Terminal)
```bash
cd /home/xzmzrphi/app.oyitipay.com
tail -f storage/logs/laravel.log | grep "Autopilot"
```

### Check Recent Activity
```bash
# Last 20 webhook calls
grep "Autopilot Webhook" storage/logs/laravel.log | tail -n 20

# Count today's webhooks
grep "Autopilot Webhook" storage/logs/laravel.log | grep "$(date +%Y-%m-%d)" | wc -l
```

### Via cPanel File Manager
1. Navigate to: `storage/logs/laravel.log`
2. Search for: `Autopilot Webhook`
3. Look for entries like:
   ```
   [2026-04-16 10:30:45] local.INFO: Autopilot Webhook received: {"status":"success"...}
   ```

---

## 🧪 Test with Real Transaction

1. **Make a small test purchase** (e.g., ₦50 airtime)
2. **Note the transaction reference**
3. **Watch the logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep "Autopilot"
   ```
4. **Check Autopilot dashboard** for webhook delivery
5. **Verify transaction status** updated in your database

---

## ✅ Success Indicators

Your webhook is working correctly if you see:

- ✅ **Autopilot Dashboard**: Shows 200 OK responses
- ✅ **Laravel Logs**: Shows "Autopilot Webhook received" entries
- ✅ **Database**: Transactions update from `plan_status = 0` to `1` (success) or `2` (failed)
- ✅ **Users**: Receive success/failure notifications
- ✅ **Refunds**: Failed transactions automatically refund users

---

## 🐛 Common Issues & Fixes

### Issue: Webhook returns 404
**Fix**: Route not configured. Check `routes/api.php` line ~410

### Issue: No logs appearing
**Fix**: Webhook not being called. Check Autopilot dashboard configuration

### Issue: Transactions not updating
**Fix**: Check if `api_reference` column exists in database tables

### Issue: 500 Server Error
**Fix**: Check `storage/logs/laravel.log` for PHP errors

---

## 📁 Files Created for You

1. **AUTOPILOT_WEBHOOK_VERIFICATION_GUIDE.md** - Complete documentation
2. **check_autopilot_webhook_simple.php** - PHP verification script
3. **test_autopilot_webhook.php** - Detailed PHP test script
4. **quick_webhook_test.sh** - Quick bash test script
5. **WEBHOOK_VERIFICATION_SUMMARY.md** - This file

---

## 🎯 Next Steps

1. **Run one of the verification scripts** (Option 1, 2, or 3 above)
2. **Check Autopilot dashboard** webhook logs
3. **Make a test transaction** to verify end-to-end
4. **Monitor logs** for any errors

---

## 📞 Need Help?

If webhook still not working, share:
- Output from verification script
- Last 20 lines from `storage/logs/laravel.log`
- Screenshot of Autopilot dashboard webhook logs
- Any error messages

---

**Quick Command Reference**:
```bash
# Navigate to app
cd /home/xzmzrphi/app.oyitipay.com

# Run quick test
./quick_webhook_test.sh

# Or run PHP test
php check_autopilot_webhook_simple.php

# Monitor logs
tail -f storage/logs/laravel.log | grep Autopilot

# Test webhook manually
curl -X POST https://app.oyitipay.com/api/autopilot/webhook/secure \
  -H "Content-Type: application/json" \
  -d '{"status":"success","data":{"reference":"TEST","product":"data"}}'
```

---

**Status**: Ready to verify ✅  
**Date**: April 16, 2026
