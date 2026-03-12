<?php

use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\AdminTrans;
use App\Http\Controllers\API\AppController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\NewStock;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\PlanController;
use App\Http\Controllers\API\SecureController;
use App\Http\Controllers\API\Selection;
use App\Http\Controllers\API\Trans;
use App\Http\Controllers\API\TransactionCalculator;
use App\Http\Controllers\API\WebhookController;
use App\Http\Controllers\Purchase\AccessUser;
use App\Http\Controllers\Purchase\AirtimeCash;
use App\Http\Controllers\Purchase\AirtimePurchase;
use App\Http\Controllers\Purchase\BillPurchase;
use App\Http\Controllers\Purchase\BonusTransfer;
use App\Http\Controllers\Purchase\BulksmsPurchase;
use App\Http\Controllers\Purchase\CablePurchase;
use App\Http\Controllers\Purchase\DataPurchase;
use App\Http\Controllers\Purchase\ExamPurchase;
use App\Http\Controllers\Purchase\IUCvad;
use App\Http\Controllers\Purchase\MeterVerify;
use App\Http\Controllers\API\CharityController;
use App\Http\Controllers\APP\Auth;
use App\Http\Controllers\Purchase\DataCard;
use App\Http\Controllers\Purchase\RechargeCard;
use App\Http\Controllers\Purchase\TransferPurchase; // New Import
use App\Http\Controllers\API\Banks;
use App\Http\Controllers\API\AccountVerification;
use App\Http\Controllers\API\VirtualCardController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\NotificationHistoryController;


use App\Http\Controllers\API\SupportController;

Route::get('account/my-account/{id}', [AuthController::class , 'account']);
Route::post('register', [AuthController::class , 'register']);
Route::post('verify/user/account', [AuthController::class , 'verify']);
Route::post('create-pin', [AuthController::class , 'createPin']);
Route::get('website/app/setting', [AppController::class , 'system']);
Route::post('login/verify/user', [AuthController::class , 'login']);
Route::post('email-receipt', [AppController::class , 'emailReceipt']);

// Support & AI Bot Routes
Route::post('chat/aboki', [SupportController::class , 'chatAboki'])->middleware('auth.token');
Route::post('support/tickets/create', [SupportController::class , 'createTicket'])->middleware('auth.token');
Route::post('support/chat/{ticketId}/send/user', [SupportController::class , 'sendUserMessage'])->middleware('auth.token');
Route::post('support/typing', [SupportController::class , 'typing'])->middleware('auth.token');
Route::get('support/chat/{ticketId}/messages/user', [SupportController::class , 'getChatMessages']);
Route::get('support/tickets', [SupportController::class , 'getTickets'])->middleware('auth.token');
Route::get('secure/info', [AppController::class , 'getAppInfo']);

// Admin Support Routes
Route::get('admin/support/open-tickets', [SupportController::class , 'adminGetOpenTickets'])->middleware('auth.token');
Route::post('admin/support/chat/{ticketId}/reply', [SupportController::class , 'adminReply'])->middleware('auth.token');
Route::post('admin/support/ticket/{ticketId}/close', [SupportController::class , 'adminCloseTicket'])->middleware('auth.token');

// KYC Verification (Phase 2)
Route::post('user/kyc/verify', [AuthController::class , 'verifyKyc'])->middleware(['auth.token', 'system.lock:kyc']);
Route::get('user/kyc/details', [AuthController::class , 'getKycDetails'])->middleware('auth.token');

// Smart KYC Flow (Customer Creation)
Route::get('user/kyc/check', [App\Http\Controllers\API\KYCController::class , 'checkKycStatus'])->middleware('auth.token');
Route::post('user/kyc/submit', [App\Http\Controllers\API\KYCController::class , 'submitKyc'])->middleware('auth.token');

// Profile Limits & Statement
Route::get('profile/limits', [ProfileController::class , 'getLimits'])->middleware('auth.token');
Route::post('profile/statement', [ProfileController::class , 'generateStatement'])->middleware('auth.token');
Route::post('profile/update-theme', [ProfileController::class , 'updateTheme'])->middleware('auth.token');

// Customer Creation (Phase 3)
Route::post('/user/customer/create', [AuthController::class , 'createCustomer'])->middleware(['auth.token']);
// Customer Update (Phase 3 Extra)
Route::post('/user/customer/update', [AuthController::class , 'updateCustomer'])->middleware(['auth.token']);
// Virtual Account Status Update (Phase 3 Extra)
Route::patch('/user/virtual-account/status', [AuthController::class , 'updateVirtualAccountStatus'])->middleware(['auth.token']);

// Virtual Cards (Phase 4)
Route::post('user/card/ngn', [VirtualCardController::class , 'createNgnCard'])->middleware(['auth.token', 'system.lock:card_ngn']);
Route::post('user/card/usd', [VirtualCardController::class , 'createUsdCard'])->middleware(['auth.token', 'system.lock:card_usd']);
Route::get('user/cards', [VirtualCardController::class , 'getCards'])->middleware(['auth.token']);

// Card Operations (Phase 5)
Route::post('user/card/{id}/fund', [VirtualCardController::class , 'fundCard'])->middleware(['auth.token']);
Route::post('user/card/{id}/withdraw', [VirtualCardController::class , 'withdrawCard'])->middleware(['auth.token']);
Route::put('user/card/{id}/status', [VirtualCardController::class , 'changeStatus'])->middleware(['auth.token']); // Freeze/Unfreeze

// Card Details & Balance (Phase 5)
Route::get('user/card/{id}/details', [VirtualCardController::class , 'getCardDetails'])->middleware(['auth.token']);


// Card Transactions (Phase 7)
Route::get('user/card/{id}/transactions', [VirtualCardController::class , 'getCardTransactions'])->middleware(['auth.token']);

