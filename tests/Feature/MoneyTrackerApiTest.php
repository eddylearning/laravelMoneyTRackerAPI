<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoneyTrackerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_without_password(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', 'test@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_wallet_and_transactions_are_returned_with_balance(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'name' => 'Business Wallet',
        ]);

        Transaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => 'income',
            'amount' => 1000,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'amount' => 250,
        ]);

        $response = $this->getJson("/api/wallets/{$wallet->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Business Wallet')
            ->assertJsonPath('data.balance', 750)
            ->assertJsonCount(2, 'data.transactions');
    }

    public function test_profile_returns_wallets_and_overall_balance(): void
    {
        $user = User::factory()->create();
        $walletA = Wallet::factory()->create(['user_id' => $user->id, 'name' => 'Main']);
        $walletB = Wallet::factory()->create(['user_id' => $user->id, 'name' => 'Side']);

        Transaction::factory()->create([
            'wallet_id' => $walletA->id,
            'type' => 'income',
            'amount' => 500,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $walletA->id,
            'type' => 'expense',
            'amount' => 100,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $walletB->id,
            'type' => 'income',
            'amount' => 300,
        ]);

        $response = $this->getJson("/api/users/{$user->id}/profile");

        $response
            ->assertOk()
            ->assertJsonPath('data.overall_balance', 700)
            ->assertJsonCount(2, 'data.wallets');
    }

    public function test_transaction_requires_positive_amount_and_valid_type(): void
    {
        $wallet = Wallet::factory()->create();

        $response = $this->postJson("/api/wallets/{$wallet->id}/transactions", [
            'type' => 'transfer',
            'amount' => -10,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'amount']);
    }
}
