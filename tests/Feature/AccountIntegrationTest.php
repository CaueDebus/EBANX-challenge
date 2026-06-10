<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccountIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->post('/reset');
    }

    public function test_full_spec_sequence(): void
    {
        $this->get('/balance?account_id=1234')->assertStatus(404)->assertContent('0');

        $this->postJson('/event', ['type' => 'deposit', 'destination' => '100', 'amount' => 10])
            ->assertStatus(201)
            ->assertJson(['destination' => ['id' => '100', 'balance' => 10]]);

        $this->postJson('/event', ['type' => 'deposit', 'destination' => '100', 'amount' => 10])
            ->assertStatus(201)
            ->assertJson(['destination' => ['id' => '100', 'balance' => 20]]);

        $this->get('/balance?account_id=100')->assertStatus(200)->assertContent('20');

        $this->postJson('/event', ['type' => 'withdraw', 'origin' => '200', 'amount' => 10])
            ->assertStatus(404)->assertContent('0');

        $this->postJson('/event', ['type' => 'withdraw', 'origin' => '100', 'amount' => 5])
            ->assertStatus(201)
            ->assertJson(['origin' => ['id' => '100', 'balance' => 15]]);

        $this->postJson('/event', ['type' => 'deposit', 'destination' => '300', 'amount' => 5]);

        $this->postJson('/event', ['type' => 'transfer', 'origin' => '100', 'amount' => 15, 'destination' => '300'])
            ->assertStatus(201)
            ->assertJson([
                'origin' => ['id' => '100', 'balance' => 0],
                'destination' => ['id' => '300', 'balance' => 20],
            ]);

        $this->postJson('/event', ['type' => 'transfer', 'origin' => '200', 'amount' => 15, 'destination' => '300'])
            ->assertStatus(404)->assertContent('0');
    }

    public function test_withdraw_is_rejected_when_balance_is_insufficient(): void
    {
        $this->postJson('/event', ['type' => 'deposit', 'destination' => '100', 'amount' => 5]);

        $response = $this->postJson('/event', ['type' => 'withdraw', 'origin' => '100', 'amount' => 10]);

        $response->assertStatus(404);
        $response->assertContent('0');
    }

    public function test_balance_does_not_go_negative_after_failed_withdraw(): void
    {
        $this->postJson('/event', ['type' => 'deposit', 'destination' => '100', 'amount' => 5]);

        $this->postJson('/event', ['type' => 'withdraw', 'origin' => '100', 'amount' => 10]);

        $this->get('/balance?account_id=100')->assertStatus(200)->assertContent('5');
    }

    public function test_transfer_is_rejected_when_balance_is_insufficient(): void
    {
        $this->postJson('/event', ['type' => 'deposit', 'destination' => '100', 'amount' => 5]);

        $response = $this->postJson('/event', [
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '300',
            'amount' => 10,
        ]);

        $response->assertStatus(404);
        $response->assertContent('0');
    }

    public function test_transfer_does_not_alter_any_balance_when_insufficient(): void
    {
        $this->postJson('/event', ['type' => 'deposit', 'destination' => '100', 'amount' => 5]);

        $this->postJson('/event', [
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '300',
            'amount' => 10,
        ]);

        $this->get('/balance?account_id=100')->assertStatus(200)->assertContent('5');
        $this->get('/balance?account_id=300')->assertStatus(404);
    }

    public function test_get_balance_does_not_alter_state(): void
    {
        $this->postJson('/event', ['type' => 'deposit', 'destination' => '100', 'amount' => 50]);

        $this->get('/balance?account_id=100');
        $this->get('/balance?account_id=100');

        $this->get('/balance?account_id=100')->assertStatus(200)->assertContent('50');
    }

    public function test_transfer_to_same_account_is_rejected(): void
    {
        $this->postJson('/event', ['type' => 'deposit', 'destination' => '100', 'amount' => 50]);

        $response = $this->postJson('/event', [
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '100',
            'amount' => 10,
        ]);

        $response->assertStatus(422);
        $this->get('/balance?account_id=100')->assertContent('50');
    }

    public function test_transfer_is_rejected_when_destination_does_not_exist(): void
    {
        $this->postJson('/event', ['type' => 'deposit', 'destination' => '100', 'amount' => 50]);

        $response = $this->postJson('/event', [
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '300',
            'amount' => 20,
        ]);

        $response->assertStatus(404);
        $response->assertContent('0');
    }

    public function test_transfer_does_not_alter_origin_balance_when_destination_does_not_exist(): void
    {
        $this->postJson('/event', ['type' => 'deposit', 'destination' => '100', 'amount' => 50]);

        $this->postJson('/event', [
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '300',
            'amount' => 20,
        ]);

        $this->get('/balance?account_id=100')->assertStatus(200)->assertContent('50');
        $this->get('/balance?account_id=300')->assertStatus(404);
    }

    public function test_reset_clears_all_state(): void
    {
        $this->postJson('/event', ['type' => 'deposit', 'destination' => '100', 'amount' => 50]);

        $this->post('/reset')->assertStatus(200);

        $this->get('/balance?account_id=100')->assertStatus(404);
    }
}