// Card Settings (Admin)
Route::get('/secure/card/settings/{id}/habukhan/secure', [AdminController::class , 'getCardSettings']);
Route::post('/secure/card/settings/update/{id}/habukhan/secure', [AdminController::class , 'UpdateDiscountOther']);

Route::get('/secure/welcome', [AppController::class , 'welcomeMessage']);
Route::get('/secure/discount/other', [AppController::class , 'discountOther']);
Route::get('/secure/discount/mobile-cash', [AppController::class , 'getDiscountCash']); // Renamed for App A2C Rates
Route::get('/secure/discount/banks', [AppController::class , 'getBankCharges']); // Added for bank transfer fees
Route::get('/secure/beneficiaries', [App\Http\Controllers\API\BeneficiaryController::class , 'index']); // New: Source of Truth for Beneficiaries
Route::post('/secure/beneficiaries/{id}/toggle-favorite', [App\Http\Controllers\API\BeneficiaryController::class , 'toggleFavorite']);
Route::delete('/secure/beneficiaries/{id}', [App\Http\Controllers\API\BeneficiaryController::class , 'destroy']);
Route::get('/secure/virtualaccounts/status', [AppController::class , 'getVirtualAccountStatus']);
Route::post('/secure/lock/virtualaccounts/{id}/habukhan/secure', [AdminController::class , 'lockVirtualAccount']);
Route::post('/secure/selection/virtualaccounts/{id}/habukhan/secure', [AdminController::class , 'setDefaultVirtualAccount']);

// System Lock Check (Admin)
Route::get('system/lock/{feature}', [AuthController::class , 'CheckSystemLock']);

// System Lock Check (Admin)
Route::get('system/lock/{feature}', [AuthController::class , 'CheckSystemLock']);

// Smart Transfer Router Admin Routes
Route::post('/secure/lock/bank/{id}/habukhan/secure', [AdminController::class , 'lockTransferProvider']);
Route::post('/secure/selection/banks/{id}/habukhan/secure', [AdminController::class , 'setTransferPriority']);
Route::post('/secure/discount/other/{id}/habukhan/secure', [AdminController::class , 'updateTransferCharges']);
Route::post('/secure/lock/global/transfers/{id}/habukhan/secure', [AdminController::class , 'toggleGlobalTransferLock']);
Route::get('/secure/trans/settings/{id}/habukhan/secure', [AdminController::class , 'getTransferSettings']);

// Mobile App - Banks List for Transfers
Route::get('/paystack/banks/{id}/secure', [Banks::class , 'GetBanksList']);

// Mobile App - Account Verification (Routes to active provider: Xixapay/Paystack/Monnify)
Route::post('/paystack/resolve/{id}/secure', [AccountVerification::class , 'verifyBankAccount']);
Route::get('/banks/sync', [Banks::class , 'syncBanks']);

// Transfer Webhooks (Paystack / Xixapay)
// URL: https://[domain]/api/webhook/transfer/paystack
// URL: https://[domain]/api/webhook/transfer/xixapay
Route::post('/webhook/transfer/{provider}', [WebhookController::class , 'transferWebhook']);

