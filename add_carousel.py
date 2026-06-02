import re

filepath = "Oyitipay Mobile/lib/modules/dashboard/screens/dashboard_screen.dart"

with open(filepath, 'r') as f:
    content = f.read()

# Replace _buildSpinWinBanner with a new carousel method
carousel_pattern = r"(Widget _buildSpinWinBanner\(\) \{)(.*?)(^\s+Widget _buildMiniRecentTransactions)"
new_carousel = """Widget _buildSpinWinBanner() {
    final promos = [
      {
        'icon': Icons.wifi_rounded,
        'title': 'Buy Cheap Data',
        'desc': 'Get up to 5% discount on all data purchases instantly.',
        'route': '/internet-data',
        'color': const Color(0xFF2563EB), // Blue
        'bg_color': const Color(0xFFEFF6FF),
      },
      {
        'icon': Icons.swap_horizontal_circle_outlined,
        'title': 'Airtime to Cash',
        'desc': 'Convert your excess airtime to instant cash at best rates.',
        'route': '/a2cash',
        'color': const Color(0xFF16A34A), // Green
        'bg_color': const Color(0xFFF0FDF4),
      },
      {
        'icon': Icons.people_alt_outlined,
        'title': 'Refer & Earn',
        'desc': 'Invite friends and earn bonuses for every active referral.',
        'route': '/referral', // Assuming this is the route
        'color': const Color(0xFFD97706), // Orange
        'bg_color': const Color(0xFFFFFBEB),
      },
    ];

    return CarouselSlider(
      options: CarouselOptions(
        height: 75.0, // Keeping it tight to fit the screen
        autoPlay: true,
        autoPlayInterval: const Duration(seconds: 4),
        autoPlayAnimationDuration: const Duration(milliseconds: 800),
        autoPlayCurve: Curves.fastOutSlowIn,
        viewportFraction: 1.0, // Take full width
        enableInfiniteScroll: true,
      ),
      items: promos.map((promo) {
        return Builder(
          builder: (BuildContext context) {
            return GestureDetector(
              onTap: () async {
                final route = promo['route'] as String?;
                if (route != null && route.isNotEmpty) {
                  // Handle lock keys just in case
                  if (route == '/a2cash' && _authService.isFeatureLocked('airtime_to_cash')) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Airtime to Cash is temporarily unavailable'), backgroundColor: Colors.orange),
                    );
                    return;
                  }
                  if (route == '/internet-data' && _authService.isFeatureLocked('data')) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Data is temporarily unavailable'), backgroundColor: Colors.orange),
                    );
                    return;
                  }
                  
                  if (route == '/referral') {
                    // Check if referral route exists or handle coming soon
                     _showComingSoonDialog(context, 'Refer & Earn');
                     return;
                  }

                  await context.push(route);
                  _authService.refreshUser();
                  _loadRecentTransactions();
                }
              },
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  color: promo['bg_color'] as Color,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: (promo['color'] as Color).withOpacity(0.15)),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: (promo['color'] as Color).withOpacity(0.2)),
                      ),
                      child: Icon(promo['icon'] as IconData, color: promo['color'] as Color, size: 22),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            promo['title'] as String,
                            style: GoogleFonts.inter(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: const Color(0xFF1F2937),
                            ),
                          ),
                          Text(
                            promo['desc'] as String,
                            style: GoogleFonts.inter(
                              fontSize: 10,
                              color: const Color(0xFF6B7280),
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    Icon(Icons.arrow_forward_ios_rounded, size: 14, color: (promo['color'] as Color).withOpacity(0.6)),
                  ],
                ),
              ),
            );
          },
        );
      }).toList(),
    ).animate().fadeIn(delay: 300.ms);
  }

"""
content = re.sub(carousel_pattern, new_carousel + r"\3", content, flags=re.DOTALL | re.MULTILINE)

# Replace 'Spin and Win Banner' comment with 'Carousel Banner' in the build method
content = content.replace("// Daily Spin and Win Banner", "// Promotional Carousel Banner")

with open(filepath, 'w') as f:
    f.write(content)

