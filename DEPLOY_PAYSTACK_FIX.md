# Deploy Paystack Virtual Accounts Fix

**Date**: April 12, 2026  
**Issue**: Mobile app showing wrong account numbers for Paystack banks  
**Fix**: Updated provider detection logic in getUserVirtualAccounts()

---

## 🔍 Problem

Mobile app was showing:
- Only "WEMA BANK" tab
- But displaying Titan account number (not Wema number)
- Missing "PAYSTACK-TITAN" tab

**Root Cause**: Provider detection logic in `getUserVirtualAccounts()` was not properly differentiating between Titan and Wema accounts.

---

## ✅ Solution

Updated `app/Http/Controllers/API/AuthController.php`:
- Separated TITAN and WEMA detection logic
- Ensured consistent bank naming
- Fixed provider assignment

**Commit**: `7454f89` - "Fix: Correct provider detection in getUserVirtualAccounts for Paystack Titan and Wema Bank"

---

## 🚀 Deployment Steps

### Step 1: Pull Latest Code on Production

```bash
ssh xzmzrphi@sbg106
cd /home/xzmzrphi/app.oyitipay.com
git pull origin main
```

Expected output:
```
From https://github.com/abokisub/oyitipay
   90f57db..7454f89  main       -> origin/main
Updating 90f57db..7454f89
Fast-forward
 app/Http/Controllers/API/AuthController.php | 17 +++++++++--------
 1 file changed, 17 insertions(+), 8 deletions(-)
```

### Step 2: Verify the Fix

Run diagnostic script to check database:
```bash
php check_paystack_accounts.php
```

This will show:
- All Paystack accounts in database
- How they're being detected (Titan vs Wema)
- What the API will return to mobile app

### Step 3: Test Login API

Test with your account:
```bash
curl -s -X POST https://app.oyitipay.com/api/login/verify/user \
  -H "Content-Type: application/json" \
  -d '{"username":"Habukhan","password":"Habukhan@Habukhan12"}' \
  | python3 -m json.tool | grep -A 30 "meta_data"
```

Expected output should show:
```json
"meta_data": {
    "virtual_accounts": [
        {
            "provider": "titan",
            "bank_name": "PAYSTACK-TITAN",
            "account_number": "1234567890",
            "account_name": "HABUKHAN ACCOUNT"
        },
        {
            "provider": "wema",
            "bank_name": "WEMA BANK",
            "account_number": "0987654321",
            "account_name": "HABUKHAN ACCOUNT"
        }
    ]
}
```

### Step 4: Test Mobile App

1. **Logout** from mobile app
2. **Login** again (this fetches fresh data)
3. Check dashboard - should now show:
   - Default account (based on settings)
   - Correct account number for that bank

---

## 🔍 Troubleshooting

### If accounts still show wrong numbers:

1. **Check database directly**:
```bash
php check_paystack_accounts.php
```

Look for:
- Are there 2 accounts per user (Titan + Wema)?
- Are the bank names correct in `user_bank` table?
- Are the account numbers different?

2. **Check what API returns**:
```bash
# Test login endpoint
curl -X POST https://app.oyitipay.com/api/login/verify/user \
  -H "Content-Type: application/json" \
  -d '{"username":"Habukhan","password":"Habukhan@Habukhan12"}' \
  | python3 -m json.tool > login_response.json

# Check meta_data.virtual_accounts
cat login_response.json | grep -A 20 "virtual_accounts"
```

3. **Check mobile app behavior**:
- The mobile app currently shows only ONE account (the default)
- It does NOT show multiple tabs
- To show both accounts, mobile app needs UI update

---

## 📱 Mobile App Behavior

**Current**: Mobile app shows only the DEFAULT account
- Uses `account_number` and `bank_name` from top-level response
- Falls back to `meta_data.virtual_accounts` if empty

**To show BOTH accounts**: Mobile app needs update to:
1. Read `meta_data.virtual_accounts` array
2. Display tabs or carousel for multiple accounts
3. Let user switch between Titan and Wema

**No app update needed for now** - backend fix ensures correct data is returned.

---

## 🎯 Expected Result

After deployment:
- ✅ API returns correct account numbers for each bank
- ✅ Titan account has provider='titan', bank_name='PAYSTACK-TITAN'
- ✅ Wema account has provider='wema', bank_name='WEMA BANK'
- ✅ Mobile app shows correct default account
- ⏳ Mobile app still shows only ONE account (UI limitation)

---

## 📝 Next Steps (Optional)

If you want mobile app to show BOTH accounts:
1. Update `dashboard_screen.dart` to read `meta_data.virtual_accounts`
2. Add tabs or carousel to switch between accounts
3. Let user select which account to display

This requires mobile app update and resubmission to Play Store.

---

## 🆘 Support

If issues persist:
1. Run `check_paystack_accounts.php` and share output
2. Test login API and share `meta_data.virtual_accounts` section
3. Check mobile app logs for errors

**Files to check**:
- `app/Http/Controllers/API/AuthController.php` (line 1349-1428)
- `app/Http/Controllers/API/Banks.php` (line 1-300)
- Database: `user_bank` table
