<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AccountService extends Service
{
    private function getAccounts(): array
    {
        return Cache::get('accounts', []);
    }

    private function saveAccounts(array $accounts): void
    {
        Cache::forever('accounts', $accounts);
    }

    public function reset(): void
    {
        Cache::forget('accounts');
    }

    public function getBalance(string $accountId): int|float|null
    {
        return $this->getAccounts()[$accountId] ?? null;
    }

    public function deposit(string $destinationId, int|float $amount): array
    {
        $accounts = $this->getAccounts();
        $accounts[$destinationId] = ($accounts[$destinationId] ?? 0) + $amount;
        $this->saveAccounts($accounts);

        return [
            'destination' => [
                'id' => $destinationId,
                'balance' => $accounts[$destinationId],
            ],
        ];
    }

    public function withdraw(string $originId, int|float $amount): array|null
    {
        $accounts = $this->getAccounts();

        // Rejeita se a conta não existe ou se o saldo é insuficiente para evitar saldo negativo
        if (!isset($accounts[$originId]) || $accounts[$originId] < $amount) {
            return null;
        }

        $accounts[$originId] -= $amount;
        $this->saveAccounts($accounts);

        return [
            'origin' => [
                'id' => $originId,
                'balance' => $accounts[$originId],
            ],
        ];
    }

    public function transfer(string $originId, string $destinationId, int|float $amount): array|null
    {
        $accounts = $this->getAccounts();

        // Rejeita a transferência inteira se a origem não existe, tem saldo insuficiente ou o destino não existe,
        // garantindo que nenhuma das contas seja alterada em caso de falha
        if (!isset($accounts[$originId]) || $accounts[$originId] < $amount || !isset($accounts[$destinationId])) {
            return null;
        }

        $accounts[$originId] -= $amount;
        $accounts[$destinationId] += $amount;
        $this->saveAccounts($accounts);

        return [
            'origin' => [
                'id' => $originId,
                'balance' => $accounts[$originId],
            ],
            'destination' => [
                'id' => $destinationId,
                'balance' => $accounts[$destinationId],
            ],
        ];
    }
}
