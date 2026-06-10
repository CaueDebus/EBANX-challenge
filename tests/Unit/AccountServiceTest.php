<?php

namespace Tests\Unit;

use App\Services\AccountService;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    private AccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountService();
        $this->service->reset();
    }

    public function test_get_balance_returns_null_for_non_existing_account(): void
    {
        $balance = $this->service->getBalance('999');

        $this->assertNull($balance);
    }

    public function test_deposit_creates_account_with_initial_balance(): void
    {
        $result = $this->service->deposit('100', 10);

        $this->assertEquals(['destination' => ['id' => '100', 'balance' => 10]], $result);
    }

    public function test_deposit_accumulates_balance_on_existing_account(): void
    {
        $this->service->deposit('100', 10);
        $result = $this->service->deposit('100', 10);

        $this->assertEquals(['destination' => ['id' => '100', 'balance' => 20]], $result);
    }

    public function test_get_balance_returns_correct_balance_after_deposit(): void
    {
        $this->service->deposit('100', 10);

        $balance = $this->service->getBalance('100');

        $this->assertEquals(10, $balance);
    }

    public function test_withdraw_returns_null_for_non_existing_account(): void
    {
        $result = $this->service->withdraw('200', 10);

        $this->assertNull($result);
    }

    public function test_withdraw_returns_null_when_balance_is_insufficient(): void
    {
        $this->service->deposit('100', 5);

        $result = $this->service->withdraw('100', 10);

        $this->assertNull($result);
    }

    public function test_withdraw_does_not_alter_balance_when_insufficient(): void
    {
        $this->service->deposit('100', 5);

        $this->service->withdraw('100', 10);

        $this->assertEquals(5, $this->service->getBalance('100'));
    }

    public function test_withdraw_deducts_balance_from_existing_account(): void
    {
        $this->service->deposit('100', 20);

        $result = $this->service->withdraw('100', 5);

        $this->assertEquals(['origin' => ['id' => '100', 'balance' => 15]], $result);
    }

    public function test_transfer_returns_null_for_non_existing_origin(): void
    {
        $result = $this->service->transfer('200', '300', 15);

        $this->assertNull($result);
    }

    public function test_transfer_returns_null_when_balance_is_insufficient(): void
    {
        $this->service->deposit('100', 5);

        $result = $this->service->transfer('100', '300', 10);

        $this->assertNull($result);
    }

    public function test_transfer_does_not_alter_any_balance_when_insufficient(): void
    {
        $this->service->deposit('100', 5);

        $this->service->transfer('100', '300', 10);

        $this->assertEquals(5, $this->service->getBalance('100'));
        $this->assertNull($this->service->getBalance('300'));
    }

    public function test_transfer_moves_amount_between_accounts(): void
    {
        $this->service->deposit('100', 15);

        $result = $this->service->transfer('100', '300', 15);

        $this->assertEquals([
            'origin' => ['id' => '100', 'balance' => 0],
            'destination' => ['id' => '300', 'balance' => 15],
        ], $result);
    }

    public function test_transfer_creates_destination_account_if_not_exists(): void
    {
        $this->service->deposit('100', 50);

        $this->service->transfer('100', '300', 20);

        $this->assertEquals(20, $this->service->getBalance('300'));
    }

    public function test_reset_clears_all_accounts(): void
    {
        $this->service->deposit('100', 10);

        $this->service->reset();

        $this->assertNull($this->service->getBalance('100'));
    }
}