Route::post('upgrade/api/user', [AppController::class , 'apiUpgrade']);
Route::get('/user/resend/{id}/otp', [AuthController::class , 'resendOtp']);
Route::post('/website/affliate/user', [AppController::class , 'buildWebsite']);
Route::get('/upgrade/awuf/{id}/user', [AppController::class , 'AwufPackage']);
Route::get('/upgrade/agent/{id}/user', [AppController::class , 'AgentPackage']);
Route::get('/website/app/network', [AppController::class , 'SystemNetwork']);
Route::get('airtimecash/number', [AppController::class , 'CashNumber']);
Route::get('/verify/network/{id}/habukhan/system', [AppController::class , 'checkNetworkType']);
Route::get('/system/notification/user/{id}/request', [AdminController::class , 'userRequest']);
Route::get('/clear/notification/clear/all/{id}/by/admin', [AdminController::class , 'ClearRequest']);
Route::get('/system/all/user/records/admin/safe/url/{id}/secure', [AdminController::class , 'UserSystem']);
Route::post('/delete/user/record/user/hacker/{id}/system', [AppController::class , 'DeleteUser']);
Route::post('/delete/single/record/user/hacker/{id}/system', [AppController::class , 'singleDelete']);
Route::post('/system/all/user/edit/user/safe/url/{id}/secure', [AdminController::class , 'editUserDetails']);
Route::post('/system/admin/create/new/user/safe/url/{id}/secure', [AdminController::class , 'CreateNewUser']);
Route::post('/system/admin/change_key/changes/of/key/url/{id}/secure', [AdminController::class , 'ChangeApiKey']);
Route::post('/system/admin/edit/edituser/habukhan/habukhan/secure/boss/asd/asd/changes/of/key/url/{id}/secure', [AdminController::class , 'EditUser']);
Route::post('/filter/user/details/admin/by/habukhan/{id}/secure/react', [AdminController::class , 'FilterUser']);
Route::post('/credit/user/only/admin/secure/{id}/verified/by/system', [AdminController::class , 'CreditUserHabukhan']);
Route::post('/credit/upgradeuser/upgrade/{id}/system/by/system', [AdminController::class , 'UpgradeUserAccount']);
Route::post('/reset/user/account/{id}/habukhan/secure', [AdminController::class , 'ResetUserPassword']);
Route::post('/delete/user/record/automated/hacker/{id}/system', [AdminController::class , 'Automated']);
Route::post('/delete/user/record/bank/hacker/{id}/system', [AdminController::class , 'BankDetails']);
Route::post('/reset/user/block/number/{id}/habukhan/secure', [AdminController::class , 'AddBlock']);
Route::post('delete/user/record/block/hacker/{id}/system', [AdminController::class , 'DeleteBlock']);
Route::get('system/all/user/discount/discount/user/safe/url/{id}/secure', [AdminController::class , 'Discount']);
Route::post('edit/airtime/discount/account/{id}/habukhan/secure', [AdminController::class , 'AirtimeDiscount']);
Route::post('edit/cable/charges/account/{id}/habukhan/secure', [AdminController::class , 'CableCharges']);
Route::post('edit/bill/charges/account/{id}/habukhan/secure', [AdminController::class , 'BillCharges']);
Route::post('edit/cash/discount/charges/account/{id}/habukhan/secure', [AdminController::class , 'CashDiscount']);
Route::post('edit/result/charges/account/{id}/habukhan/secure', [AdminController::class , 'ResultCharge']);
Route::post('edit/other/charges/account/{id}/habukhan/secure', [AdminController::class , 'OtherCharge']);
Route::post('edit/airtime/lock/account/{id}/habukhan/secure', [SecureController::class , 'Airtimelock']);
Route::post('edit/data/lock/account/{id}/habukhan/secure', [SecureController::class , 'DataLock']);
Route::post('edit/cable/lock/account/{id}/habukhan/secure', [SecureController::class , 'CableLock']);
Route::post('edit/result/lock/account/{id}/habukhan/secure', [SecureController::class , 'ResultLock']);
Route::post('edit/other/lock/account/{id}/habukhan/secure', [SecureController::class , 'OtherLock']);
Route::post('delete/data/habukhan/plans/hacker/{id}/system', [SecureController::class , 'DataPlanDelete']);
Route::post('add/data/plan/new/habukhan/safe/url/{id}/secure', [SecureController::class , 'AddDataPlan']);
Route::post('system/data/plan/edit/user/safe/url/{id}/secure', [SecureController::class , 'RDataPlan']);
Route::post('system/admin/edit/dataplan/dataplan/habukhan/secure/boss/asd/asd/changes/{id}/secure', [SecureController::class , 'EditDataPlan']);
Route::post('delete/cable/habukhan/plans/hacker/{id}/system', [SecureController::class , 'DeleteCablePlan']);
Route::post('system/cable/plan/edit/user/safe/url/{id}/secure', [SecureController::class , 'RCablePlan']);
Route::post('add/cable/plan/new/habukhan/safe/url/{id}/secure', [SecureController::class , 'AddCablePlan']);
Route::post('system/admin/edit/cableplan/cableplan/habukhan/secure/boss/asd/asd/changes/{id}/secure', [SecureController::class , 'EditCablePlan']);
Route::post('delete/bill/habukhan/plans/hacker/{id}/system', [SecureController::class , 'DeleteBillPlan']);
Route::post('system/bill/plan/edit/user/safe/url/{id}/secure', [SecureController::class , 'RBillPlan']);
Route::post('add/bill/plan/new/habukhan/safe/url/{id}/secure', [SecureController::class , 'CreateBillPlan']);
Route::post('system/admin/edit/billplan/billplan/habukhan/secure/boss/asd/asd/changes/{id}/secure', [SecureController::class , 'EditBillPlan']);
Route::post('system/network/plan/edit/user/safe/url/{id}/secure', [SecureController::class , 'RNetwork']);
Route::post('edit/network/plan/new/habukhan/safe/url/{id}/secure', [SecureController::class , 'EditeNetwork']);
Route::post('edit/habukhanapi/charges/account/{id}/habukhan/secure', [SecureController::class , 'EditHabukhanApi']);
Route::post('edit/adexapi/charges/account/{id}/habukhan/secure', [SecureController::class , 'EditAdexApi']);
Route::post('edit/msorgapi/charges/account/{id}/habukhan/secure', [SecureController::class , 'EditMsorgApi']);
Route::post('edit/virusapi/charges/account/{id}/habukhan/secure', [SecureController::class , 'EditVirusApi']);
Route::post('edit/otherapi/charges/account/{id}/habukhan/secure', [SecureController::class , 'EditOtherApi']);
Route::post('edit/webapi/charges/account/{id}/habukhan/secure', [SecureController::class , 'EditWebUrl']);
Route::post('system/result/plan/edit/user/safe/url/{id}/secure', [SecureController::class , 'RResult']);
Route::post('add/result/plan/new/habukhan/safe/url/{id}/secure', [SecureController::class , 'AddResult']);
Route::post('delete/result/habukhan/plans/hacker/{id}/system', [SecureController::class , 'DelteResult']);
Route::post('system/admin/edit/resultplan/resultplan/habukhan/secure/boss/asd/asd/changes/{id}/secure', [SecureController::class , 'EditResult']);
Route::get('system/notification/user/{id}/request/user', [AppController::class , 'UserNotif']);
Route::get('clear/notification/clear/all/{id}/by/user', [AppController::class , 'ClearNotifUser']);
Route::get('user/stock/wallet/{id}/secure/habukhan', [SecureController::class , 'UserStock']);
Route::post('user/edit/stockvending/{id}/habukhan/secure', [SecureController::class , 'UserEditStock']);
Route::post('edituser/habukhan/secure/{id}/secure', [SecureController::class , 'UserProfile']);
Route::post('change/password/by/user/habukhan/{id}/now', [SecureController::class , 'ResetPasswordUser']);
Route::post('change/pin/by/user/habukhan/{id}/now', [SecureController::class , 'ChangePin']);
Route::post('create/newpin/by/user/habukhan/{id}/now', [SecureController::class , 'CreatePin']);
Route::post('accountdetails/habukhan/secure/{id}/secure', [SecureController::class , 'UserAccountDetails']);
Route::get('user/accountdetails/wallet/{id}/secure/habukhan', [SecureController::class , 'UsersAccountDetails']);
Route::post('get/data/plans/{id}/habukhan', [PlanController::class , 'DataPlan']);
Route::get('cable/plan/{id}/habukhan/system', [PlanController::class , 'CablePlan']);
Route::get('cable/charges/{id}/admin', [PlanController::class , 'CableCharges']);
Route::post('edit/datasel/account/{id}/habukhan/secure', [AdminController::class , 'DataSel']);
Route::post('edit/data_card_sel/account/{id}/secure', [AdminController::class , 'DataCardSel']);
Route::post('edit/recharge_card_sel/account/{id}/secure', [AdminController::class , 'RechargeCardSel']);
Route::post('edit/airtimesel/account/{id}/habukhan/secure', [AdminController::class , 'AirtimeSel']);
Route::post('edit/cashsel/account/{id}/habukhan/secure', [AdminController::class , 'CashSel']);
Route::post('edit/cablesel/account/{id}/habukhan/secure', [AdminController::class , 'CableSel']);
Route::post('edit/billsel/account/{id}/habukhan/secure', [AdminController::class , 'BillSel']);
Route::post('edit/bulksmssel/account/{id}/habukhan/secure', [AdminController::class , 'BulkSMSsel']);
Route::post('edit/bank-transfer/sel/account/{id}/habukhan/secure', [AdminController::class , 'BankTransferSel']);
Route::post('edit/examsel/account/{id}/habukhan/secure', [AdminController::class , 'ExamSel']);
Route::get('website/app/cable/lock', [AppController::class , 'CableName']);
Route::get('bill/charges/{id}/admin', [AppController::class , 'BillCal']);
Route::get('website/app/bill/list', [AppController::class , 'DiscoList']);
Route::post('airtimecash/discount/admin', [AppController::class , 'AirtimeCash']);
Route::get('bulksms/cal/admin', [AppController::class , 'BulksmsCal']);
Route::get('resultprice/admin/secure', [AppController::class , 'ResultPrice']);
Route::get('total/data/purchase/{id}/secure', [SecureController::class , 'DataPurchased']);
Route::get('system/user/stockbalance/{id}/secure', [SecureController::class , 'StockBalance']);
Route::get('system/app/softwarwe', [SecureController::class , 'SOFTWARE']);
Route::post('edit/systeminfo/{id}/habukhan/secure', [SecureController::class , 'SystemInfo']);
Route::post('system/message/{id}/habukhan/secure', [SecureController::class , 'SytemMessage']);
Route::post('delete/feature/{id}/system', [SecureController::class , 'DeleteFeature']);
Route::post('new/feature/{id}/habukhan/secure', [SecureController::class , 'AddFeature']);
Route::post('system/delete/kyc/{id}/secure', [AdminController::class , 'DeleteKyc']);
Route::post('delete/app/{id}/system', [SecureController::class , 'DeleteApp']);
Route::post('new/app/{id}/habukhan/secure', [SecureController::class , 'NewApp']);
Route::post('edit/paymentinfo/{id}/habukhan/secure', [SecureController::class , 'PaymentInfo']);
Route::post('manualpayment/habukhan/secure/{id}/secure', [PaymentController::class , 'BankTransfer']);
Route::get('all/user/infomation/admin/setting/{id}/secure', [AdminController::class , 'AllUsersInfo']);
Route::get('bank/info/all/bank/all/bank/{id}/secure', [AdminController::class , 'AllBankDetails']);
Route::get('user/bank/account/details/{id}/secure', [AdminController::class , 'UserBankAccountD']);
Route::get('user/banned/habukhan/ade/banned/user/{id}/secure', [AdminController::class , 'AllUserBanned']);
Route::get('all/system/plan/purchase/by/habukhan/{id}/secure', [AdminController::class , 'AllSystemPlan']);
Route::get('system/all/user/kyc/records/{id}/secure', [AdminController::class , 'AllUsersKyc']);
Route::post('system/admin/kyc/approve/{id}/secure', [AdminController::class , 'ApproveUserKyc']);
Route::post('system/admin/kyc/reject/{id}/secure', [AdminController::class , 'RejectUserKyc']);
Route::post('system/admin/kyc/delete/{id}/secure', [AdminController::class , 'DeleteUserKyc']);


