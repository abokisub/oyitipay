import re
import os

filepath = "Oyitipay Mobile/lib/modules/dashboard/screens/dashboard_screen.dart"

with open(filepath, 'r') as f:
    content = f.read()

# We need to replace the Column children inside the build method.
# Current build column children:
#                     // 1. Header
#                     _buildHeader(),
#                     SizedBox(height: verticalGap),
#                     // 2. Balance Card (with quick actions inside)
#                     _buildBalanceCard(isSmallScreen),
#                     SizedBox(height: sectionGap),
#                     // 2b. Virtual Account Card
#                     _buildVirtualAccountCard(isSmallScreen),
#                     SizedBox(height: sectionGap),
#                     // 3. Services Grid
#                     _buildServicesGrid(isSmallScreen),
#                     SizedBox(height: sectionGap),
#                     // 4. Transactions header + recent list

new_build_children = """                    // 1. Header
                    _buildHeader(),
                    SizedBox(height: verticalGap),

                    // 2. Balance Card
                    _buildBalanceCard(isSmallScreen),
                    const SizedBox(height: 12),

                    // 2b. Virtual Account Card
                    _buildVirtualAccountCard(isSmallScreen),
                    SizedBox(height: sectionGap),

                    // 3. Grids
                    _buildGridGroup1(isSmallScreen),
                    SizedBox(height: 12),
                    _buildGridGroup2(isSmallScreen),
                    SizedBox(height: 12),
                    _buildGridGroup3(isSmallScreen),
                    SizedBox(height: sectionGap),

                    // Daily Spin and Win Banner
                    _buildSpinWinBanner(),
                    SizedBox(height: sectionGap),

                    // 4. Transactions header + recent list"""

content = re.sub(
    r"// 1\. Header\s+_buildHeader\(\),\s+SizedBox\(height: verticalGap\),\s+// 2\. Balance Card[^\n]*\n\s+_buildBalanceCard\(isSmallScreen\),\s+SizedBox\(height: sectionGap\),\s+// 2b\. Virtual Account Card[^\n]*\n\s+_buildVirtualAccountCard\(isSmallScreen\),\s+SizedBox\(height: sectionGap\),\s+// 3\. Services Grid[^\n]*\n\s+_buildServicesGrid\(isSmallScreen\),\s+SizedBox\(height: sectionGap\),\s+// 4\. Transactions header \+ recent list",
    new_build_children,
    content,
    flags=re.MULTILINE
)

# Replace _buildBalanceCard
balance_card_pattern = r"(Widget _buildBalanceCard\(bool isSmallScreen\) \{)(.*?)(^\s+Widget _buildCurrencyTab)"
new_balance_card = """Widget _buildBalanceCard(bool isSmallScreen) {
    final balance = _authService.balance;

    return Container(
      padding: EdgeInsets.all(isSmallScreen ? 16 : 20),
      decoration: BoxDecoration(
        color: const Color(0xFF2563EB),
        borderRadius: BorderRadius.circular(isSmallScreen ? 16 : 20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF2563EB).withOpacity(0.25),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Text(
                    'Available Balance',
                    style: GoogleFonts.inter(
                      color: Colors.white.withOpacity(0.9),
                      fontSize: 13,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(width: 6),
                  GestureDetector(
                    onTap: () {
                      final newState = !_isBalanceVisible;
                      setState(() => _isBalanceVisible = newState);
                      SessionManager.instance.saveUIState('dashboard_balance_visible', newState);
                    },
                    child: Icon(
                      _isBalanceVisible ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                      color: Colors.white.withOpacity(0.9),
                      size: 16,
                    ),
                  ),
                ],
              ),
              GestureDetector(
                onTap: () async {
                  await context.push('/history');
                  _authService.refreshUser();
                  _loadRecentTransactions();
                },
                child: Row(
                  children: [
                    Text(
                      'Transaction History',
                      style: GoogleFonts.inter(
                        color: Colors.white.withOpacity(0.9),
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const Icon(Icons.chevron_right, color: Colors.white, size: 16),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              PrivacyBlur(
                isVisible: _isBalanceVisible,
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      '\u20A6',
                      style: TextStyle(
                        fontFamily: 'Arial',
                        color: Colors.white,
                        fontSize: isSmallScreen ? 24 : 28,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      NumberFormat('#,##0.00', 'en_US').format(balance),
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontSize: isSmallScreen ? 28 : 34,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
              GestureDetector(
                onTap: () async {
                  await context.push('/fund-wallet');
                  _authService.refreshUser();
                  _loadRecentTransactions();
                },
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.add_circle_outline, color: Color(0xFF2563EB), size: 18),
                      const SizedBox(width: 4),
                      Text(
                        'Add Money',
                        style: GoogleFonts.inter(
                          color: const Color(0xFF2563EB),
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Cashback: \u20A60',
                style: GoogleFonts.inter(
                  color: Colors.white.withOpacity(0.9),
                  fontSize: 12,
                ),
              ),
              Text(
                'Referral: \u20A60',
                style: GoogleFonts.inter(
                  color: Colors.white.withOpacity(0.9),
                  fontSize: 12,
                ),
              ),
            ],
          ),
        ],
      ),
    ).animate().fadeIn().scale(begin: const Offset(0.98, 0.98));
  }

"""
content = re.sub(balance_card_pattern, new_balance_card + r"\3", content, flags=re.DOTALL | re.MULTILINE)

