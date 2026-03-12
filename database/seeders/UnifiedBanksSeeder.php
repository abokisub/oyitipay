<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UnifiedBanksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $banks = [
            // === COMMERCIAL BANKS ===
            ['name' => 'Access Bank', 'code' => '044', 'slug' => 'access-bank'],
            ['name' => 'Access Bank (Diamond)', 'code' => '063', 'slug' => 'access-bank-diamond'],
            ['name' => 'ALAT by WEMA', 'code' => '035A', 'slug' => 'alat-by-wema'],
            ['name' => 'Citibank Nigeria', 'code' => '023', 'slug' => 'citibank-nigeria'],
            ['name' => 'Ecobank Nigeria', 'code' => '050', 'slug' => 'ecobank-nigeria'],
            ['name' => 'Fidelity Bank', 'code' => '070', 'slug' => 'fidelity-bank'],
            ['name' => 'First Bank of Nigeria', 'code' => '011', 'slug' => 'first-bank-of-nigeria'],
            ['name' => 'First City Monument Bank', 'code' => '214', 'slug' => 'first-city-monument-bank'],
            ['name' => 'Globus Bank', 'code' => '00103', 'slug' => 'globus-bank'],
            ['name' => 'Guaranty Trust Bank', 'code' => '058', 'slug' => 'guaranty-trust-bank'],
            ['name' => 'Heritage Bank', 'code' => '030', 'slug' => 'heritage-bank'],
            ['name' => 'Jaiz Bank', 'code' => '301', 'slug' => 'jaiz-bank'],
            ['name' => 'Keystone Bank', 'code' => '082', 'slug' => 'keystone-bank'],
            ['name' => 'Optimus Bank', 'code' => '00107', 'slug' => 'optimus-bank'],
            ['name' => 'Parallex Bank', 'code' => '526', 'slug' => 'parallex-bank'],
            ['name' => 'Polaris Bank', 'code' => '076', 'slug' => 'polaris-bank'],
            ['name' => 'Premium Trust Bank', 'code' => '00031', 'slug' => 'premium-trust-bank'],
            ['name' => 'Providus Bank', 'code' => '101', 'slug' => 'providus-bank'],
            ['name' => 'Signature Bank', 'code' => '106', 'slug' => 'signature-bank'],
            ['name' => 'Stanbic IBTC Bank', 'code' => '221', 'slug' => 'stanbic-ibtc-bank'],
            ['name' => 'Standard Chartered Bank', 'code' => '068', 'slug' => 'standard-chartered-bank'],
            ['name' => 'Sterling Bank', 'code' => '232', 'slug' => 'sterling-bank'],
            ['name' => 'Suntrust Bank', 'code' => '100', 'slug' => 'suntrust-bank'],
            ['name' => 'Titan Bank', 'code' => '102', 'slug' => 'titan-bank'],
            ['name' => 'Union Bank of Nigeria', 'code' => '032', 'slug' => 'union-bank-of-nigeria'],
            ['name' => 'United Bank For Africa', 'code' => '033', 'slug' => 'united-bank-for-africa'],
            ['name' => 'Unity Bank', 'code' => '215', 'slug' => 'unity-bank'],
            ['name' => 'Wema Bank', 'code' => '035', 'slug' => 'wema-bank'],
            ['name' => 'Zenith Bank', 'code' => '057', 'slug' => 'zenith-bank'],

            // === MOBILE MONEY / FINTECH ===
            ['name' => 'Opay', 'code' => '999992', 'slug' => 'paycom'],
            ['name' => 'Palmpay', 'code' => '999991', 'slug' => 'palmpay'],
            ['name' => 'Kuda Bank', 'code' => '50211', 'slug' => 'kuda-bank'],
            ['name' => 'Moniepoint Microfinance Bank', 'code' => '50515', 'slug' => 'moniepoint-microfinance-bank'],
            ['name' => 'Carbon', 'code' => '565', 'slug' => 'carbon'],
            ['name' => 'FairMoney Microfinance Bank', 'code' => '51318', 'slug' => 'fairmoney-microfinance-bank'],
            ['name' => 'Paga', 'code' => '100002', 'slug' => 'paga'],
            ['name' => 'PiggyVest (Pocket App)', 'code' => '51225', 'slug' => 'abeg-app'],
            ['name' => 'VFD Microfinance Bank', 'code' => '566', 'slug' => 'vfd'],
            ['name' => 'Mintyn Bank', 'code' => '50219', 'slug' => 'mintyn-bank'],
            ['name' => 'Raven Bank', 'code' => '51325', 'slug' => 'raven-bank'],
            ['name' => 'Rubies MFB', 'code' => '125', 'slug' => 'rubies-mfb'],
            ['name' => 'Sparkle Microfinance Bank', 'code' => '51310', 'slug' => 'sparkle-microfinance-bank'],
            ['name' => '9 Payment Service Bank', 'code' => '120001', 'slug' => '9-payment-service-bank'],
            ['name' => 'Hope PSB', 'code' => '120002', 'slug' => 'hope-psb'],
            ['name' => 'MOMO PSB', 'code' => '120003', 'slug' => 'momo-psb'],
            ['name' => 'SmartCash PSB', 'code' => '120004', 'slug' => 'smartcash-psb'],

            // === MICROFINANCE BANKS (Significant Only) ===
            ['name' => 'Abbey Mortgage Bank', 'code' => '801', 'slug' => 'abbey-mortgage-bank'],
            ['name' => 'Accion Microfinance Bank', 'code' => '50132', 'slug' => 'accion-microfinance-bank'],
            ['name' => 'Addosser Microfinance Bank', 'code' => '50123', 'slug' => 'addosser-microfinance-bank'],
            ['name' => 'Advans La Fayette Microfinance Bank', 'code' => '50223', 'slug' => 'advans-la-fayette-microfinance-bank'],
            ['name' => 'Amjun Doyi MFB', 'code' => '50926', 'slug' => 'amju-unique-mfb'],
            ['name' => 'ASO Savings and Loans', 'code' => '401', 'slug' => 'aso-savings-and-loans'],
            ['name' => 'Baines Credit MFB', 'code' => '51229', 'slug' => 'baines-credit-mfb'],
            ['name' => 'Baobab Microfinance Bank', 'code' => '50125', 'slug' => 'baobab-microfinance-bank'],
            ['name' => 'Bowen Microfinance Bank', 'code' => '50931', 'slug' => 'bowen-microfinance-bank'],
            ['name' => 'CEMCS Microfinance Bank', 'code' => '50823', 'slug' => 'cemcs-microfinance-bank'],
            ['name' => 'Chanelle Microfinance Bank', 'code' => '50171', 'slug' => 'chanelle-microfinance-bank'],
            ['name' => 'Corestep Microfinance Bank', 'code' => '50204', 'slug' => 'corestep-microfinance-bank'],
            ['name' => 'Coronation Merchant Bank', 'code' => '559', 'slug' => 'coronation-merchant-bank'],
            ['name' => 'Daylight Microfinance Bank', 'code' => '50155', 'slug' => 'daylight-microfinance-bank'],
            ['name' => 'Ekondo Microfinance Bank', 'code' => '562', 'slug' => 'ekondo-microfinance-bank'],
            ['name' => 'Empire Trust Microfinance Bank', 'code' => '50285', 'slug' => 'empire-trust-microfinance-bank'],
            ['name' => 'FBNQuest Merchant Bank', 'code' => '060002', 'slug' => 'fbn-quest-merchant-bank'],
            ['name' => 'Fina Trust Microfinance Bank', 'code' => '50165', 'slug' => 'fina-trust-microfinance-bank'],
            ['name' => 'Firmus MFB', 'code' => '50296', 'slug' => 'firmus-mfb'],
            ['name' => 'FirstTrust Mortgage Bank Plc', 'code' => '90114', 'slug' => 'first-trust-mortgage-bank-plc'],
            ['name' => 'Fortis Microfinance Bank', 'code' => '50074', 'slug' => 'fortis-microfinance-bank'],
            ['name' => 'Gateway Mortgage Bank', 'code' => '802', 'slug' => 'gateway-mortgage-bank'],
            ['name' => 'Globus Bank', 'code' => '00103', 'slug' => 'globus-bank'],
            ['name' => 'GoMoney', 'code' => '100022', 'slug' => 'gomoney'],
            ['name' => 'Greenwich Merchant Bank', 'code' => '00003', 'slug' => 'greenwich-merchant-bank'],
            ['name' => 'Hackman Microfinance Bank', 'code' => '51251', 'slug' => 'hackman-microfinance-bank'],
            ['name' => 'Hasal Microfinance Bank', 'code' => '50383', 'slug' => 'hasal-microfinance-bank'],
            ['name' => 'Ibile Microfinance Bank', 'code' => '51244', 'slug' => 'ibile-microfinance-bank'],
            ['name' => 'Infinity MFB', 'code' => '50457', 'slug' => 'infinity-mfb'],
            ['name' => 'Kadpoly Microfinance Bank', 'code' => '50502', 'slug' => 'kadpoly-microfinance-bank'],
            ['name' => 'Lagos Building Investment Company Plc.', 'code' => '90052', 'slug' => 'lbic-plc'],
            ['name' => 'Links MFB', 'code' => '50549', 'slug' => 'links-mfb'],
            ['name' => 'Living Trust Mortgage Bank', 'code' => '00030', 'slug' => 'living-trust-mortgage-bank'],
            ['name' => 'Lotus Bank', 'code' => '303', 'slug' => 'lotus-bank'],
            ['name' => 'Mayfair MFB', 'code' => '50563', 'slug' => 'mayfair-mfb'],
            ['name' => 'Mint-Finex MFB', 'code' => '50304', 'slug' => 'mint-finex-mfb'],
            ['name' => 'Money Master PSB', 'code' => '946', 'slug' => 'moneymaster-psb'],
            ['name' => 'Nova Merchant Bank', 'code' => '00002', 'slug' => 'nova-merchant-bank'],
            ['name' => 'Omoluabi Mortgage Bank', 'code' => '90011', 'slug' => 'omoluabi-mortgage-bank'],
            ['name' => 'Parkway - ReadyCash', 'code' => '311', 'slug' => 'parkway-ready-cash'],
            ['name' => 'Petra Mircofinance Bank Plc', 'code' => '50746', 'slug' => 'petra-microfinance-bank-plc'],
            ['name' => 'Platinum Mortgage Bank', 'code' => '808', 'slug' => 'platinum-mortgage-bank'],
            ['name' => 'Rand Merchant Bank', 'code' => '502', 'slug' => 'rand-merchant-bank'],
            ['name' => 'Refuge Mortgage Bank', 'code' => '819', 'slug' => 'refuge-mortgage-bank'],
            ['name' => 'Rigo Microfinance Bank', 'code' => '51286', 'slug' => 'rigo-microfinance-bank'],
            ['name' => 'Safe Haven MFB', 'code' => '50902', 'slug' => 'safe-haven-mfb'],
            ['name' => 'Seedvest Microfinance Bank', 'code' => '50809', 'slug' => 'seedvest-microfinance-bank'],
            ['name' => 'Spectrum Microfinance Bank', 'code' => '50785', 'slug' => 'spectrum-microfinance-bank'],
            ['name' => 'TAJ Bank', 'code' => '302', 'slug' => 'taj-bank'],
            ['name' => 'TCF MFB', 'code' => '51211', 'slug' => 'tcf-mfb'],
            ['name' => 'Unical MFB', 'code' => '50873', 'slug' => 'unical-mfb'],
            ['name' => 'VFD Microfinance Bank', 'code' => '566', 'slug' => 'vfd'],
        ];

        $now = Carbon::now();

        foreach ($banks as $bank) {
            DB::table('unified_banks')->updateOrInsert(
                ['code' => $bank['code']],
                [
                    'name' => $bank['name'],
                    'paystack_code' => $bank['code'],
                    'xixapay_code' => $bank['code'],
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('Targeted Unified Banks Seeder run successfully. ' . count($banks) . ' banks processed.');
    }
}