Route::post('new_data_card_plan/{id}/secure', [NewStock::class , 'NewDataCardPlan']);
Route::post('new_recharge_card_plan/{id}/secure', [NewStock::class , 'NewRechargeCardPlan']);
Route::get('all/store/plan/{id}/secure', [NewStock::class , 'AllNewStock']);
Route::post('delete/data_card_plan/{id}/system', [NewStock::class , 'DeleteDataCardPlan']);
Route::post('delete/recharge_card_plan/{id}/system', [NewStock::class , 'DeleteRechargeCardPlan']);
Route::post('habukhan/data_plan_card/{id}/secure', [NewStock::class , 'RDataCardPlan']);
Route::post('habukhan/recharge_plan_card/{id}/secure', [NewStock::class , 'RRechargeCardPlan']);
Route::post('edit_data_card_plan/{id}/secure', [NewStock::class , 'EditDataCard']);
Route::post('edit_new_recharge_card_plan/{id}/secure', [NewStock::class , 'EditRechargeCardPlan']);
Route::post('delete/store_data_card/{id}/system', [NewStock::class , 'DeleteStockDataCard']);
Route::post('get/data_card_plan/{id}/system', [NewStock::class , 'DataCardPlansList']);
Route::post('add_store_data_card/{id}/secure', [NewStock::class , 'StoreDataCard']);
Route::post('r_add_store_data_card/{id}/secure', [NewStock::class , 'RStockDataCard']);
Route::post('r_add_store_recharge_card/{id}/secure', [NewStock::class , 'RStockRechargeCard']);
Route::post('get/recharge_card_plan/{id}/system', [NewStock::class , 'RechargeCardPlanList']);
Route::post('edit_store_data_plans/{id}/secure', [NewStock::class , 'EditDataCardPlan']);
Route::post('delete/store_recharge_card/{id}/system', [NewStock::class , 'DeleteStockRechargeCardPlan']);
Route::post('add_store_recharge_card/{id}/secure', [NewStock::class , 'AddStockRechargeCard']);
Route::post('edit_store_recharge_plans/{id}/secure', [NewStock::class , 'EditStoreRechargePlan']);
Route::post('data_card_lock/{id}/secure', [NewStock::class , 'DataCardLock']);
Route::post('recharge_card_lock/{id}/secure', [NewStock::class , 'RechargeCardLock']);
Route::post('get/data_card_plans/{id}/habukhan', [NewStock::class , 'UserDataCardPlan']);
Route::post('get/recharge_card_plans/{id}/habukhan', [NewStock::class , 'UserRechargeCardPlan']);
// transas both admin and users here
Route::get('all/data_recharge_cards/{id}/secure', [Trans::class , 'DataRechardPrint']);
Route::get('recharge_card/trans/{id}/secure', [Trans::class , 'RechargeCardProcess']);
Route::get('recharge_card/trans/{id}/secure/sucess', [Trans::class , 'RechargeCardPrint']);
Route::post('search/by/user/{id}/history', [Trans::class , 'SearchAllDataBase']);
Route::get('system/all/trans/{id}/secure', [Trans::class , 'UserTrans']);
Route::get('system/all/history/records/{id}/secure', [Trans::class , 'AllHistoryUser']);
Route::get('system/all/datatrans/habukhan/{id}/secure', [Trans::class , 'AllDataHistoryUser']);
Route::get('system/all/stock/trans/habukhan/{id}/secure', [Trans::class , 'AllStockHistoryUser']);
Route::get('system/all/deposit/trans/habukhan/{id}/secure', [Trans::class , 'AllDepositHistory']);
Route::get('system/all/airtime/trans/habukhan/{id}/secure', [Trans::class , 'AllAirtimeUser']);
Route::get('system/all/cable/trans/habukhan/{id}/secure', [Trans::class , 'AllCableHistoryUser']);
Route::get('system/all/bill/trans/habukhan/{id}/secure', [Trans::class , 'AllBillHistoryUser']);
Route::get('system/all/result/trans/habukhan/{id}/secure', [Trans::class , 'AllResultHistoryUser']);

