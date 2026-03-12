<?php

namespace Database\Factories;

use App\Models\PointWaveTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointWaveTransactionFactory extends Factory
{
    protected $model = PointWaveTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $types = ['deposit', 'transfer', 'withdrawal'];
        $statuses = ['pending', 'successful', 'failed'];
        $type = $this->faker->randomElement($types);
        
        return [
            'user_id' => User::factory(),
            'type' => $type,
            'amount' => $this->faker->randomFloat(2, 100, 50000),
            'fee' => $type === 'transfer' ? 50.00 : 0.00,
            'status' => $this->faker->randomElement($statuses),
            'reference' => 'PW-' . time() . '-' . $this->faker->unique()->randomNumber(5),
            'pointwave_transaction_id' => 'PWTXN-' . $this->faker->unique()->uuid(),
            'pointwave_customer_id' => 'PWCUST-' . $this->faker->uuid(),
            'account_number' => $type === 'transfer' ? $this->faker->numerify('##########') : null,
            'bank_code' => $type === 'transfer' ? $this->faker->numerify('###') : null,
            'account_name' => $type === 'transfer' ? $this->faker->name() : null,
            'narration' => $this->faker->sentence(),
            'metadata' => [
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
            ],
        ];
    }

    /**
     * Indicate that the transaction is a deposit.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function deposit()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'deposit',
                'fee' => 0.00,
                'account_number' => null,
                'bank_code' => null,
                'account_name' => null,
            ];
        });
    }

    /**
     * Indicate that the transaction is a transfer.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function transfer()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'transfer',
                'fee' => 50.00,
                'account_number' => $this->faker->numerify('##########'),
                'bank_code' => $this->faker->numerify('###'),
                'account_name' => $this->faker->name(),
            ];
        });
    }

    /**
     * Indicate that the transaction is successful.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function successful()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'successful',
            ];
        });
    }

    /**
     * Indicate that the transaction is pending.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    /**
     * Indicate that the transaction is failed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
            ];
        });
    }
}
