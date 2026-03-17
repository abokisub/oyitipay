<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('faqs')->insert([
            [
                'question' => 'What is OyitiPay?',
                'answer' => 'OyitiPay is a secure digital wallet and payment platform that allows you to manage your finances, pay bills, buy airtime and data, and send money with ease.',
                'category' => 'General',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'How do I fund my wallet?',
                'answer' => 'You can fund your wallet via bank transfer to your virtual account, USSD, or card payment. Navigate to the "Fund Wallet" section on your dashboard for more details.',
                'category' => 'Account',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Is my data secure?',
                'answer' => 'Yes, we use industry-standard encryption and security protocols to ensure your data and transactions are always protected.',
                'category' => 'Security',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'How can I contact support?',
                'answer' => 'You can reach us via our live chat, call our support line, or contact us through WhatsApp. Our support channels are available to assist you.',
                'category' => 'Support',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What services can I pay for on OyitiPay?',
                'answer' => 'You can buy airtime, data bundles, pay for cable TV subscriptions (DSTV, GOTV, Startimes), electricity bills, exam PINs (WAEC, NECO), and send bulk SMS.',
                'category' => 'Services',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Are there any charges for deposits?',
                'answer' => 'Deposit charges depend on your admin settings. When set to 0%, you enjoy FREE deposits with no fees deducted from your deposit amount.',
                'category' => 'Fees',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