// Fix: Missing route for "Adex" history calls (maps to AllHistoryUser)
Route::get('system/all/history/adex/{id}/secure', [Trans::class , 'AllHistoryUser']);
// Fix: Stub for card transactions to prevent 500 error
Route::get('card-transactions/{id}/secure', function () {
    return response()->json(['status' => 'success', 'data' => []]);
});
Route::get('data_card/trans/{id}/secure', [Trans::class , 'DataCardInvoice']);
Route::get('data_card/trans/{id}/secure/sucess', [Trans::class , 'DataCardSuccess']);
Route::get('data/trans/{id}/secure', [Trans::class , "DataTrans"]);
Route::get('airtime/trans/{id}/secure', [Trans::class , 'AirtimeTrans']);
Route::get('deposit/trans/{id}/secure', [Trans::class , 'DepositTrans']);
Route::get('cable/trans/{id}/secure', [Trans::class , 'CableTrans']);
Route::get('bill/trans/{id}/secure', [Trans::class , 'BillTrans']);
Route::get('airtimecash/trans/{id}/secure', [Trans::class , 'AirtimeCashTrans']);
Route::get('bulksms/trans/{id}/secure', [Trans::class , 'BulkSMSTrans']);
Route::get('resultchecker/trans/{id}/secure', [Trans::class , 'ResultCheckerTrans']);
Route::get('manual/trans/{id}/secure', [Trans::class , 'ManualTransfer']);
Route::get('transfer/trans/{id}/secure', [Trans::class , 'TransferDetails']);
Route::get('website/app/{id}/data_card_pan', [PlanController::class , 'DataCard']);
Route::get('website/app/{id}/recharge_card_pan', [PlanController::class , 'RechargeCard']);
Route::get('website/app/{id}/dataplan', [PlanController::class , 'DataList']);
Route::get('website/app/cableplan', [PlanController::class , 'CableList']);
Route::get('website/app/disco', [PlanController::class , 'DiscoList']);
Route::get('website/app/exam', [PlanController::class , 'ExamList']);
// api endpoint for users
Route::post('data', [DataPurchase::class , 'BuyData']);
Route::post('topup', [AirtimePurchase::class , 'BuyAirtime']);
Route::get('cable/cable-validation', [IUCvad::class , 'IUC']);
Route::post('cable', [CablePurchase::class , 'BuyCable']);
Route::get('bill/bill-validation', [MeterVerify::class , 'Check']);
Route::post('bill', [BillPurchase::class , 'Buy']);
Route::post('cash', [AirtimeCash::class , 'Convert']);
Route::post('bulksms', [BulksmsPurchase::class , 'Buy']);
Route::post('transferwallet', [BonusTransfer::class , 'Convert']);
Route::post('transfer', [TransferPurchase::class , 'TransferRequest']); // Web Transfer Route
Route::post('paystack/transfer/{id}/secure', [TransferPurchase::class , 'TransferRequest']); // Mobile App Transfer Route
Route::post('exam', [ExamPurchase::class , 'ExamPurchase']);
Route::post('user', [AccessUser::class , 'Generate']);
Route::post('data_card', [DataCard::class , 'DataCardPurchase']);
Route::post('recharge_card', [RechargeCard::class , 'RechargeCardPurchase']);
Route::post('autopilot/a2c/otp', [AirtimeCash::class , 'A2C_SendOtp']);
Route::post('autopilot/a2c/verify', [AirtimeCash::class , 'A2C_VerifyOtp']);
Route::post('autopilot/a2c/submit', [AirtimeCash::class , 'A2C_Execute']);
Route::post('autopilot/a2c/submit', [AirtimeCash::class , 'A2C_Execute']);
Route::post('transfer/internal', [\App\Http\Controllers\Purchase\InternalTransferController::class , 'transfer']);
Route::get('transfer/internal/verify', [\App\Http\Controllers\Purchase\InternalTransferController::class , 'verifyUser']);
// admin transaction and auto refund