# Replace Virtual Account Card
virtual_card_pattern = r"(Widget _buildVirtualAccountCard\(bool isSmallScreen\) \{)(.*?)(^\s+String _formatDate)"
new_virtual_card = """Widget _buildVirtualAccountCard(bool isSmallScreen) {
    final user = _authService.currentUser;
    final userEntity = user != null ? User.fromJson(user) : null;
    
    String bankName = userEntity?.bankName ?? 'PalmPay';
    String rawAccount = userEntity?.accountNumber ?? '';

    if (user != null && _defaultProvider != null) {
      final metaData = user['meta_data'];
      if (metaData != null && metaData['virtual_accounts'] != null) {
        final accounts = metaData['virtual_accounts'];
        for (var account in accounts) {
          final provider = account['provider']?.toString().toLowerCase();
          if (provider == _defaultProvider) {
            final accountBankName = account['bank_name']?.toString();
            if (accountBankName != null && accountBankName.isNotEmpty) {
              bankName = accountBankName;
              rawAccount = account['account_number']?.toString() ?? '';
              break;
            }
          }
        }
      }
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.withOpacity(0.1)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          const Icon(Icons.account_balance, color: Color(0xFF2563EB), size: 20),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              'BANK ACCOUNT: $rawAccount $bankName',
              style: GoogleFonts.inter(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: const Color(0xFF1F2937),
              ),
              overflow: TextOverflow.ellipsis,
            ),
          ),
          const SizedBox(width: 8),
          if (rawAccount.isNotEmpty)
            GestureDetector(
              onTap: () {
                Clipboard.setData(ClipboardData(text: rawAccount));
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Account number copied')),
                );
              },
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: const Color(0xFF2563EB),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'Copy',
                  style: GoogleFonts.inter(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
        ],
      ),
    ).animate().fadeIn(delay: 200.ms).moveY(begin: 10, end: 0);
  }

"""
content = re.sub(virtual_card_pattern, new_virtual_card + r"\3", content, flags=re.DOTALL | re.MULTILINE)


