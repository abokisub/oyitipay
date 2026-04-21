#!/bin/bash

# Quick Autopilot Webhook Test Script
# Run this in cPanel Terminal or SSH

echo ""
echo "🚀 AUTOPILOT WEBHOOK QUICK TEST"
echo "================================"
echo ""

# Test 1: Check if URL responds
echo "1️⃣  Testing webhook URL..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://app.oyitipay.com/api/autopilot/webhook/secure)

if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "405" ]; then
    echo "   ✅ Webhook URL is accessible (HTTP $HTTP_CODE)"
elif [ "$HTTP_CODE" == "404" ]; then
    echo "   ❌ Webhook URL returns 404 - Route not configured!"
else
    echo "   ⚠️  Webhook URL returns HTTP $HTTP_CODE"
fi

echo ""

# Test 2: Send test webhook
echo "2️⃣  Sending test webhook..."
RESPONSE=$(curl -s -X POST https://app.oyitipay.com/api/autopilot/webhook/secure \
  -H "Content-Type: application/json" \
  -d '{"status":"success","data":{"reference":"TEST_'$(date +%s)'","product":"data","amount":100}}')

echo "   Response: $RESPONSE"

if [[ "$RESPONSE" == *"success"* ]] || [[ "$RESPONSE" == *"ignored"* ]]; then
    echo "   ✅ Webhook is responding correctly"
else
    echo "   ⚠️  Unexpected response"
fi

echo ""

# Test 3: Check recent logs
echo "3️⃣  Checking recent webhook logs..."
if [ -f "storage/logs/laravel.log" ]; then
    LOGS=$(tail -n 50 storage/logs/laravel.log | grep -i "autopilot" | tail -n 3)
    
    if [ -z "$LOGS" ]; then
        echo "   ⚠️  No recent Autopilot webhook logs found"
        echo "   This means webhook hasn't been called recently"
    else
        echo "   ✅ Found recent webhook activity:"
        echo "$LOGS" | while read line; do
            echo "   $line"
        done
    fi
else
    echo "   ❌ Log file not found"
fi

echo ""
echo "================================"
echo "✅ Test complete!"
echo ""
echo "Next steps:"
echo "• Check Autopilot dashboard webhook logs"
echo "• Make a test transaction"
echo "• Monitor logs: tail -f storage/logs/laravel.log | grep Autopilot"
echo ""