Route::get('admin/records/all/trans/{id}/secure', [AdminTrans::class , 'AllTrans']);
Route::post('admin/data_card_refund/{id}/secure', [AdminTrans::class , 'DataCardRefund']);
Route::post('admin/recharge_card_refund/{id}/secure', [AdminTrans::class , 'RechargeCardRefund']);
Route::get('admin/all/data_recharge_cards/{id}/secure', [AdminTrans::class , 'DataRechargeCard']);
Route::get('admin/all/transaction/history/{id}/secure', [AdminTrans::class , 'AllSummaryTrans']);
Route::get('admin/all/data/trans/by/system/{id}/secure', [AdminTrans::class , 'DataTransSum']);
Route::get('admin/all/airtime/trans/by/system/{id}/secure', [AdminTrans::class , 'AirtimeTransSum']);
Route::get('admin/all/stock/trans/by/system/{id}/secure', [AdminTrans::class , 'StockTransSum']);
Route::get('admin/all/transfer/trans/by/system/{id}/secure', [AdminTrans::class , 'TransferTransSum']);
Route::get('admin/all/deposit/trans/by/system/{id}/secure', [AdminTrans::class , 'DepositTransSum']);
Route::get('admin/all/card/trans/by/system/{id}/secure', [AdminTrans::class , 'CardTransSum']);
Route::get('admin/all/charity/donations/{id}/secure', [AdminTrans::class , 'CharityDonationsTransSum']);
Route::get('admin/all/internal/transfers/{id}/secure', [AdminTrans::class , 'InternalTransfersTransSum']);
// Charity Management
Route::post('admin/charity/add', [CharityController::class , 'addCharity']);
Route::post('admin/charity/update', [CharityController::class , 'updateCharity']);
Route::post('admin/charity/delete', [CharityController::class , 'deleteCharity']);
Route::post('admin/campaign/delete', [CharityController::class , 'deleteCampaign']);
Route::get('admin/users/search/{id}/secure', [CharityController::class , 'searchUsers']);
Route::get('admin/charities/{id}/secure', [CharityController::class , 'getCharities']);
Route::get('charity/details/{id}', [CharityController::class , 'getCharityDetails']); // Added for user-facing profile
Route::post('admin/campaign/add', [CharityController::class , 'addCampaign']);
Route::get('charity/campaigns', [CharityController::class , 'getCampaigns']);
Route::get('charity/categories', [CharityController::class , 'getCategories']);
Route::post('charity/donate', [CharityController::class , 'donate']);
Route::get('charity/my-donations', [CharityController::class , 'getUserDonations']);
Route::post('admin/charity/payouts', [CharityController::class , 'processPayouts']);
Route::post('admin/charity/withdrawal/approve', [CharityController::class , 'approveWithdrawal']);
Route::post('admin/card_refund/{id}/secure', [AdminTrans::class , 'CardRefund']);
Route::post('admin/data/{id}/secure', [AdminTrans::class , 'DataRefund']);
Route::post('admin/airtime/{id}/secure', [AdminTrans::class , 'AirtimeRefund']);
Route::post('admin/cable/{id}/secure', [AdminTrans::class , 'CableRefund']);
Route::post('admin/bill/{id}/secure', [AdminTrans::class , 'BillRefund']);
Route::post('admin/exam/{id}/secure', [AdminTrans::class , 'ResultRefund']);
Route::post('admin/bulksms/{id}/secure', [AdminTrans::class , 'BulkSmsRefund']);
Route::post('cash/data/{id}/secure', [AdminTrans::class , 'AirtimeCashRefund']);
Route::post('manual/data/{id}/secure', [AdminTrans::class , 'ManualSuccess']);
Route::post('admin/transfer/{id}/secure', [AdminTrans::class , 'TransferUpdate']);
//message notif
Route::post('gmail/sendmessage/{id}/habukhan/secure', [MessageController::class , 'Gmail']);
Route::post('system/sendmessage/{id}/habukhan/secure', [MessageController::class , 'System']);
Route::post('bulksms/sendmessage/{id}/habukhan/secure', [MessageController::class , 'Bulksms']);

// Notification History Management
Route::get('admin/notifications/history/{id}/secure', [NotificationHistoryController::class , 'getNotifications']);
Route::post('admin/notifications/resend/{notificationId}/{id}/secure', [NotificationHistoryController::class , 'resendNotification']);
Route::post('admin/notifications/update/{notificationId}/{id}/secure', [NotificationHistoryController::class , 'updateNotification']);
Route::delete('admin/notifications/delete/{notificationId}/{id}/secure', [NotificationHistoryController::class , 'deleteNotification']);

//calculator
Route::post('transaction/calculator/{id}/habukhan/secure', [TransactionCalculator::class , 'Admin']);
Route::post('user/calculator/{id}/habukhan/secure', [TransactionCalculator::class , 'User']);