# Replace old services grid and promo banner with the new grids
services_grid_pattern = r"(Widget _buildServicesGrid\(bool isSmallScreen\) \{)(.*?)(^\s+void _showComingSoonDialog)"
new_services_grids = """Widget _buildGridGroup1(bool isSmallScreen) {
    final services = [
      {'iconImage': 'assets/images/logooG.png', 'label': 'To Oyitipay', 'route': '/internal-transfer', 'lock': 'transfer'},
      {'icon': Icons.account_balance_rounded, 'label': 'To Bank', 'action': 'bank'},
      {'icon': Icons.card_giftcard_rounded, 'label': 'Voucher', 'route': null},
      {'icon': Icons.loyalty_rounded, 'label': 'Cashback', 'route': null},
    ];
    return _buildCustomGrid(services, isSmallScreen);
  }

  Widget _buildGridGroup2(bool isSmallScreen) {
    final services = [
      {'icon': Icons.phone_android_rounded, 'label': 'Airtime', 'route': '/airtime', 'lock': 'airtime', 'badge': '2% off'},
      {'icon': Icons.wifi_rounded, 'label': 'Data', 'route': '/internet-data', 'lock': 'data', 'badge': '5% off'},
      {'icon': Icons.lightbulb_outline, 'label': 'Electricity', 'route': '/electricity', 'lock': 'electricity'},
      {'icon': Icons.tv_rounded, 'label': 'TV', 'route': '/tv-subscription', 'lock': 'tv'},
    ];
    return _buildCustomGrid(services, isSmallScreen);
  }

  Widget _buildGridGroup3(bool isSmallScreen) {
    final services = [
      {'icon': Icons.school_rounded, 'label': 'Edu PIN', 'route': '/exam-pin', 'lock': 'exam_pin'},
      {'icon': Icons.swap_horizontal_circle_outlined, 'label': 'A2Cash', 'route': '/a2cash', 'lock': 'airtime_to_cash'},
      {'icon': Icons.print_outlined, 'label': 'Airtime PIN', 'route': '/recharge-pin', 'lock': 'recharge_pin'},
      {'icon': Icons.grid_view_rounded, 'label': 'More', 'route': '/services', 'action': 'more'},
    ];
    return _buildCustomGrid(services, isSmallScreen);
  }

  Widget _buildCustomGrid(List<Map<String, dynamic>> services, bool isSmallScreen) {
    return Container(
      padding: EdgeInsets.all(isSmallScreen ? 12 : 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.withOpacity(0.1)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: GridView.builder(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        padding: EdgeInsets.zero,
        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 4,
          mainAxisSpacing: isSmallScreen ? 8 : 12,
          crossAxisSpacing: isSmallScreen ? 8 : 12,
          childAspectRatio: 0.85,
        ),
        itemCount: services.length,
        itemBuilder: (context, index) {
          final service = services[index];
          return GestureDetector(
            onTap: () async {
              if (service['action'] == 'more') {
                _showMoreServicesSheet(context);
                return;
              }
              if (service['action'] == 'bank') {
                await _checkKycAndNavigateToBank();
                return;
              }

              final lockKey = service['lock'] as String?;
              if (lockKey != null && lockKey == 'transfer' && _isTransferLocked) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Internal transfer is temporarily unavailable'), backgroundColor: Colors.orange),
                );
                return;
              }
              if (lockKey != null && lockKey != 'transfer' && _authService.isFeatureLocked(lockKey)) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('${service['label']} is temporarily unavailable'), backgroundColor: Colors.orange),
                );
                return;
              }

              if (service['route'] != null) {
                await context.push(service['route'] as String);
                _authService.refreshUser();
                _loadRecentTransactions();
              } else {
                _showComingSoonDialog(context, service['label'].toString());
              }
            },
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Stack(
                  clipBehavior: Clip.none,
                  alignment: Alignment.center,
                  children: [
                    Container(
                      width: 46,
                      height: 46,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(12),
                        color: const Color(0xFF2563EB).withOpacity(0.06),
                      ),
                      child: service['iconImage'] != null
                        ? Padding(
                            padding: const EdgeInsets.all(12.0),
                            child: Image.asset(
                              service['iconImage'] as String,
                              width: 20,
                              height: 20,
                            ),
                          )
                        : Icon(
                            service['icon'] as IconData,
                            color: const Color(0xFF2563EB),
                            size: 24,
                          ),
                    ),
                    if (service['badge'] != null)
                      Positioned(
                        top: -8,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF59E0B),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            service['badge'] as String,
                            style: GoogleFonts.inter(
                              color: Colors.white,
                              fontSize: 8,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  service['label'] as String,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: GoogleFonts.inter(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: const Color(0xFF4B5563),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    ).animate().fadeIn(delay: 200.ms).moveY(begin: 20, end: 0);
  }

  Widget _buildSpinWinBanner() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFF0FDF4),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFF16A34A).withOpacity(0.1)),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: const Color(0xFF16A34A).withOpacity(0.2)),
            ),
            child: const Icon(Icons.casino_outlined, color: Color(0xFF16A34A), size: 24),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Daily Spin & Win',
                  style: GoogleFonts.inter(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF1F2937),
                  ),
                ),
                Text(
                  'Spin the wheel and win up to \u20A6100',
                  style: GoogleFonts.inter(
                    fontSize: 11,
                    color: const Color(0xFF6B7280),
                  ),
                ),
              ],
            ),
          ),
          GestureDetector(
            onTap: () => _showComingSoonDialog(context, 'Daily Spin & Win'),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: const Color(0xFFDCFCE7),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                'Spin Now',
                style: GoogleFonts.inter(
                  color: const Color(0xFF16A34A),
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ],
      ),
    ).animate().fadeIn(delay: 300.ms);
  }

"""
content = re.sub(services_grid_pattern, new_services_grids + r"\3", content, flags=re.DOTALL | re.MULTILINE)


with open(filepath, 'w') as f:
    f.write(content)

