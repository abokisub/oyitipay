import re

with open('frontend/src/routes/index.js', 'r') as f:
    content = f.read()

# We need to find the place where `path: 'selection'` is correctly supposed to be.
# It was right after `api` block.
# The `api` block ends with:
api_end_str = """        {
          path: 'api',
          children: [
            { element: <Navigate to="/secure/api/system" replace />, index: true },
            { path: 'system', element: <HabukhanApis /> },
            { path: 'adex', element: <AdexApis /> },
            { path: 'msorg', element: <MsorgApi /> },
            { path: 'virus', element: <VirusApi /> },
            { path: 'other', element: <OtherApi /> },
            { path: 'web', element: <WebApi /> }
          ]
        },"""

selection_replacement = """        {
          path: 'selection',
          children: [
            { element: <Navigate to="/secure/selection/data" replace />, index: true },
            { path: 'data', element: <DataSel /> },
            { path: 'airtime', element: <AirtimeSel /> },
            { path: 'cable', element: <CableSel /> },
            { path: 'bill', element: <BillSel /> },
            { path: 'bulksms', element: <BulkSel /> },
            { path: 'exam', element: <ExamSel /> },
            { path: 'data_card', element: <DataCardSel /> },
            { path: 'recharge_card', element: <RechargeCardSel /> },
            { path: 'virtualaccounts', element: <VirtualAccountSel /> },
            { path: 'bank-transfer', element: <BankTransferSel /> },
            { path: 'kyc', element: <KycSel /> },
            { path: 'cash', element: <CashSel /> },
          ]
        },
        { path: 'support', element: <SupportManagement /> },
        { path: 'vouchers', element: <VoucherManagement /> },
        { path: 'cashback-settings', element: <CashbackSettings /> }
      ],
    },"""

parts = content.split(api_end_str)

# The first part is everything up to the FIRST api block.
start_content = parts[0] + api_end_str + "\n"

# The rest is all the garbage, including the second api block.
rest = "".join(parts[1:])

end_marker = "{ path: 'cashback-settings', element: <CashbackSettings /> }\n      ],\n    },"
if end_marker in rest:
    # We find the LAST occurrence of end_marker to skip all the duplicated stuff
    rest_parts = rest.rsplit(end_marker, 1)
    final_rest = rest_parts[1]
    
    new_content = start_content + selection_replacement + final_rest
    
    with open('frontend/src/routes/index.js', 'w') as f:
        f.write(new_content)
    print("Successfully fixed!")
else:
    print("end marker not found!")