// fund
Route::post('atmfunding/habukhan/secure/{id}/secure', [PaymentController::class , 'ATM']);
// Route::get('monnify/callback', [PaymentController::class, 'MonnifyATM']);
Route::any('xixapay_webhook/secure/callback/pay/habukhan/0001', [PaymentController::class , 'Xixapay']);
Route::post('paystack/habukhan/secure/{id}/secure', [PaymentController::class , 'Paystackfunding']);
Route::get('callback/paystack', [PaymentController::class , 'PaystackCallBack']);

Route::post('update-kyc-here/habukhan/secure', [PaymentController::class , 'UpdateKYC']);
Route::post('dynamic-account-number-here/habukhan/secure', [PaymentController::class , 'DynamicAccount']);

Route::any('callback/simserver', [WebhookController::class , 'Simserver']);
Route::any('habukhan/webhook/secure', [WebhookController::class , 'HabukhanWebhook']);
Route::any('autopilot/webhook/secure', [WebhookController::class , 'AutopilotWebhook']);

// invite
Route::post('inviting/user/{id}/secure', [SecureController::class , 'InviteUser']);
//reset
Route::post('reset/mypassword', [SecureController::class , 'ResetPassword']);
Route::post('change/mypassword/{id}/secure', [SecureController::class , 'ChangePPassword']);

// list data plan
Route::get('website/plan', [PlanController::class , 'HomeData']);

// sel


Route::get('data/sel/by/system/{id}/secure', [Selection::class , 'DataSel']);
Route::get('airtime/sel/by/system/{id}/secure', [Selection::class , 'AirtimeSel']);
Route::get('cash/sel/by/system/{id}/secure', [Selection::class , 'CashSel']);
Route::get('cable/sel/by/system/{id}/secure', [Selection::class , 'CableSel']);
Route::get('bulksms/sel/by/system/{id}/secure', [Selection::class , 'BulksmsSel']);
Route::get('bill/sel/by/system/{id}/secure', [Selection::class , 'BillSel']);
Route::get('exam/sel/by/system/{id}/secure', [Selection::class , 'ResultSel']);
Route::get('bank-transfer/sel/by/system/{id}/secure', [Selection::class , 'BankTransferSel']);
Route::get('data_card_sel/system/{id}/data_card', [Selection::class , 'DataCard']);
Route::get('recharge_card_sel/system/{id}/recharge_card', [Selection::class , 'RechargeCard']);


// app link over here
//

Route::post('app/habukhan/secure/login', [Auth::class , 'AppLogin']);
Route::post('app/habukhan/verify/otp', [Auth::class , 'AppVerify']);
Route::post('app/habukhan/resend/otp', [Auth::class , 'ResendOtp']);
Route::post('app/habukhan/signup', [Auth::class , 'SignUp']);
Route::post('app/finger/habukhan/login', [Auth::class , 'FingerPrint']);
Route::match (['get', 'post'], 'app/secure/check/login/details', [Auth::class , 'APPLOAD']);
Route::get('app/habukhan/setting', [Auth::class , 'AppGeneral']);
// Route::post('app/check/monnify/secure', [Auth::class, 'APPMOnify']);
Route::post('app/manual/funding/{id}/send', [Auth::class , 'ManualFunding']);
Route::get('app/network', [Auth::class , 'Network']);
Route::get('app/network_type/{id}/check', [Auth::class , 'NetworkType']);
Route::post('app/data_plan/{id}/load', [Auth::class , 'DataPlans']);
Route::post('app/verify/transaction-pin', [Auth::class , 'TransactionPin']);
Route::get('app/cable_bill', [Auth::class , 'CableBillID']);
Route::post('app/cable_plan/load', [Auth::class , 'CablePlan']);
Route::post('app/price', [Auth::class , 'PriceList']);
Route::get('secure/discount/banks', [AppController::class , 'getDiscountBanks']);
Route::get('secure/discount/other', [AppController::class , 'getDiscountOther']);
Route::get('secure/discount/system', [AppController::class , 'getDiscountSystem']);
Route::post('/user/password/change', [Auth::class , 'ChangePassword']);
Route::post('/user/profile/update', [Auth::class , 'updateProfile']);
Route::post('/user/kyc/update', [Auth::class , 'updateKyc']);
Route::post('/user/pin/change', [Auth::class , 'ChangePin']);
Route::get('/user/referral/data', [Auth::class , 'getReferralData']);
Route::post('/user/referral/transfer', [Auth::class , 'transferReferralBonus']);
Route::post('app/transaction', [Auth::class , 'Transaction']);
Route::post('app/profile_image', [Auth::class , 'ProfileImage']);
Route::post('app/notification', [Auth::class , 'Notification']);
Route::post('app/complete_profile', [Auth::class , 'CompleteProfile']);
Route::post('app/complete_pin', [Auth::class , 'NewPin']);
Route::post('app/deposit/transaction', [Auth::class , 'DepositTransaction']);
Route::post('app/transaction/details', [Auth::class , 'TransactionInvoice']);
Route::get('receipt/{id}/{transid}', [Auth::class , 'getReceipt']);
Route::post('app/transaction_history_habukhan_doing', [Auth::class , 'TransactionHistoryHabukhan']);
Route::post('app/system_notification_here', [Auth::class , 'AppSystemNotification']);
Route::post('app/clear/notification/here', [Auth::class , 'ClearNotification']);
Route::post('app/recent_transacion', [Auth::class , 'recentTransaction']);
Route::get('user/recent-transactions/{user_id}', [Auth::class , 'recentTransaction']);
Route::get('transactions', [Auth::class , 'appTransactions']);
Route::post('app/data_card_plan', [Auth::class , 'DataCardPlans']);
Route::post('app/recharge_card_plan', [Auth::class , 'RechargeCardPlans']);
Route::post('app/otp_transaction_pin', [Auth::class , 'SendOtp']);
Route::post('app/delete_account_habukhan', [Auth::class , 'DeleteUserAccountNot']);
Route::post('app/update-fcm-token', [Auth::class , 'updateFcmToken']);
Route::post('app/notification/count', [Auth::class , 'NotificationCount']);
Route::post('app/notification/delete', [Auth::class , 'DeleteSingleNotification']);

