# Production Deployment Guide - Paystack Accounts

**Date**: April 12, 2026  
**Purpose**: Deploy Paystack Dedicated Virtual Accounts to production

---

## 🚀 Deployment Steps

### 1. Pull Latest Code on Production Server

```bash
cd /path/to/app.oyitipay.com
git pull origin main
```

---

### 2. Configure Webhook in Paystack Dashboard

1. Login to [Paystack Dashboard](https://dashboard.paystack.com)
2. Go to **Settings → Webhooks**
3. Add webhook URL: `https://app.oyitipay.com/api/webhook/paystack/dva`
4. Select event: **charge.success**
5. Save webhook

---

### 3. Verify Paystack Keys are Configured

```bash
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$habukhanKey = DB::table('habukhan_key')->first();
if (\$habukhanKey && !empty(\$habukhanKey->psk)) {
    echo '✅ Paystack keys configured\n';
    if (strpos(\$habukhanKey->psk, 'sk_live_') === 0) {
        echo '✅ Using LIVE key\n';
    } else {
        echo '⚠️  Using TEST key\n';
    }
} else {
    echo '❌ Paystack keys NOT configured\n';
}
"
```

If keys are not configured, add them via admin dashboard.

---

### 4. Run Account Creation Script

**IMPORTANT**: This script is SAFE for production:
- ✅ Checks if accounts already exist (won't duplicate)
- ✅ Handles rate limiting (3 second delay)
- ✅ Detailed progress tracking
- ✅ Error recovery
- ✅ Creates log file

```bash
php production_create_paystack_accounts.php
```

**What it does**:
1. Asks for confirmation (type `yes` to proceed)
2. Shows progress for each user
3. Creates Titan account (primary)
4. Creates Wema account (secondary)
5. Updates database
6. Saves log file

**Expected output**:
```
[1/50] Processing: username1 (email@example.com)
-------------------------------------------
  ✅ Customer created: CUS_xxxxx
  Creating Titan account...
  ✅ Titan: 9729821825 (Paystack-Titan)
  Creating Wema account...
  ✅ Wema: 9811592416 (Wema Bank)
  ✅ SUCCESS
  ⏳ Waiting 3 seconds...
```

**Time estimate**: ~6 seconds per user
- 50 users = ~5 minutes
- 100 users = ~10 minutes
- 500 users = ~50 minutes

---

### 5. Verify Accounts Created

```bash
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$titanCount = DB::table('user_bank')->where('bank', 'LIKE', '%TITAN%')->count();
\$wemaCount = DB::table('user_bank')->where('bank', 'LIKE', '%WEMA%')->count();
\$totalUsers = DB::table('user')->where('status', 1)->count();

echo \"Total Active Users: \$totalUsers\n\";
echo \"Titan Accounts: \$titanCount\n\";
echo \"Wema Accounts: \$wemaCount\n\";

if (\$titanCount >= \$totalUsers && \$wemaCount >= \$totalUsers) {
    echo \"\n✅ All users have both accounts!\n\";
} else {
    echo \"\n⚠️  Some users missing accounts\n\";
}
"
```

---

### 6. Test with Real User

1. Login to frontend as a test user
2. Go to "Fund Wallet" section
3. You should see TWO tabs:
   - **PAYSTACK-TITAN** (with account number)
   - **WEMA BANK** (with account number)
4. Transfer ₦100 to Titan account from your bank app
5. Wait 1-2 minutes
6. Check if wallet is credited automatically

---

### 7. Monitor Logs

```bash
# Watch Laravel logs for webhook activity
tail -f storage/logs/laravel.log | grep "Paystack"

# Check account creation log
ls -lh storage/logs/paystack_account_creation_*.log
cat storage/logs/paystack_account_creation_*.log
```

---

## 🔍 Troubleshooting

### Issue: Script fails with "Paystack key not found"
**Solution**: Configure keys in admin dashboard at `/secure/paymentKey`

### Issue: "Rate limit exceeded"
**Solution**: Script already handles this with 3-second delays. If it still happens, increase delay in script.

### Issue: Some users missing accounts
**Solution**: Run script again - it will skip users who already have accounts and only create missing ones.

### Issue: Webhook not working
**Solution**: 
1. Check webhook URL in Paystack dashboard
2. Verify `charge.success` event is enabled
3. Check Laravel logs for webhook errors
4. Test with manual requery: `POST /api/paystack/requery/account`

---

## 📊 Database Changes

The script modifies these tables:

### `user` table
- Updates `paystack_account` (Titan account number)
- Updates `paystack_bank` (Paystack-Titan)

### `user_bank` table
- Inserts Titan account record
- Inserts Wema account record

### `settings` table
- Already has `paystack_preferred_bank` = 'titan-paystack'

---

## ✅ Success Criteria

After deployment, verify:
- [ ] All users have Titan accounts
- [ ] All users have Wema accounts
- [ ] Frontend shows both tabs correctly
- [ ] Webhook URL configured in Paystack
- [ ] Test transfer credits wallet automatically
- [ ] Logs show no errors

---

## 🔄 Rollback Plan

If something goes wrong:

```bash
# Rollback code
git reset --hard HEAD~1
git push origin main --force

# Clear Paystack accounts (if needed)
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

DB::table('user')->update([
    'paystack_account' => null,
    'paystack_bank' => null
]);

DB::table('user_bank')
    ->whereIn('bank', ['PAYSTACK-TITAN', 'WEMA BANK'])
    ->delete();

echo 'Paystack accounts cleared\n';
"
```

---

## 📞 Support

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check account creation log: `storage/logs/paystack_account_creation_*.log`
3. Verify Paystack dashboard for API errors
4. Test API manually with Postman

---

## 🎯 Post-Deployment

After successful deployment:
1. Monitor webhook activity for 24 hours
2. Check user feedback
3. Verify all transactions are processing
4. Update documentation if needed

---

**✅ Ready for production deployment!**