// data and airtime refund
Route::get('refund/system/refund', [AdminTrans::class , 'AutoRefundBySystem']);
Route::get('success/system/success', [AdminTrans::class , 'AutoSuccessBySystem']);

Route::get('check/banks/user/gstar/{id}/secure/this/site/here', [Banks::class , 'GetBanksArray']);
// api get admin balance

Route::get('check/api/balance/{id}/secure', [AdminController::class , 'ApiBalance']);
Route::get('all/virtual/cards/{id}/secure', [AdminController::class , 'AllVirtualCards']);
Route::post('admin/terminate/virtual/card/{id}/secure', [AdminController::class , 'AdminTerminateCard']);
Route::post('admin/debit/virtual/card/{id}/secure', [AdminController::class , 'AdminDebitCard']);
Route::post('admin/delete/virtual/card/{id}/secure', [AdminController::class , 'AdminDeleteCard']);
Route::get('admin/card/customer/info/{cardId}/{id}/secure', [AdminController::class , 'AdminCardCustomerInfo']);

// Route::get('habukhan-export-to-excel', [PaymentController::class, 'importExcel']);





Route::post('xixapay/webhook', [PaymentController::class , 'Xixapay']);
Route::post('webhooks/xixapay/card', [WebhookController::class , 'handleCardWebhook']); // Phase 6
Route::post('monnify/webhook', [PaymentController::class , 'MonnifyWebhook']);
Route::post('paymentpoint/webhook/secure/callback/pay/habukhan/0001', [PaymentController::class , 'PaymentPointWebhook']);

// PointWave Test Routes (Remove after testing)
Route::prefix('pointwave/test')->group(function () {
    Route::get('all', [\App\Http\Controllers\API\PointWaveTestController::class, 'testAll']);
    Route::get('connection', [\App\Http\Controllers\API\PointWaveTestController::class, 'testConnection']);
    Route::get('balance', [\App\Http\Controllers\API\PointWaveTestController::class, 'getBalance']);
    Route::get('banks', [\App\Http\Controllers\API\PointWaveTestController::class, 'getBanks']);
    Route::post('verify-account', [\App\Http\Controllers\API\PointWaveTestController::class, 'verifyAccount']);
    Route::get('transactions', [\App\Http\Controllers\API\PointWaveTestController::class, 'getTransactions']);
    Route::post('create-virtual-account', [\App\Http\Controllers\API\PointWaveTestController::class, 'createTestVirtualAccount']);
});

// PointWave Production Routes
Route::prefix('pointwave')->middleware('auth:sanctum')->group(function () {
    // Virtual Account Management
    Route::get('virtual-account', [\App\Http\Controllers\API\PointWaveController::class, 'getVirtualAccount']);
    Route::post('virtual-account/create', [\App\Http\Controllers\API\PointWaveController::class, 'createVirtualAccount']);
    
    // Bank Operations
    Route::get('banks', [\App\Http\Controllers\API\PointWaveController::class, 'getBanks']);
    Route::post('verify-account', [\App\Http\Controllers\API\PointWaveController::class, 'verifyAccount']);
    
    // Transfer Operations
    Route::post('transfer', [\App\Http\Controllers\API\PointWaveController::class, 'initiateTransfer']);
    
    // Transaction Management
    Route::get('transactions', [\App\Http\Controllers\API\PointWaveController::class, 'getTransactions']);
    Route::get('transactions/{reference}', [\App\Http\Controllers\API\PointWaveController::class, 'getTransaction']);
    
    // KYC Management
    Route::post('kyc/submit', [\App\Http\Controllers\API\PointWaveController::class, 'submitKYC']);
    Route::get('kyc/status', [\App\Http\Controllers\API\PointWaveController::class, 'getKYCStatus']);
    
    // Admin KYC Management (TODO: Add admin middleware)
    Route::post('kyc/verify/{userId}', [\App\Http\Controllers\API\PointWaveController::class, 'verifyKYC']);
    Route::post('kyc/reject/{userId}', [\App\Http\Controllers\API\PointWaveController::class, 'rejectKYC']);
    
    // KYC Verification Services (EaseID Integration)
    Route::post('kyc/verify-bvn', [\App\Http\Controllers\API\PointWaveController::class, 'verifyBVNEnhanced']);
    Route::post('kyc/verify-nin', [\App\Http\Controllers\API\PointWaveController::class, 'verifyNINEnhanced']);
    Route::post('kyc/face-compare', [\App\Http\Controllers\API\PointWaveController::class, 'verifyFaceRecognition']);
    Route::post('kyc/liveness/initialize', [\App\Http\Controllers\API\PointWaveController::class, 'initializeLiveness']);
    Route::post('kyc/liveness/query', [\App\Http\Controllers\API\PointWaveController::class, 'queryLiveness']);
    Route::post('kyc/blacklist-check', [\App\Http\Controllers\API\PointWaveController::class, 'checkBlacklist']);
    Route::post('kyc/credit-score', [\App\Http\Controllers\API\PointWaveController::class, 'getCreditScore']);
    Route::post('kyc/loan-features', [\App\Http\Controllers\API\PointWaveController::class, 'getLoanFeatures']);
    Route::get('kyc/easeid-balance', [\App\Http\Controllers\API\PointWaveController::class, 'getEaseIDBalance']);
});

// PointWave Webhook Route (no auth, signature verification in middleware)
Route::post('pointwave/webhook', [\App\Http\Controllers\API\PointWaveWebhookController::class, 'handleWebhook']);